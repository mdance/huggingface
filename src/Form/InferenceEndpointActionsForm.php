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
        '#markup' => '<div class="messages messages--error">' . $this->t('Inference endpoint configuration not found.') . '</div>',
      ];
      return $form;
    }

    $state = strtolower($endpoint->get('state') ?: '');

    // Determine state styling.
    $state_class = match ($state) {
      'running' => 'color-success',
      'paused' => 'color-warning',
      'scaledtozero', 'scaled_to_zero' => 'color-warning',
      'initializing', 'pending', 'updating' => 'messages--status',
      'failed' => 'color-error',
      default => '',
    };

    $state_display = $endpoint->get('state') ?: $this->t('Unknown');

    // Endpoint Information.
    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint Information'),
      '#open' => TRUE,
    ];

    $form['info']['status_banner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="' . $state_class . '" style="padding: 10px; margin-bottom: 15px; border-radius: 4px;"><strong>' . $this->t('Status:') . '</strong> ' . $state_display . '</div>',
    ];

    $form['info']['details'] = [
      '#type' => 'table',
      '#header' => [],
      '#rows' => [
        [
          ['data' => $this->t('Name'), 'header' => TRUE],
          $endpoint->get('name') ?: '-',
        ],
        [
          ['data' => $this->t('Namespace'), 'header' => TRUE],
          $endpoint->get('namespace') ?: '-',
        ],
        [
          ['data' => $this->t('Model'), 'header' => TRUE],
          $endpoint->get('model') ?: '-',
        ],
        [
          ['data' => $this->t('Task'), 'header' => TRUE],
          $endpoint->get('task') ?: '-',
        ],
        [
          ['data' => $this->t('Min/Max Replicas'), 'header' => TRUE],
          ($endpoint->get('minReplica') ?? '0') . ' / ' . ($endpoint->get('maxReplica') ?? '1'),
        ],
        [
          ['data' => $this->t('URL'), 'header' => TRUE],
          $endpoint->get('url') ? ['data' => ['#markup' => '<a href="' . $endpoint->get('url') . '" target="_blank">' . $endpoint->get('url') . '</a>']] : $this->t('Not available (endpoint may be paused)'),
        ],
      ],
    ];

    // Actions section.
    $form['lifecycle'] = [
      '#type' => 'details',
      '#title' => $this->t('Lifecycle Management'),
      '#open' => TRUE,
    ];

    $form['lifecycle']['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Manage the operational state of your HuggingFace Inference Endpoint. Changes are applied directly to the HuggingFace API.') . '</p>',
    ];

    $form['lifecycle']['actions'] = [
      '#type' => 'actions',
    ];

    $form['lifecycle']['actions']['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh Status'),
      '#submit' => ['::refreshStatus'],
      '#attributes' => ['title' => $this->t('Fetch the latest status from HuggingFace API')],
    ];

    // Show appropriate action buttons based on current state.
    if ($state === 'running') {
      $form['lifecycle']['actions']['scale_to_zero'] = [
        '#type' => 'submit',
        '#value' => $this->t('Scale to Zero'),
        '#submit' => ['::scaleToZero'],
        '#attributes' => ['title' => $this->t('Scale down to zero replicas. Endpoint will auto-start on next request (cold start delay).')],
      ];

      $form['lifecycle']['actions']['pause'] = [
        '#type' => 'submit',
        '#value' => $this->t('Pause Endpoint'),
        '#submit' => ['::pauseEndpoint'],
        '#attributes' => [
          'class' => ['button--danger'],
          'title' => $this->t('Fully pause the endpoint. No charges while paused. Requires manual resume.'),
        ],
      ];

      $form['lifecycle']['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="description" style="margin-top: 15px;"><strong>' . $this->t('Tip:') . '</strong> ' . $this->t('Use "Scale to Zero" to save costs while keeping the endpoint ready for auto-start. Use "Pause" for longer periods when you don\'t need the endpoint.') . '</div>',
      ];
    }
    elseif ($state === 'scaledtozero' || $state === 'scaled_to_zero') {
      $form['lifecycle']['actions']['pause'] = [
        '#type' => 'submit',
        '#value' => $this->t('Pause Endpoint'),
        '#submit' => ['::pauseEndpoint'],
        '#attributes' => [
          'class' => ['button--danger'],
          'title' => $this->t('Fully pause the endpoint instead of scale-to-zero.'),
        ],
      ];

      $form['lifecycle']['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="description" style="margin-top: 15px;">' . $this->t('The endpoint is scaled to zero and will automatically start when it receives a request. There may be a cold start delay of 30-60 seconds.') . '</div>',
      ];
    }
    elseif ($state === 'paused') {
      $form['lifecycle']['actions']['resume'] = [
        '#type' => 'submit',
        '#value' => $this->t('Resume Endpoint'),
        '#submit' => ['::resumeEndpoint'],
        '#button_type' => 'primary',
        '#attributes' => ['title' => $this->t('Start the endpoint. This may take a few minutes.')],
      ];

      $form['lifecycle']['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="description" style="margin-top: 15px;">' . $this->t('The endpoint is paused and not incurring charges. Click "Resume Endpoint" to start it. Startup typically takes 2-5 minutes.') . '</div>',
      ];
    }
    elseif (in_array($state, ['initializing', 'pending', 'updating'])) {
      $form['lifecycle']['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--status" style="margin-top: 15px;">' . $this->t('The endpoint is currently starting up or updating. Please wait and refresh the status.') . '</div>',
      ];
    }
    elseif ($state === 'failed') {
      $form['lifecycle']['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--error" style="margin-top: 15px;">' . $this->t('The endpoint has failed. Check the HuggingFace dashboard for error details. You may need to recreate the endpoint.') . '</div>',
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
