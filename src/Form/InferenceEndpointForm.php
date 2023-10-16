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

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Provides the label.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\huggingface\Entity\InferenceEndpoint::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Provides the description.'),
    ];

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $this->entity->get('accessToken'),
      '#description' => $this->t('Provides the namespace.'),
    ];

    $form['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace'),
      '#default_value' => $this->entity->get('namespace'),
      '#description' => $this->t('Provides the namespace.'),
    ];

    $form['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint Type'),
      '#default_value' => $this->entity->get('type'),
      '#description' => $this->t('Provides the type.'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint Name'),
      '#default_value' => $this->entity->get('name'),
      '#description' => $this->t('Provides the name.'),
    ];

    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model'),
      '#default_value' => $this->entity->get('model'),
      '#description' => $this->t('Provides the model.'),
    ];

    $form['state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#default_value' => $this->entity->get('state'),
      '#description' => $this->t('Provides the state.'),
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->entity->get('url'),
      '#description' => $this->t('Provides the URL.'),
    ];

    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $this->entity->get('accountId'),
      '#description' => $this->t('Provides the account ID.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
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
