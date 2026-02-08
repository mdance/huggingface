<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huggingface\HuggingFaceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the InferenceEndpointsSyncForm class.
 */
class InferenceEndpointsSyncForm extends FormBase {

  /**
   * Provides the constructor method.
   *
   * @param \Drupal\huggingface\HuggingFaceServiceInterface $service
   *   Provides the module service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected HuggingFaceServiceInterface $service,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('huggingface'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'huggingface_sync_inference_endpoints';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Import your HuggingFace Inference Endpoints into Drupal. Enter your credentials below, then select the endpoints you want to sync.') . '</p>',
    ];

    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('HuggingFace Credentials'),
      '#open' => TRUE,
    ];

    $access_token = $values['access_token'] ?? $this->service->getAccessToken();

    $has_global_token = !empty($this->service->getAccessToken());

    $form['credentials']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $access_token,
      '#description' => $has_global_token
        ? $this->t('Using token from <a href="/admin/config/services/huggingface/settings">global settings</a>. You can override it here if needed.')
        : $this->t('Your HuggingFace API token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co/settings/tokens</a>, or configure it in <a href="/admin/config/services/huggingface/settings">global settings</a>.'),
      '#required' => !$has_global_token,
    ];

    $namespace = $values['namespace'] ?? '';

    $form['credentials']['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace'),
      '#default_value' => $namespace,
      '#description' => $this->t('Your HuggingFace username or organization name. This is the namespace where your Inference Endpoints are deployed.'),
      '#required' => TRUE,
    ];

    $form['credentials']['fetch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fetch Endpoints'),
      '#submit' => ['::fetchEndpoints'],
      '#limit_validation_errors' => [['access_token'], ['namespace']],
    ];

    if (!empty($namespace)) {
      $parameters = [
        'access_token' => $access_token,
        'namespace' => $namespace,
      ];

      $error_message = NULL;

      try {
        $results = $this->service->getInferenceEndpoints($parameters);
      }
      catch (\Exception $e) {
        $error_message = $e->getMessage();
        $results = NULL;
      }

      if ($error_message) {
        $form['error'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--error">' . $this->t('Failed to fetch endpoints: @error', ['@error' => $error_message]) . '</div>',
        ];
      }
      else {
        $header = [
          'type' => $this->t('Type'),
          'name' => $this->t('Name'),
          'model' => $this->t('Model'),
          'task' => $this->t('Task'),
          'status' => $this->t('State'),
          'url' => $this->t('URL'),
        ];

        $items = $results->items ?? [];
        $options = [];

        foreach ($items as $item) {
          $name = $item->name;
          $state = $item->status->state ?? 'unknown';

          // Add visual indicator for state.
          $state_class = match (strtolower($state)) {
            'running' => 'color-success',
            'paused', 'scaledtozero' => 'color-warning',
            'failed' => 'color-error',
            default => '',
          };

          $options[$name] = [
            'type' => $item->type ?? 'protected',
            'name' => $name,
            'model' => $item->model->repository ?? '',
            'task' => $item->model->task ?? '',
            'status' => [
              'data' => ['#markup' => '<span class="' . $state_class . '">' . $state . '</span>'],
            ],
            'url' => $item->status->url ?? $this->t('Not available'),
          ];
        }

        $form['endpoints'] = [
          '#type' => 'details',
          '#title' => $this->t('Available Endpoints (@count)', ['@count' => count($options)]),
          '#open' => TRUE,
        ];

        $form['endpoints']['help'] = [
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('Select the endpoints you want to import. Existing endpoints with the same ID will be skipped.') . '</p>',
        ];

        $form['endpoints']['items'] = [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $options,
          '#empty' => $this->t('No inference endpoints found in this namespace. Create endpoints at <a href="https://ui.endpoints.huggingface.co" target="_blank">ui.endpoints.huggingface.co</a>.'),
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Selected Endpoints'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Submit handler to fetch endpoints without full form submission.
   */
  public function fetchEndpoints(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $access_token = $values['access_token'] ?? $this->service->getAccessToken();
    $namespace = $values['namespace'] ?? '';

    $items = $values['items'] ?? [];
    $items = array_filter($items);

    if (!empty($items)) {
      $parameters = [
        'access_token' => $access_token,
        'namespace' => $namespace,
      ];

      try {
        $response = $this->service->getInferenceEndpoints($parameters);
        $results = $response->items ?? [];

        $storage = $this->entityTypeManager->getStorage('inference_endpoint');

        foreach ($items as $item) {
          foreach ($results as $result) {
            if ($result->name != $item) {
              continue;
            }

            try {
              $data = [];

              $data['id'] = $namespace . '-' . $result->name;
              $data['label'] = $result->name;
              $data['status'] = TRUE;
              $data['description'] = '';
              // Only store access token if different from global setting.
              $global_token = $this->service->getAccessToken();
              $data['accessToken'] = ($access_token !== $global_token) ? $access_token : '';
              $data['namespace'] = $namespace;
              $data['type'] = $result->type ?? 'protected';
              $data['name'] = $result->name;
              $data['accountId'] = $result->accountId ?? '';
              $data['model'] = $result->model->repository ?? '';
              $data['framework'] = $result->model->framework ?? '';
              $data['revision'] = $result->model->revision ?? '';
              $data['task'] = $result->model->task ?? '';
              $data['state'] = $result->status->state ?? '';
              $data['url'] = $result->status->url ?? '';
              $data['createdAt'] = $result->status->createdAt ?? '';
              $data['updatedAt'] = $result->status->updatedAt ?? '';

              // Compute configuration.
              if (isset($result->compute)) {
                $data['accelerator'] = $result->compute->accelerator ?? '';
                $data['instanceSize'] = $result->compute->instanceSize ?? '';
                $data['instanceType'] = $result->compute->instanceType ?? '';
                if (isset($result->compute->scaling)) {
                  $data['minReplica'] = $result->compute->scaling->minReplica ?? 0;
                  $data['maxReplica'] = $result->compute->scaling->maxReplica ?? 1;
                  $data['scaleToZeroTimeout'] = $result->compute->scaling->scaleToZeroTimeout ?? NULL;
                }
              }

              // Provider configuration.
              if (isset($result->provider)) {
                $data['region'] = $result->provider->region ?? '';
                $data['vendor'] = $result->provider->vendor ?? '';
              }

              $entity = $storage->create($data);

              $entity->save();
            }
            catch (\Exception $e) {
              $args = [];

              $args['@name'] = $result->name;

              $message = $this->t('An error occurred creating the inference endpoint configuration for @name', $args);

              $form_state->setError($form, $message);
            }
          }
        }
      }
      catch (\Exception $e) {
        $message = $this->t('An error occurred syncing the inference endpoints, please try again later.');

        $form_state->setError($form, $message);
      }
    }

    $form_state->setRebuild();
  }

}
