<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Inference Endpoint form.
 *
 * @property \Drupal\huggingface\InferenceEndpointInterface $entity
 */
class InferenceEndpointForm extends EntityForm {

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

    // HuggingFace Connection.
    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('HuggingFace Connection'),
      '#open' => TRUE,
    ];

    $form['connection']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $this->entity->get('accessToken'),
      '#description' => $this->t('Your HuggingFace API access token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co/settings/tokens</a>. Required for authentication with the Inference Endpoints API.'),
      '#maxlength' => 255,
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

    // Compute & Scaling (read-only info).
    $form['compute'] = [
      '#type' => 'details',
      '#title' => $this->t('Compute & Scaling'),
      '#description' => $this->t('These settings are managed via the HuggingFace dashboard or API. Shown here for reference.'),
      '#open' => FALSE,
    ];

    $form['compute']['state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current State'),
      '#default_value' => $this->entity->get('state'),
      '#description' => $this->t('The current operational state: running, paused, scaledToZero, pending, failed, etc.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['accelerator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accelerator'),
      '#default_value' => $this->entity->get('accelerator'),
      '#description' => $this->t('Hardware type: cpu, gpu, etc.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['instance_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance Size'),
      '#default_value' => $this->entity->get('instanceSize'),
      '#description' => $this->t('Instance size: x1, x2, x4, etc.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['instance_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance Type'),
      '#default_value' => $this->entity->get('instanceType'),
      '#description' => $this->t('Cloud instance type: intel-icl, nvidia-a10g, etc.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#default_value' => $this->entity->get('region'),
      '#description' => $this->t('Cloud region: us-east-1, eu-west-1, etc.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['vendor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cloud Vendor'),
      '#default_value' => $this->entity->get('vendor'),
      '#description' => $this->t('Cloud provider: aws, azure, gcp.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['min_replica'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Replicas'),
      '#default_value' => $this->entity->get('minReplica') ?? 0,
      '#description' => $this->t('Minimum number of replicas. Set to 0 for scale-to-zero.'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['compute']['max_replica'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Replicas'),
      '#default_value' => $this->entity->get('maxReplica') ?? 1,
      '#description' => $this->t('Maximum number of replicas for auto-scaling.'),
      '#attributes' => ['readonly' => 'readonly'],
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
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new inference endpoint %label.', $message_args)
      : $this->t('Updated inference endpoint %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
