<?php

namespace Drupal\huggingface;

use GuzzleHttp\RequestOptions;

/**
 * Provides the HuggingFaceServiceInterface interface.
 */
interface HuggingFaceServiceInterface {

  /**
   * Gets the access token.
   *
   * @return string
   *   A string containing the access token.
   */
  public function getAccessToken();

  /**
   * Gets the url.
   *
   * @return string
   *   A string containing the url.
   */
  public function getUrl();

  /**
   * Gets the logging status.
   *
   * @return bool
   *   A boolean indicating the logging status.
   */
  public function getLogging();

  /**
   * Saves the configuration.
   *
   * @param array $input
   *   The configuration array.
   *
   * @return $this
   */
  public function saveConfiguration(array $input);

  /**
   * Gets the configurations.
   *
   * @return array
   *   The configuration array.
   */
  public function getConfiguration();

  /**
   * Adds a response.
   *
   * @param string $type
   *   A string containing the response type.
   * @param string $data
   *   A string containing the response data.
   */
  public function addResponse($type, $data);

  /**
   * Performs cron processing.
   */
  public function cron();

  /**
   * Provides the task options.
   *
   * @return array
   *   An array of task options.
   */
  public function getTaskOptions();

  /**
   * Gets the default task models.
   */
  public function getTaskModels(): array;

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
  public function performTask(string $task, array $parameters = []);

  /**
   * Gets the request headers.
   *
   * @return array
   *   An array of request headers.
   */
  public function getHeaders();

  /**
   * Performs a text classification request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function textClassification(array $parameters = []);

  /**
   * Performs a zero shot classification request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function zeroShotClassification(array $parameters = []);

  /**
   * {@inheritDoc}
   */
  public function getAggregationStrategies();

  /**
   * Performs a token classification request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function tokenClassification(array $parameters = []);

  /**
   * Performs a question answering request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function questionAnswering(array $parameters = []);

  /**
   * Performs a fill mask request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function fillMask(array $parameters = []);

  /**
   * Performs a summarization request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function summarization(array $parameters = []);

  /**
   * Performs a feature abstraction request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function featureExtraction(array $parameters = []);

  /**
   * Performs a sentence embeddings request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function sentenceEmbeddings(array $parameters = []);

  /**
   * Performs a sentence similarity request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function sentenceSimilarity(array $parameters = []);

  /**
   * Performs a ranking request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function ranking(array $parameters = []);

  /**
   * Performs an image classification request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function imageClassification(array $parameters = []);

  /**
   * Performs an automatic speech recognition request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function automaticSpeechRecognition(array $parameters = []);

  /**
   * Performs an audio classification request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function audioClassification(array $parameters = []);

  /**
   * Performs an object detection request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function objectDetection(array $parameters = []);

  /**
   * Performs a table question answering request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function tableQuestionAnswering(array $parameters = []);

  /**
   * Performs a conversational request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function conversational(array $parameters = []);

  /**
   * Performs a text to image request.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @throws HuggingFaceException
   */
  public function textToImage(array $parameters = []);

  /**
   * Gets the inference endpoints.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @return array
   *   The inference endpoints.
   */
  public function getInferenceEndpoints(array $parameters = []);

}
