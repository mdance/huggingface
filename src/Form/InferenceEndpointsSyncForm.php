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
   * @param HuggingFaceServiceInterface $service
   *   Provides the module service.
   */
  public function __construct(
    protected HuggingFaceServiceInterface $service,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('huggingface'),
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

    $key = 'access_token';

    $access_token = $values[$key] ?? $this->service->getAccessToken();

    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $access_token,
    ];

    $key = 'namespace';

    $namespace = $values[$key] ?? '';

    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace'),
      '#default_value' => $namespace,
    ];

    if (!empty($namespace)) {
      $parameters = [
        'access_token' => $access_token,
        'namespace' => $namespace,
      ];

      try {
        $results = $this->service->getInferenceEndpoints($parameters);
      } catch (\Exception $e) {}

      $header = [];

      $header['type'] = $this->t('Type');
      $header['name'] = $this->t('Name');
      $header['model'] = $this->t('Model');
      $header['task'] = $this->t('Task');
      $header['status'] = $this->t('Status');
      $header['url'] = $this->t('URL');
      $header['account_id'] = $this->t('Account ID');

      $items = $results->items ?? [];

      $options = [];

      foreach ($items as $item) {
        $option = [];

        $name = $item->name;

        $option['type'] = $item->type;
        $option['name'] = $name;
        $option['model'] = $item->model->repository;
        $option['task'] = $item->model->task;
        $option['status'] = $item->status->state;
        $option['url'] = $item->status->url ?? '';
        $option['account_id'] = $item->accountId ?? '';

        $options[$name] = $option;
      }

      $form['items'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $options,
        '#empty' => $this->t('There are no inference endpoints.'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $actions = &$form['actions'];

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
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

        $storage = \Drupal::entityTypeManager()->getStorage('inference_endpoint');

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
              $data['accessToken'] = $access_token;
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
