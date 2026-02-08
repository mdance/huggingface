<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huggingface\HuggingFaceConstants;
use Drupal\huggingface\HuggingFaceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the HuggingFaceAdminForm class.
 */
class HuggingFaceAdminForm extends ConfigFormBase {

  /**
   * Provides the constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\huggingface\HuggingFaceServiceInterface $service
   *   The HuggingFace service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    TypedConfigManagerInterface $typedConfigManager,
    protected HuggingFaceServiceInterface $service,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('huggingface'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'huggingface_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [HuggingFaceConstants::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Configure global settings for HuggingFace integration. These settings are used as defaults throughout the module.') . '</p>',
    ];

    $form['authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication'),
      '#open' => TRUE,
    ];

    $form['authentication']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $this->service->getAccessToken(),
      '#description' => $this->t('Your HuggingFace API access token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co/settings/tokens</a>. This token is used as the default for all HuggingFace API requests unless overridden per-endpoint.'),
      '#maxlength' => 255,
    ];

    $form['hosted_inference'] = [
      '#type' => 'details',
      '#title' => $this->t('Hosted Inference API'),
      '#description' => $this->t('Settings for the free HuggingFace Hosted Inference API. This is different from dedicated Inference Endpoints.'),
      '#open' => TRUE,
    ];

    $form['hosted_inference']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Inference API URL'),
      '#default_value' => $this->service->getUrl(),
      '#description' => $this->t('The URL for the Hosted Inference API. Typically <code>https://api-inference.huggingface.co/models/MODEL_NAME</code>. Used by the Test Endpoint form for running inference on public models. Leave empty if you only use dedicated Inference Endpoints.'),
      '#placeholder' => 'https://api-inference.huggingface.co/models/gpt2',
      '#maxlength' => 512,
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    ];

    $form['advanced']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable API Logging'),
      '#default_value' => $this->service->getLogging(),
      '#description' => $this->t('When enabled, all API responses are logged to the database for debugging purposes. Disable in production to reduce database size.'),
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $keys = [
      'access_token',
    ];

    foreach ($keys as $key) {
      if (empty($values[$key])) {
        unset($values[$key]);
      }
    }

    $this->service->saveConfiguration($values);

    parent::submitForm($form, $form_state);
  }

}
