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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
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
   * {@inheritDoc}
   */
  public function getTaskModels(): array {
    // https://huggingface.co/docs/api-inference/detailed_parameters
    $output = [];

    $output['text_classification'] = 'distilbert-base-uncased-finetuned-sst-2-english';
    $output['zero_shot_classification'] = 'facebook/bart-large-mnli';
    $output['token_classification'] = 'dbmdz/bert-large-cased-finetuned-conll03-english';
    $output['question_answering'] = 'deepset/roberta-base-squad2';
    $output['fill_mask'] = 'bert-base-uncased';
    $output['summarization'] = 'facebook/bart-large-cnn';
    // $output['translation'] = 't5-base';
    $output['translation'] = 'Helsinki-NLP/opus-mt-ru-en';
    $output['text_to_text_generation'] = 'dbmdz/bert-large-cased-finetuned-conll03-english';
    $output['text_generation'] = 'gpt2';
    $output['feature_extraction'] = 'sentence-transformers/paraphrase-xlm-r-multi';
    $output['sentence_similarity'] = 'sentence-transformers/all-MiniLM-L6-v2';
    $output['image_classification'] = 'google/vit-base-patch16-224';
    $output['automatic_speech_recognition'] = 'facebook/wav2vec2-base-960h';
    $output['audio_classification'] = 'superb/hubert-large-superb-er';
    $output['object_detection'] = 'facebook/detr-resnet-50';
    $output['image_segmentation'] = 'facebook/detr-resnet-50-panoptic';
    $output['table_question_answering'] = 'google/tapas-base-finetuned-wtq';
    $output['conversational'] = 'microsoft/DialoGPT-large';

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

  /**
   * {@inheritDoc}
   */
  public function getInferenceEndpoints(array $parameters = []) {
    $url = HuggingFaceConstants::SCHEMA . '://' . HuggingFaceConstants::HOST . HuggingFaceConstants::PATH_ENDPOINTS;

    $options = [];

    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $defaults = [
      'access_token' => $this->getAccessToken(),
      'namespace' => '',
    ];

    $parameters = array_merge($defaults, $parameters);

    $options[RequestOptions::HEADERS]['authorization'] = 'Bearer ' . $parameters['access_token'];

    if (empty($parameters['namespace'])) {
      throw new HuggingFaceException('The inference endpoints namespace was not specified.');
    }

    $url .= '/' . $parameters['namespace'];

    try {
      $response = $this->client->get($url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Failed to get inference endpoints for namespace @namespace. Status: @status, Response: @response', [
          '@namespace' => $parameters['namespace'],
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('An error occurred retrieving the inference endpoints: ' . $body);
      }

      $body = (string) $response->getBody();
      $this->addResponse('inference_endpoints', $body);

      return json_decode($body);
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error getting inference endpoints for namespace @namespace: @error. Response: @response', [
        '@namespace' => $parameters['namespace'],
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);
      throw new HuggingFaceException('Failed to get inference endpoints: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error getting inference endpoints for namespace @namespace: @error', [
        '@namespace' => $parameters['namespace'],
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getInferenceEndpoint(string $namespace, string $name, array $parameters = []) {
    $url = HuggingFaceConstants::SCHEMA . '://' . HuggingFaceConstants::HOST . HuggingFaceConstants::PATH_ENDPOINTS;
    $url .= '/' . $namespace . '/' . $name;

    $options = [];
    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $access_token = $parameters['access_token'] ?? $this->getAccessToken();
    $options[RequestOptions::HEADERS]['authorization'] = 'Bearer ' . $access_token;

    try {
      $response = $this->client->get($url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Failed to get inference endpoint @namespace/@name. Status: @status, Response: @response', [
          '@namespace' => $namespace,
          '@name' => $name,
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('An error occurred retrieving the inference endpoint: ' . $body);
      }

      $body = (string) $response->getBody();
      $this->addResponse('inference_endpoint_get', $body);

      return json_decode($body);
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error getting inference endpoint @namespace/@name: @error. Response: @response', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);
      throw new HuggingFaceException('Failed to get inference endpoint: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error getting inference endpoint @namespace/@name: @error', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function createInferenceEndpoint(array $data, array $parameters = []) {
    $namespace = $data['namespace'] ?? '';

    if (empty($namespace)) {
      throw new HuggingFaceException('The namespace is required to create an inference endpoint.');
    }

    $url = HuggingFaceConstants::SCHEMA . '://' . HuggingFaceConstants::HOST . HuggingFaceConstants::PATH_ENDPOINTS;
    $url .= '/' . $namespace;

    $options = [];
    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $access_token = $parameters['access_token'] ?? $this->getAccessToken();
    $options[RequestOptions::HEADERS]['authorization'] = 'Bearer ' . $access_token;

    // Build the endpoint configuration.
    $task = $data['task'] ?? 'text-generation';
    $framework = $data['framework'] ?? 'pytorch';

    // The API expects model.image with variant key: {"huggingface": {}} or
    // {"custom": {"url": "...", "health_route": "...", "env": {...}}}.
    $image_config = ['huggingface' => new \stdClass()];

    if (!empty($data['custom_image'])) {
      if (is_array($data['custom_image'])) {
        $image_config = ['custom' => $data['custom_image']];
      }
      else {
        $image_config = ['custom' => ['url' => $data['custom_image']]];
      }
    }

    $endpoint_config = [
      'name' => $data['name'] ?? '',
      'type' => $data['type'] ?? 'protected',
      'compute' => [
        'accelerator' => $data['accelerator'] ?? 'cpu',
        'instanceSize' => $data['instance_size'] ?? 'x1',
        'instanceType' => $data['instance_type'] ?? 'intel-spr',
        'scaling' => [
          'minReplica' => $data['min_replica'] ?? 0,
          'maxReplica' => $data['max_replica'] ?? 1,
        ],
      ],
      'model' => [
        'repository' => $data['repository'] ?? '',
        'framework' => $framework,
        'task' => $task,
        'image' => $image_config,
      ],
      'provider' => [
        'region' => $data['region'] ?? 'us-east-1',
        'vendor' => $data['vendor'] ?? 'aws',
      ],
    ];

    // Add revision if specified.
    if (!empty($data['revision'])) {
      $endpoint_config['model']['revision'] = $data['revision'];
    }

    // Add scale to zero timeout if specified.
    if (isset($data['scale_to_zero_timeout'])) {
      $endpoint_config['compute']['scaling']['scaleToZeroTimeout'] = $data['scale_to_zero_timeout'];
    }

    $options[RequestOptions::JSON] = $endpoint_config;

    // Log the request payload for debugging.
    if ($this->getLogging()) {
      \Drupal::logger('huggingface')->debug('Creating inference endpoint: @url with payload: @payload', [
        '@url' => $url,
        '@payload' => json_encode($endpoint_config),
      ]);
    }

    try {
      $response = $this->client->post($url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200 && $status_code !== 201 && $status_code !== 202) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Failed to create inference endpoint. Status: @status, Response: @response', [
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('An error occurred creating the inference endpoint: ' . $body);
      }

      $body = (string) $response->getBody();
      $this->addResponse('inference_endpoint_create', $body);

      if ($this->getLogging()) {
        \Drupal::logger('huggingface')->info('Successfully created inference endpoint @name', [
          '@name' => $data['name'] ?? 'unknown',
        ]);
      }

      return json_decode($body);
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error creating inference endpoint: @error. Response: @response. Payload: @payload', [
        '@error' => $e->getMessage(),
        '@response' => $response_body,
        '@payload' => json_encode($endpoint_config),
      ]);
      throw new HuggingFaceException('Failed to create inference endpoint: ' . $response_body, 0, $e);
    }
    catch (ServerException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Server error creating inference endpoint: @error. Response: @response', [
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);
      throw new HuggingFaceException('HuggingFace server error: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error creating inference endpoint: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function updateInferenceEndpoint(string $namespace, string $name, array $data, array $parameters = []) {
    $url = HuggingFaceConstants::SCHEMA . '://' . HuggingFaceConstants::HOST . HuggingFaceConstants::PATH_ENDPOINTS;
    $url .= '/' . $namespace . '/' . $name;

    $options = [];
    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $access_token = $parameters['access_token'] ?? $this->getAccessToken();
    $options[RequestOptions::HEADERS]['authorization'] = 'Bearer ' . $access_token;

    // Build the update payload.
    $update_config = [];

    // Compute configuration updates.
    $compute = [];
    if (isset($data['accelerator'])) {
      $compute['accelerator'] = $data['accelerator'];
    }
    if (isset($data['instance_size'])) {
      $compute['instanceSize'] = $data['instance_size'];
    }
    if (isset($data['instance_type'])) {
      $compute['instanceType'] = $data['instance_type'];
    }
    if (isset($data['min_replica']) || isset($data['max_replica'])) {
      $compute['scaling'] = [];
      if (isset($data['min_replica'])) {
        $compute['scaling']['minReplica'] = $data['min_replica'];
      }
      if (isset($data['max_replica'])) {
        $compute['scaling']['maxReplica'] = $data['max_replica'];
      }
      if (isset($data['scale_to_zero_timeout'])) {
        $compute['scaling']['scaleToZeroTimeout'] = $data['scale_to_zero_timeout'];
      }
    }
    if (!empty($compute)) {
      $update_config['compute'] = $compute;
    }

    // Model configuration updates.
    $model = [];
    if (isset($data['repository'])) {
      $model['repository'] = $data['repository'];
    }
    if (isset($data['framework'])) {
      $model['framework'] = $data['framework'];
    }
    if (isset($data['task'])) {
      $model['task'] = $data['task'];
    }
    if (isset($data['revision'])) {
      $model['revision'] = $data['revision'];
    }
    // Custom image uses model.image with variant key format.
    if (isset($data['custom_image'])) {
      if (is_array($data['custom_image'])) {
        $model['image'] = ['custom' => $data['custom_image']];
      }
      else {
        $model['image'] = ['custom' => ['url' => $data['custom_image']]];
      }
    }
    if (!empty($model)) {
      $update_config['model'] = $model;
    }

    $options[RequestOptions::JSON] = $update_config;

    // Log the request payload for debugging.
    if ($this->getLogging()) {
      \Drupal::logger('huggingface')->debug('Updating inference endpoint @namespace/@name with payload: @payload', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@payload' => json_encode($update_config),
      ]);
    }

    try {
      $response = $this->client->request('PUT', $url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200 && $status_code !== 202) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Failed to update inference endpoint @namespace/@name. Status: @status, Response: @response', [
          '@namespace' => $namespace,
          '@name' => $name,
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('An error occurred updating the inference endpoint: ' . $body);
      }

      $body = (string) $response->getBody();
      $this->addResponse('inference_endpoint_update', $body);

      if ($this->getLogging()) {
        \Drupal::logger('huggingface')->info('Successfully updated inference endpoint @namespace/@name', [
          '@namespace' => $namespace,
          '@name' => $name,
        ]);
      }

      return json_decode($body);
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error updating inference endpoint @namespace/@name: @error. Response: @response', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);
      throw new HuggingFaceException('Failed to update inference endpoint: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error updating inference endpoint @namespace/@name: @error', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function deleteInferenceEndpoint(string $namespace, string $name, array $parameters = []) {
    $url = HuggingFaceConstants::SCHEMA . '://' . HuggingFaceConstants::HOST . HuggingFaceConstants::PATH_ENDPOINTS;
    $url .= '/' . $namespace . '/' . $name;

    $options = [];
    $options[RequestOptions::HEADERS] = $this->getHeaders();

    $access_token = $parameters['access_token'] ?? $this->getAccessToken();
    $options[RequestOptions::HEADERS]['authorization'] = 'Bearer ' . $access_token;

    if ($this->getLogging()) {
      \Drupal::logger('huggingface')->debug('Deleting inference endpoint @namespace/@name', [
        '@namespace' => $namespace,
        '@name' => $name,
      ]);
    }

    try {
      $response = $this->client->delete($url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200 && $status_code !== 202 && $status_code !== 204) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Failed to delete inference endpoint @namespace/@name. Status: @status, Response: @response', [
          '@namespace' => $namespace,
          '@name' => $name,
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('An error occurred deleting the inference endpoint: ' . $body);
      }

      $this->addResponse('inference_endpoint_delete', json_encode(['namespace' => $namespace, 'name' => $name]));

      if ($this->getLogging()) {
        \Drupal::logger('huggingface')->info('Successfully deleted inference endpoint @namespace/@name', [
          '@namespace' => $namespace,
          '@name' => $name,
        ]);
      }

      return TRUE;
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error deleting inference endpoint @namespace/@name: @error. Response: @response', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);
      throw new HuggingFaceException('Failed to delete inference endpoint: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error deleting inference endpoint @namespace/@name: @error', [
        '@namespace' => $namespace,
        '@name' => $name,
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function pauseInferenceEndpoint(string $namespace, string $name, array $parameters = []) {
    // Pausing is done by setting min/max replicas to 0.
    return $this->updateInferenceEndpoint($namespace, $name, [
      'min_replica' => 0,
      'max_replica' => 0,
    ], $parameters);
  }

  /**
   * {@inheritDoc}
   */
  public function resumeInferenceEndpoint(string $namespace, string $name, array $parameters = []) {
    // Resuming is done by setting min/max replicas back to at least 1.
    return $this->updateInferenceEndpoint($namespace, $name, [
      'min_replica' => 1,
      'max_replica' => 1,
    ], $parameters);
  }

  /**
   * {@inheritDoc}
   */
  public function scaleToZeroInferenceEndpoint(string $namespace, string $name, array $parameters = []) {
    // Scale to zero sets min replica to 0 but keeps max replica > 0 for auto-scaling.
    return $this->updateInferenceEndpoint($namespace, $name, [
      'min_replica' => 0,
    ], $parameters);
  }

  /**
   * {@inheritDoc}
   */
  public function imageTextToText(array $parameters = []) {
    $model = $parameters['model'] ?? 'microsoft/Florence-2-large';
    $image = $parameters['image'] ?? '';
    $prompt = $parameters['prompt'] ?? '<OCR>';
    $access_token = $parameters['access_token'] ?? $this->getAccessToken();

    if (empty($image)) {
      throw new HuggingFaceException('Image data is required for image-text-to-text.');
    }

    // Build the URL for the Hosted Inference API.
    $url = 'https://router.huggingface.co/models/' . $model;

    $options = [];
    $options[RequestOptions::HEADERS] = [
      'Authorization' => 'Bearer ' . $access_token,
      'Content-Type' => 'application/json',
    ];

    // Florence-2 expects inputs as a dict with image and text.
    // The image should be base64 encoded.
    $imageData = $image;
    if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $image)) {
      // Image is binary, encode it.
      $imageData = base64_encode($image);
    }

    $payload = [
      'inputs' => [
        'image' => $imageData,
        'text' => $prompt,
      ],
      'parameters' => [
        'max_new_tokens' => $parameters['max_tokens'] ?? 1024,
      ],
    ];

    $options[RequestOptions::JSON] = $payload;
    $options['timeout'] = $parameters['timeout'] ?? 120;

    if ($this->getLogging()) {
      \Drupal::logger('huggingface')->debug('Image-text-to-text request to @model with prompt: @prompt', [
        '@model' => $model,
        '@prompt' => $prompt,
      ]);
    }

    try {
      $response = $this->client->post($url, $options);

      $status_code = $response->getStatusCode();

      if ($status_code !== 200) {
        $body = (string) $response->getBody();
        \Drupal::logger('huggingface')->error('Image-text-to-text request failed. Status: @status, Response: @response', [
          '@status' => $status_code,
          '@response' => $body,
        ]);
        throw new HuggingFaceException('Image-text-to-text request failed: ' . $body);
      }

      $body = (string) $response->getBody();

      if ($this->getLogging()) {
        $this->addResponse('image_text_to_text', $body);
      }

      return json_decode($body);
    }
    catch (ClientException $e) {
      $response_body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
      \Drupal::logger('huggingface')->error('Client error in image-text-to-text: @error. Response: @response', [
        '@error' => $e->getMessage(),
        '@response' => $response_body,
      ]);

      // Check for model loading status.
      $decoded = json_decode($response_body, TRUE);
      if (isset($decoded['error']) && str_contains($decoded['error'], 'loading')) {
        throw new HuggingFaceException('Model is currently loading. Please try again in a few seconds. ' . $response_body, 0, $e);
      }

      throw new HuggingFaceException('Image-text-to-text request failed: ' . $response_body, 0, $e);
    }
    catch (\Exception $e) {
      \Drupal::logger('huggingface')->error('Unexpected error in image-text-to-text: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
