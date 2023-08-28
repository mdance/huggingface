<?php

namespace Drupal\huggingface\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @param ConfigFactoryInterface $configFactory
   * @param HuggingFaceServiceInterface $service
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    protected HuggingFaceServiceInterface $service,
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $this->service->getAccessToken(),
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->service->getUrl(),
    ];

    $form['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Logging'),
      '#default_value' => $this->service->getLogging(),
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
