<?php

declare(strict_types=1);

namespace Drupal\huggingface\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huggingface\HuggingFaceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for managing inference endpoint lifecycle actions.
 */
class InferenceEndpointActionsForm extends FormBase {

  /**
   * Constructs the form.
   *
   * @param \Drupal\huggingface\HuggingFaceServiceInterface $service
   *   The HuggingFace service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected HuggingFaceServiceInterface $service,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
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
    return 'huggingface_inference_endpoint_actions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $inference_endpoint = NULL) {
    $form['#inference_endpoint'] = $inference_endpoint;

    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $endpoint */
    $endpoint = $this->entityTypeManager->getStorage('inference_endpoint')->load($inference_endpoint);

    if (!$endpoint) {
      $form['error'] = [
        '#markup' => $this->t('Inference endpoint not found.'),
      ];
      return $form;
    }

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint Information'),
      '#open' => TRUE,
    ];

    $form['info']['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Name'),
      '#markup' => $endpoint->get('name'),
    ];

    $form['info']['namespace'] = [
      '#type' => 'item',
      '#title' => $this->t('Namespace'),
      '#markup' => $endpoint->get('namespace'),
    ];

    $form['info']['state'] = [
      '#type' => 'item',
      '#title' => $this->t('Current State'),
      '#markup' => $endpoint->get('state') ?: $this->t('Unknown'),
    ];

    $form['info']['url'] = [
      '#type' => 'item',
      '#title' => $this->t('URL'),
      '#markup' => $endpoint->get('url') ?: $this->t('Not available'),
    ];

    // Refresh button to get current status.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh Status'),
      '#submit' => ['::refreshStatus'],
    ];

    $state = strtolower($endpoint->get('state') ?: '');

    // Show appropriate action buttons based on current state.
    if ($state === 'running' || $state === 'scaled_to_zero' || $state === 'scaledtozero') {
      $form['actions']['pause'] = [
        '#type' => 'submit',
        '#value' => $this->t('Pause Endpoint'),
        '#submit' => ['::pauseEndpoint'],
        '#attributes' => ['class' => ['button--danger']],
      ];

      $form['actions']['scale_to_zero'] = [
        '#type' => 'submit',
        '#value' => $this->t('Scale to Zero'),
        '#submit' => ['::scaleToZero'],
      ];
    }

    if ($state === 'paused') {
      $form['actions']['resume'] = [
        '#type' => 'submit',
        '#value' => $this->t('Resume Endpoint'),
        '#submit' => ['::resumeEndpoint'],
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Default submit does nothing.
  }

  /**
   * Refreshes the endpoint status from the API.
   */
  public function refreshStatus(array &$form, FormStateInterface $form_state) {
    $endpoint_id = $form['#inference_endpoint'];

    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $endpoint */
    $endpoint = $this->entityTypeManager->getStorage('inference_endpoint')->load($endpoint_id);

    if (!$endpoint) {
      $this->messenger()->addError($this->t('Endpoint not found.'));
      return;
    }

    $namespace = $endpoint->get('namespace');
    $name = $endpoint->get('name');
    $access_token = $endpoint->get('accessToken');

    try {
      $result = $this->service->getInferenceEndpoint($namespace, $name, [
        'access_token' => $access_token,
      ]);

      // Update local entity with fresh data.
      $endpoint->set('state', $result->status->state ?? '');
      $endpoint->set('url', $result->status->url ?? '');
      $endpoint->set('updatedAt', $result->status->updatedAt ?? '');

      if (isset($result->compute->scaling)) {
        $endpoint->set('minReplica', $result->compute->scaling->minReplica ?? 0);
        $endpoint->set('maxReplica', $result->compute->scaling->maxReplica ?? 1);
      }

      $endpoint->save();

      $this->messenger()->addStatus($this->t('Endpoint status refreshed. Current state: @state', [
        '@state' => $result->status->state ?? 'unknown',
      ]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to refresh endpoint status: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }

  /**
   * Pauses the inference endpoint.
   */
  public function pauseEndpoint(array &$form, FormStateInterface $form_state) {
    $endpoint_id = $form['#inference_endpoint'];

    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $endpoint */
    $endpoint = $this->entityTypeManager->getStorage('inference_endpoint')->load($endpoint_id);

    if (!$endpoint) {
      $this->messenger()->addError($this->t('Endpoint not found.'));
      return;
    }

    $namespace = $endpoint->get('namespace');
    $name = $endpoint->get('name');
    $access_token = $endpoint->get('accessToken');

    try {
      $result = $this->service->pauseInferenceEndpoint($namespace, $name, [
        'access_token' => $access_token,
      ]);

      // Update local entity.
      $endpoint->set('state', $result->status->state ?? 'paused');
      $endpoint->set('minReplica', 0);
      $endpoint->set('maxReplica', 0);
      $endpoint->save();

      $this->messenger()->addStatus($this->t('Endpoint paused successfully.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to pause endpoint: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }

  /**
   * Resumes the inference endpoint.
   */
  public function resumeEndpoint(array &$form, FormStateInterface $form_state) {
    $endpoint_id = $form['#inference_endpoint'];

    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $endpoint */
    $endpoint = $this->entityTypeManager->getStorage('inference_endpoint')->load($endpoint_id);

    if (!$endpoint) {
      $this->messenger()->addError($this->t('Endpoint not found.'));
      return;
    }

    $namespace = $endpoint->get('namespace');
    $name = $endpoint->get('name');
    $access_token = $endpoint->get('accessToken');

    try {
      $result = $this->service->resumeInferenceEndpoint($namespace, $name, [
        'access_token' => $access_token,
      ]);

      // Update local entity.
      $endpoint->set('state', $result->status->state ?? 'initializing');
      $endpoint->set('minReplica', 1);
      $endpoint->set('maxReplica', 1);
      $endpoint->save();

      $this->messenger()->addStatus($this->t('Endpoint resume initiated. It may take a few minutes to fully start.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to resume endpoint: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }

  /**
   * Scales the endpoint to zero.
   */
  public function scaleToZero(array &$form, FormStateInterface $form_state) {
    $endpoint_id = $form['#inference_endpoint'];

    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $endpoint */
    $endpoint = $this->entityTypeManager->getStorage('inference_endpoint')->load($endpoint_id);

    if (!$endpoint) {
      $this->messenger()->addError($this->t('Endpoint not found.'));
      return;
    }

    $namespace = $endpoint->get('namespace');
    $name = $endpoint->get('name');
    $access_token = $endpoint->get('accessToken');

    try {
      $result = $this->service->scaleToZeroInferenceEndpoint($namespace, $name, [
        'access_token' => $access_token,
      ]);

      // Update local entity.
      $endpoint->set('state', $result->status->state ?? 'scaledToZero');
      $endpoint->set('minReplica', 0);
      $endpoint->save();

      $this->messenger()->addStatus($this->t('Endpoint scaled to zero. It will automatically restart on the next request.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to scale endpoint to zero: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }

}
