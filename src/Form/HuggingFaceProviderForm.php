<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Hugging Face AI provider settings.
 */
class HuggingFaceProviderForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'huggingface.settings';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId() {
    return 'huggingface_provider_settings';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Hugging Face API Key'),
      '#description' => $this->t('Select the Key entity containing your Hugging Face access token. Manage keys at <a href=":url">Key configuration</a>.', [
        ':url' => '/admin/config/system/keys',
      ]),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
