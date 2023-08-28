<?php

namespace Drupal\huggingface;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

/**
 * Provides the HuggingFaceService class.
 */
class HuggingFaceService implements HuggingFaceServiceInterface {

  use StringTranslationTrait;

  /**
   * Provides the config.
   */
  protected Config $config;

  /**
   * Provides the client.
   */
  protected ClientInterface $client;

  /**
   * Provides the cookie jar.
   */
  protected CookieJar $cookieJar;

  /**
   * Provides the constructor method.
   */
  public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected StateInterface $state,
        protected ClientFactory $clientFactory,
        protected FileSystemInterface $fileSystem,
        protected FileRepositoryInterface $fileRepository,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $connection,
        protected CacheBackendInterface $cacheBackend,
        protected ModuleHandlerInterface $moduleHandler,
    ) {
    $this->config = $configFactory->get(HuggingFaceConstants::SETTINGS);

    $cookie_jar = new CookieJar();

    $config = [
      'cookies' => $cookie_jar,
    ];

    $this->cookieJar = $cookie_jar;

    $this->client = $clientFactory->fromOptions($config);
  }

  /**
   * {@inheritDoc}
   */
  public function getAccessToken() {
    return $this->getConfiguration()['access_token'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl() {
    return $this->getConfiguration()['url'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function getLogging() {
    return $this->getConfiguration()['logging'] ?? TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function saveConfiguration(array $input) {
    $keys = [
      'access_token',
    ];

    $state = $this->state->get(HuggingFaceConstants::SETTINGS, []);

    foreach ($keys as $key) {
      if (isset($input[$key])) {
        $state[$key] = $input[$key];
        unset($input[$key]);
      }
    }

    $config = $this->configFactory->getEditable(HuggingFaceConstants::SETTINGS);

    foreach ($input as $key => $value) {
      $config->set($key, $value);
    }

    $this->state->set(HuggingFaceConstants::SETTINGS, $state);
    $config->save();

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration() {
    $output = $this->config->get();
    $state = $this->state->get(HuggingFaceConstants::SETTINGS, []);

    $output = array_merge($output, $state);

    return $output;
  }

  /**
   * {@inheritDoc}
   */
  public function addResponse($type, $data) {
    $query = $this->connection->insert(HuggingFaceConstants::TABLE_RESPONSES);

    $fields = [];

    $fields['type'] = $type;
    $fields['created'] = time();
    $fields['data'] = $data;

    $query->fields($fields);

    $query->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function cron() {
    try {
    }
    catch (\Exception $e) {
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskOptions(): array {
    $output = [];

    $output['text_classification'] = $this->t('Text Classification');
    $output['zero_shot_classification'] = $this->t('Zero Shot Classification');
    $output['token_classification'] = $this->t('Token Classification');
    $output['question_answering'] = $this->t('Question Answering');
    $output['fill_mask'] = $this->t('Fill Mask');
    $output['summarization'] = $this->t('Summarization');
    $output['translation'] = $this->t('Translation');
    $output['text_to_text_generation'] = $this->t('Text To Text Generation');
    $output['text_generation'] = $this->t('Text Generation');
    $output['feature_extraction'] = $this->t('Feature Extraction');
    $output['sentence_embeddings'] = $this->t('Sentence Embeddings');
    $output['sentence_similarity'] = $this->t('Sentence Similarity');
    $output['ranking'] = $this->t('Ranking');
    $output['image_classification'] = $this->t('Image Classification');
    $output['automatic_speech_recognition'] = $this->t('Automatic Speech Recognition');
    $output['audio_classification'] = $this->t('Audio Classification');
    $output['object_detection'] = $this->t('Object Detection');
    $output['image_segmentation'] = $this->t('Image Segmentation');
    $output['table_question_answering'] = $this->t('Table Question Answering');
    $output['conversational'] = $this->t('Conversational');
    $output['text_to_image'] = $this->t('Text To Image');

    $output['custom'] = $this->t('Custom');

    return $output;
  }

  /**
   * Performs a task.
   *
   * @param string $task
   *   The task name.
   * @param array $parameters
   *   An array of parameters.
   *
   * @return object
   *   The response object.
   */
  public function performTask(string $task, array $parameters = []) {
    try {
      switch ($task) {
        case 'text_classification':
          return $this->textClassification($parameters);

        case 'zero_shot_classification':
          return $this->zeroShotClassification($parameters);

        case 'token_classification':
          return $this->tokenClassification($parameters);

        case 'question_answering':
          return $this->questionAnswering($parameters);

        case 'fill_mask':
          return $this->fillMask($parameters);

        case 'summarization':
          return $this->summarization($parameters);

        case 'feature_extraction':
          return $this->featureExtraction($parameters);

        case 'sentence_embeddings':
          return $this->sentenceEmbeddings($parameters);

        case 'sentence_similarity':
          return $this->sentenceSimilarity($parameters);

        case 'ranking':
          return $this->ranking($parameters);

        case 'table_question_answering':
          return $this->tableQuestionAnswering($parameters);

        case 'conversational':
          return $this->conversational($parameters);

        case 'text_to_image':
          return $this->textToImage($parameters);

        case 'image_classification':
          return $this->imageClassification($parameters);

        case 'automatic_speech_recognition':
          return $this->automaticSpeechRecognition($parameters);

        case 'audio_classification':
          return $this->audioClassification($parameters);

        case 'object_detection':
          return $this->objectDetection($parameters);

        case 'image_segmentation':
          return $this->imageSegmentation($parameters);
      }
    }
    catch (\Exception $e) {
      // @todo {"error":"Expected all tensors to be on the same device, but found at least two devices, cuda:0 and cuda:1! (when checking argument for argument index in method wrapper__index_select)"}
      if ($this->getLogging()) {
        watchdog_exception('huggingface', $e);
      }

      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getHeaders() {
    $output = [];

    $token = $this->getAccessToken();

    $output['content-type'] = 'application/json';
    $output['authorization'] = 'Bearer ' . $token;

    return $output;
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function textClassification(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
      'parameters' => [
        'top_k' => NULL,
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the text classification request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('text_classification', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function zeroShotClassification(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
      'parameters' => [
        'candidate_labels' => [],
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    if (is_array($parameters['parameters']['candidate_labels'])) {
      $parameters['parameters']['candidate_labels'] = implode(', ', $parameters['parameters']['candidate_labels']);
    }

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the zero shot classification request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('zero_shot_classification', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   */
  public function getAggregationStrategies() {
    $output = [];

    $output[HuggingFaceConstants::AGGREGATION_STRATEGY_SIMPLE] = $this->t('Simple');

    return $output;
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function tokenClassification(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
      'parameters' => [
        'aggregation_strategy' => HuggingFaceConstants::AGGREGATION_STRATEGY_SIMPLE,
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the token classification request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('token_classification', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function questionAnswering(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [
        'question' => '',
        'context' => '',
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the question answering request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('question_answering', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function fillMask(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the fill mask request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('fill_mask', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function summarization(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the summarization request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('summarization', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function featureExtraction(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the feature extraction request');
    }

    $body = (string) $response->getBody();
    // $this->addResponse('feature_extraction', $body);
    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function sentenceEmbeddings(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the sentence embeddings request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('sentence_embeddings', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function sentenceSimilarity(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [
        'source_sentence' => '',
        'sentences' => [],
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the sentence similarity request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('sentence_similarity', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function ranking(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the ranking request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('ranking', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function imageClassification(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [],
    ];

    $parameters = array_merge($defaults, $parameters);

    $inputs = $parameters['inputs'];

    $storage = $this->entityTypeManager->getStorage('file');

    $filename = '';

    foreach ($inputs as $input) {
      if (is_numeric($input)) {
        $file = $storage->load($input);

        $options[RequestOptions::HEADERS]['content-type'] = $file->getMimeType();
        $filename = $file->getFileUri();

        break;
      }
    }

    if (empty($filename)) {
      throw new HuggingFaceException('An error occurred performing the image classification request');
    }

    $options[RequestOptions::BODY] = fopen($filename, 'r');

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the image classification request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('image_classification', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function automaticSpeechRecognition(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [],
    ];

    $parameters = array_merge($defaults, $parameters);

    $inputs = $parameters['inputs'];

    $storage = $this->entityTypeManager->getStorage('file');

    $filename = '';

    foreach ($inputs as $input) {
      if (is_numeric($input)) {
        $file = $storage->load($input);

        $options[RequestOptions::HEADERS]['content-type'] = $file->getMimeType();
        $filename = $file->getFileUri();

        break;
      }
    }

    if (empty($filename)) {
      throw new HuggingFaceException('An error occurred performing the automatic speech recognition request');
    }

    $options['connect_timeout'] = 0;
    $options[RequestOptions::BODY] = fopen($filename, 'r');

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the automatic speech recognition request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('automatic_speech_recognition', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function audioClassification(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [],
    ];

    $parameters = array_merge($defaults, $parameters);

    $inputs = $parameters['inputs'];

    $storage = $this->entityTypeManager->getStorage('file');

    $filename = '';

    foreach ($inputs as $input) {
      if (is_numeric($input)) {
        $file = $storage->load($input);

        $options[RequestOptions::HEADERS]['content-type'] = $file->getMimeType();
        $filename = $file->getFileUri();

        break;
      }
    }

    if (empty($filename)) {
      throw new HuggingFaceException('An error occurred performing the audio classification request');
    }

    $options['connect_timeout'] = 0;
    $options[RequestOptions::BODY] = fopen($filename, 'r');

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the audio classification request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('audio_classification', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function objectDetection(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [],
    ];

    $parameters = array_merge($defaults, $parameters);

    $inputs = $parameters['inputs'];

    $storage = $this->entityTypeManager->getStorage('file');

    $filename = '';

    foreach ($inputs as $input) {
      if (is_numeric($input)) {
        $file = $storage->load($input);

        $options[RequestOptions::HEADERS]['content-type'] = $file->getMimeType();
        $filename = $file->getFileUri();

        break;
      }
    }

    if (empty($filename)) {
      throw new HuggingFaceException('An error occurred performing the object detection request');
    }

    $options[RequestOptions::BODY] = fopen($filename, 'r');

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the object detection request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('object_detection', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function tableQuestionAnswering(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [
        'query' => '',
        'table' => [],
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the table question answering request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('table_question_answering', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function conversational(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => [
        'past_user_inputs' => [],
        'generated_responses' => [],
        'text' => '',
      ],
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the conversational request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('conversational', $body);

    return json_decode($body);
  }

  /**
   * {@inheritDoc}
   *
   * @throws HuggingFaceException
   */
  public function textToImage(array $parameters = []) {
    $url = $this->getUrl();

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'inputs' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::JSON] = $parameters;

    $response = $this->client->post($url, $options);

    $status_code = $response->getStatusCode();

    if ($status_code !== 200) {
      throw new HuggingFaceException('An error occurred performing the text to image request');
    }

    $body = (string) $response->getBody();
    $this->addResponse('text_to_image', $body);

    return json_decode($body);
  }

}
