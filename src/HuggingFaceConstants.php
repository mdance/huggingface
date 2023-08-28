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
   * Provides the path.
   */
  const PATH = '';

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
