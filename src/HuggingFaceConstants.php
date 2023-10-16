<?php

namespace Drupal\huggingface;

/**
 * Provides the Hugging Face constants.
 */
class HuggingFaceConstants {

  /**
   * Provides the source.
   */
  const SOURCE = 'huggingface';

  /**
   * Provides the settings key.
   */
  const SETTINGS = 'huggingface.settings';

  /**
   * Provides the schema.
   */
  public const SCHEMA = 'https';

  /**
   * Provides the host.
   */
  public const HOST = 'api.endpoints.huggingface.cloud';

  /**
   * Provides the inference api host.
   */
  public const HOST_INFERENCE = 'api-inference.huggingface.co';

  /**
   * Provides the path.
   */
  const PATH = '';

  /**
   * Provides the version 2 path.
   */
  public const PATH_V2 = '/v2';

  /**
   * Provides the endpoints path.
   */
  public const PATH_ENDPOINTS = self:: PATH_V2 . '/endpoint';

  /**
   * Provides the models path.
   */
  public const PATH_MODELS = '/models';

  /**
   * Provides the module directory.
   */
  const DIR = 'private://huggingface';

  /**
   * Provides the responses table.
   */
  const TABLE_RESPONSES = 'huggingface_responses';

  /**
   * Provides the simple aggregation strategy.
   */
  const AGGREGATION_STRATEGY_SIMPLE = 'simple';

  /**
   * Provides the mask token.
   */
  const TOKEN_MASK = '[MASK]';

}
