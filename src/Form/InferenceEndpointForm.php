<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huggingface\HuggingFaceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Inference Endpoint form.
 *
 * @property \Drupal\huggingface\InferenceEndpointInterface $entity
 */
class InferenceEndpointForm extends EntityForm {

  /**
   * The HuggingFace service.
   *
   * @var \Drupal\huggingface\HuggingFaceServiceInterface
   */
  protected HuggingFaceServiceInterface $huggingFaceService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->huggingFaceService = $container->get('huggingface');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Basic Information.
    $form['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
    ];

    $form['basic']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('A human-readable name for this endpoint configuration (e.g., "Production Text Generation").'),
      '#required' => TRUE,
    ];

    $form['basic']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\huggingface\Entity\InferenceEndpoint::load',
        'source' => ['basic', 'label'],
      ],
      '#disabled' => !$this->entity->isNew(),
      '#description' => $this->t('A unique machine name. Can only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['basic']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Optional notes about this endpoint, such as its purpose or usage instructions.'),
    ];

    $form['basic']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
      '#description' => $this->t('When enabled, this endpoint can be used for inference requests.'),
    ];

    // Show create on HuggingFace option only for new entities.
    if ($this->entity->isNew()) {
      $form['basic']['create_on_huggingface'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create endpoint on HuggingFace'),
        '#default_value' => TRUE,
        '#description' => $this->t('When checked, a new Inference Endpoint will be created on HuggingFace. Uncheck to only create a local configuration for an existing endpoint.'),
      ];
    }

    // HuggingFace Connection.
    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('HuggingFace Connection'),
      '#open' => TRUE,
    ];

    $form['connection']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token (Optional)'),
      '#default_value' => $this->entity->get('accessToken'),
      '#description' => $this->t('Override the global access token for this endpoint. <strong>Leave empty to use the token from <a href="/admin/config/services/huggingface/settings">global settings</a></strong>. Only set this if this endpoint requires a different token (e.g., different HuggingFace account).'),
      '#maxlength' => 255,
      '#placeholder' => $this->t('Leave empty to use global token'),
    ];

    $form['connection']['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace'),
      '#default_value' => $this->entity->get('namespace'),
      '#description' => $this->t('Your HuggingFace username or organization name where the endpoint is deployed.'),
      '#required' => TRUE,
    ];

    $form['connection']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint Name'),
      '#default_value' => $this->entity->get('name'),
      '#description' => $this->t('The unique name of your inference endpoint on HuggingFace (as shown in the Inference Endpoints dashboard).'),
      '#required' => TRUE,
    ];

    // Endpoint Configuration.
    $form['endpoint'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint Configuration'),
      '#open' => TRUE,
    ];

    $form['endpoint']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Endpoint Type'),
      '#default_value' => $this->entity->get('type') ?: 'protected',
      '#options' => [
        'public' => $this->t('Public - Anyone can access'),
        'protected' => $this->t('Protected - Requires authentication'),
        'private' => $this->t('Private - VPC access only'),
      ],
      '#description' => $this->t('The security level of the endpoint. "Protected" is recommended for most use cases.'),
    ];

    $form['endpoint']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $this->entity->get('url'),
      '#description' => $this->t('The inference URL provided by HuggingFace (e.g., https://xxxxx.us-east-1.aws.endpoints.huggingface.cloud). This is auto-populated when syncing.'),
      '#maxlength' => 512,
    ];

    // Model Configuration.
    $form['model_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Model Configuration'),
      '#open' => FALSE,
    ];

    $form['model_config']['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model Repository'),
      '#default_value' => $this->entity->get('model'),
      '#description' => $this->t('The HuggingFace model repository (e.g., "meta-llama/Llama-2-7b-chat-hf", "gpt2", "facebook/bart-large-cnn").'),
    ];

    $form['model_config']['framework'] = [
      '#type' => 'select',
      '#title' => $this->t('Framework'),
      '#default_value' => $this->entity->get('framework') ?: '',
      '#options' => [
        '' => $this->t('- Auto-detect -'),
        'pytorch' => $this->t('PyTorch'),
        'tensorflow' => $this->t('TensorFlow'),
        'custom' => $this->t('Custom (TGI, vLLM, etc.)'),
      ],
      '#description' => $this->t('The ML framework used by the model. Leave as auto-detect unless you need a specific framework.'),
    ];

    $form['model_config']['task'] = [
      '#type' => 'select',
      '#title' => $this->t('Task'),
      '#default_value' => $this->entity->get('task') ?: '',
      '#options' => [
        '' => $this->t('- Auto-detect -'),
        'text-generation' => $this->t('Text Generation'),
        'text-classification' => $this->t('Text Classification'),
        'token-classification' => $this->t('Token Classification (NER)'),
        'question-answering' => $this->t('Question Answering'),
        'summarization' => $this->t('Summarization'),
        'translation' => $this->t('Translation'),
        'fill-mask' => $this->t('Fill Mask'),
        'feature-extraction' => $this->t('Feature Extraction (Embeddings)'),
        'sentence-similarity' => $this->t('Sentence Similarity'),
        'zero-shot-classification' => $this->t('Zero-Shot Classification'),
        'image-classification' => $this->t('Image Classification'),
        'object-detection' => $this->t('Object Detection'),
        'image-segmentation' => $this->t('Image Segmentation'),
        'text-to-image' => $this->t('Text to Image'),
        'automatic-speech-recognition' => $this->t('Speech Recognition'),
        'audio-classification' => $this->t('Audio Classification'),
      ],
      '#description' => $this->t('The ML task this endpoint performs. Auto-detect uses the model\'s default task.'),
    ];

    $form['model_config']['revision'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model Revision'),
      '#default_value' => $this->entity->get('revision'),
      '#description' => $this->t('Optional: A specific model revision/commit hash. Leave empty to use the latest version.'),
    ];

    // Compute & Scaling.
    $is_new = $this->entity->isNew();

    $form['compute'] = [
      '#type' => 'details',
      '#title' => $this->t('Compute & Scaling'),
      '#description' => $is_new
        ? $this->t('Configure the compute resources for your endpoint. These settings determine cost and performance.')
        : $this->t('These settings are managed via the HuggingFace dashboard or API. Shown here for reference.'),
      '#open' => $is_new,
    ];

    if (!$is_new) {
      $form['compute']['state'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Current State'),
        '#default_value' => $this->entity->get('state'),
        '#description' => $this->t('The current operational state: running, paused, scaledToZero, pending, failed, etc.'),
        '#attributes' => ['readonly' => 'readonly'],
      ];
    }

    $form['compute']['accelerator'] = [
      '#type' => 'select',
      '#title' => $this->t('Accelerator'),
      '#default_value' => $this->entity->get('accelerator') ?: 'cpu',
      '#options' => [
        'cpu' => $this->t('CPU'),
        'gpu' => $this->t('GPU'),
      ],
      '#description' => $this->t('Hardware type. GPU provides faster inference but costs more.'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['instance_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Instance Size'),
      '#default_value' => $this->entity->get('instanceSize') ?: 'x1',
      '#options' => [
        'x1' => $this->t('x1 (Small)'),
        'x2' => $this->t('x2 (Medium)'),
        'x4' => $this->t('x4 (Large)'),
        'x8' => $this->t('x8 (Extra Large)'),
      ],
      '#description' => $this->t('Instance size determines memory and compute power.'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['instance_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Instance Type'),
      '#default_value' => $this->entity->get('instanceType') ?: 'intel-icl',
      '#options' => [
        'intel-icl' => $this->t('Intel Ice Lake (CPU)'),
        'intel-sapphire-rapids' => $this->t('Intel Sapphire Rapids (CPU)'),
        'nvidia-a10g' => $this->t('NVIDIA A10G (GPU)'),
        'nvidia-t4' => $this->t('NVIDIA T4 (GPU)'),
        'nvidia-l4' => $this->t('NVIDIA L4 (GPU)'),
        'nvidia-a100' => $this->t('NVIDIA A100 (GPU)'),
      ],
      '#description' => $this->t('Cloud instance type. Choose based on your model requirements.'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['vendor'] = [
      '#type' => 'select',
      '#title' => $this->t('Cloud Vendor'),
      '#default_value' => $this->entity->get('vendor') ?: 'aws',
      '#options' => [
        'aws' => $this->t('Amazon Web Services (AWS)'),
        'azure' => $this->t('Microsoft Azure'),
        'gcp' => $this->t('Google Cloud Platform (GCP)'),
      ],
      '#description' => $this->t('Cloud provider where the endpoint will be deployed.'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#default_value' => $this->entity->get('region') ?: 'us-east-1',
      '#options' => [
        'us-east-1' => $this->t('US East (N. Virginia)'),
        'us-west-2' => $this->t('US West (Oregon)'),
        'eu-west-1' => $this->t('EU (Ireland)'),
        'eu-west-2' => $this->t('EU (London)'),
        'ap-southeast-1' => $this->t('Asia Pacific (Singapore)'),
      ],
      '#description' => $this->t('Cloud region. Choose a region close to your users for lower latency.'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['min_replica'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Replicas'),
      '#default_value' => $this->entity->get('minReplica') ?? 0,
      '#min' => 0,
      '#max' => 10,
      '#description' => $this->t('Minimum number of replicas. Set to 0 for scale-to-zero (saves costs when idle).'),
      '#disabled' => !$is_new,
    ];

    $form['compute']['max_replica'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Replicas'),
      '#default_value' => $this->entity->get('maxReplica') ?? 1,
      '#min' => 1,
      '#max' => 10,
      '#description' => $this->t('Maximum number of replicas for auto-scaling under load.'),
      '#disabled' => !$is_new,
    ];

    // Advanced/Internal.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    ];

    $form['advanced']['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $this->entity->get('accountId'),
      '#description' => $this->t('HuggingFace account ID (auto-populated during sync).'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity($entity, array $form, FormStateInterface $form_state) {
    // Map form values to entity properties, handling nested form elements.
    $values = $form_state->getValues();

    // Basic fields.
    $entity->set('label', $values['label'] ?? '');
    $entity->set('description', $values['description'] ?? '');
    $entity->set('status', !empty($values['status']));

    // Connection fields.
    $entity->set('accessToken', $values['access_token'] ?? '');
    $entity->set('namespace', $values['namespace'] ?? '');
    $entity->set('name', $values['name'] ?? '');

    // Endpoint configuration.
    $entity->set('type', $values['type'] ?? 'protected');
    $entity->set('url', $values['url'] ?? '');

    // Model configuration.
    $entity->set('model', $values['model'] ?? '');
    $entity->set('framework', $values['framework'] ?? '');
    $entity->set('task', $values['task'] ?? '');
    $entity->set('revision', $values['revision'] ?? '');

    // Compute configuration.
    $entity->set('accelerator', $values['accelerator'] ?? 'cpu');
    $entity->set('instanceSize', $values['instance_size'] ?? 'x1');
    $entity->set('instanceType', $values['instance_type'] ?? 'intel-icl');
    $entity->set('vendor', $values['vendor'] ?? 'aws');
    $entity->set('region', $values['region'] ?? 'us-east-1');
    $entity->set('minReplica', (int) ($values['min_replica'] ?? 0));
    $entity->set('maxReplica', (int) ($values['max_replica'] ?? 1));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $is_new = $this->entity->isNew();
    $create_on_huggingface = $form_state->getValue('create_on_huggingface');

    // Validate required fields for HuggingFace API creation.
    if ($is_new && $create_on_huggingface) {
      $access_token = $form_state->getValue('access_token') ?: $this->huggingFaceService->getAccessToken();

      if (empty($access_token)) {
        $form_state->setErrorByName('access_token', $this->t('Access token is required. Configure it in <a href="/admin/config/services/huggingface/settings">global settings</a> or provide one for this endpoint.'));
      }

      if (empty($form_state->getValue('model'))) {
        $form_state->setErrorByName('model', $this->t('Model repository is required when creating an endpoint on HuggingFace.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $is_new = $this->entity->isNew();
    $create_on_huggingface = $form_state->getValue('create_on_huggingface');

    // If creating a new endpoint on HuggingFace, call the API first.
    if ($is_new && $create_on_huggingface) {
      $access_token = $this->entity->get('accessToken') ?: $this->huggingFaceService->getAccessToken();
      $namespace = $this->entity->get('namespace');
      $name = $this->entity->get('name');

      // Build the endpoint configuration for the API.
      $endpoint_data = [
        'namespace' => $namespace,
        'name' => $name,
        'type' => $this->entity->get('type') ?: 'protected',
        'repository' => $this->entity->get('model'),
        'framework' => $this->entity->get('framework') ?: 'pytorch',
        'task' => $this->entity->get('task') ?: 'text-generation',
        'accelerator' => $this->entity->get('accelerator') ?: 'cpu',
        'instance_size' => $this->entity->get('instanceSize') ?: 'x1',
        'instance_type' => $this->entity->get('instanceType') ?: 'intel-icl',
        'vendor' => $this->entity->get('vendor') ?: 'aws',
        'region' => $this->entity->get('region') ?: 'us-east-1',
        'min_replica' => (int) ($this->entity->get('minReplica') ?? 0),
        'max_replica' => (int) ($this->entity->get('maxReplica') ?? 1),
      ];

      if ($revision = $this->entity->get('revision')) {
        $endpoint_data['revision'] = $revision;
      }

      try {
        // Create the endpoint on HuggingFace.
        $result = $this->huggingFaceService->createInferenceEndpoint($endpoint_data, [
          'access_token' => $access_token,
        ]);

        // Update entity with response data.
        if (isset($result->status)) {
          $this->entity->set('state', $result->status->state ?? 'pending');
          $this->entity->set('url', $result->status->url ?? '');
        }
        if (isset($result->accountId)) {
          $this->entity->set('accountId', $result->accountId);
        }

        $this->messenger()->addStatus($this->t('Inference endpoint created on HuggingFace. It may take a few minutes to initialize.'));
      }
      catch (\Exception $e) {
        // Show error and rebuild form to preserve values.
        $this->messenger()->addError($this->t('Failed to create endpoint on HuggingFace: @error', [
          '@error' => $e->getMessage(),
        ]));
        $form_state->setRebuild();
        return FALSE;
      }
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];

    if (!$is_new) {
      $this->messenger()->addStatus($this->t('Updated inference endpoint %label.', $message_args));
    }
    elseif (!$create_on_huggingface) {
      $this->messenger()->addStatus($this->t('Created local configuration for inference endpoint %label.', $message_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
