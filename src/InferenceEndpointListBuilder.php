<?php

namespace Drupal\huggingface;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of inference endpoints.
 */
class InferenceEndpointListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['namespace'] = $this->t('Namespace');
    $header['model'] = $this->t('Model');
    $header['state'] = $this->t('State');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\huggingface\Entity\InferenceEndpoint $entity */
    $row['label'] = $entity->label();
    $row['namespace'] = $entity->get('namespace') ?? '';
    $row['model'] = $entity->get('model') ?? '';
    $row['state'] = $entity->get('state') ?? '';
    $row['status'] = $entity->status() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['actions'] = [
      'title' => $this->t('Manage'),
      'weight' => 5,
      'url' => Url::fromRoute('entity.inference_endpoint.actions', [
        'inference_endpoint' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
