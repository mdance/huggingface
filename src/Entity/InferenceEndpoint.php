<?php

namespace Drupal\huggingface\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\huggingface\InferenceEndpointInterface;

/**
 * Defines the inference endpoint entity type.
 *
 * @ConfigEntityType(
 *   id = "inference_endpoint",
 *   label = @Translation("Inference Endpoint"),
 *   label_collection = @Translation("Inference Endpoints"),
 *   label_singular = @Translation("inference endpoint"),
 *   label_plural = @Translation("inference endpoints"),
 *   label_count = @PluralTranslation(
 *     singular = "@count inference endpoint",
 *     plural = "@count inference endpoints",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\huggingface\InferenceEndpointListBuilder",
 *     "form" = {
 *       "add" = "Drupal\huggingface\Form\InferenceEndpointForm",
 *       "edit" = "Drupal\huggingface\Form\InferenceEndpointForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "info" = "Drupal\huggingface\Form\InferenceEndpointInfoForm",
 *       "logs" = "Drupal\huggingface\Form\InferenceEndpointLogsForm",
 *       "metrics" = "Drupal\huggingface\Form\InferenceEndpointMetricsForm",
 *       "sync" = "Drupal\huggingface\Form\InferenceEndpointsSyncForm"
 *     }
 *   },
 *   config_prefix = "inference_endpoint",
 *   admin_permission = "administer inference_endpoint",
 *   links = {
 *     "collection" = "/admin/config/services/huggingface/inference-endpoint",
 *     "add-form" = "/admin/config/services/huggingface/inference-endpoint/add",
 *     "edit-form" = "/admin/config/services/huggingface/inference-endpoints/{inference_endpoint}",
 *     "delete-form" = "/admin/config/services/huggingface/inference-endpoints/{inference_endpoint}/delete",
 *     "sync-form" = "/admin/config/services/huggingface/inference-endpoints/sync"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "status",
 *     "accessToken",
 *     "namespace",
 *     "type",
 *     "name",
 *     "accountId",
 *     "model",
 *     "state",
 *     "url"
 *   }
 * )
 */
class InferenceEndpoint extends ConfigEntityBase implements InferenceEndpointInterface {

  /**
   * The inference endpoint ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The inference endpoint label.
   *
   * @var string
   */
  protected $label;

  /**
   * The inference endpoint status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The inference_endpoint description.
   *
   * @var string
   */
  protected $description;

  /**
   * Provides the access token.
   */
  protected string $accessToken;

  /**
   * Provides the namespace.
   */
  protected string $namespace;

  /**
   * Provides the endpoint type.
   */
  protected string $type;

  /**
   * Provides the endpoint name.
   */
  protected string $name;

  /**
   * Provides the model.
   */
  protected string $model;

  /**
   * Provides the state.
   */
  protected string $state;

  /**
   * Provides the URL.
   */
  protected ?string $url;

  /**
   * Provides the account ID.
   */
  protected ?string $accountId;

}
