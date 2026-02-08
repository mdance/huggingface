<?php

namespace Drupal\huggingface\Plugin\AiProvider;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\AiProviderClientBase;
use Drupal\ai\Exception\AiResponseErrorException;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInterface;
use Drupal\ai\OperationType\Embeddings\EmbeddingsOutput;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\ai\OperationType\ImageClassification\ImageClassificationInput;
use Drupal\ai\OperationType\ImageClassification\ImageClassificationInterface;
use Drupal\ai\OperationType\ImageClassification\ImageClassificationItem;
use Drupal\ai\OperationType\ImageClassification\ImageClassificationOutput;
use Drupal\ai\OperationType\SpeechToText\SpeechToTextInput;
use Drupal\ai\OperationType\SpeechToText\SpeechToTextInterface;
use Drupal\ai\OperationType\SpeechToText\SpeechToTextOutput;
use Drupal\ai\OperationType\TextToImage\TextToImageInput;
use Drupal\ai\OperationType\TextToImage\TextToImageInterface;
use Drupal\ai\OperationType\TextToImage\TextToImageOutput;
use Drupal\ai\Traits\OperationType\ChatTrait;
use Drupal\huggingface\HuggingFaceServiceInterface;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'huggingface' provider.
 */
#[AiProvider(
  id: 'huggingface',
  label: new TranslatableMarkup('Hugging Face'),
)]
class HuggingFaceProvider extends AiProviderClientBase implements ChatInterface, EmbeddingsInterface, ImageClassificationInterface, TextToImageInterface, SpeechToTextInterface {

  use ChatTrait;

  /**
   * The HuggingFace service.
   */
  protected HuggingFaceServiceInterface $huggingFaceService;

  /**
   * The API key loaded from Key module.
   */
  protected string $apiKey = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->huggingFaceService = $container->get('huggingface');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function isUsable(?string $operation_type = NULL, array $capabilities = []): bool {
    if (!$this->getConfig()->get('api_key')) {
      return FALSE;
    }
    if ($operation_type) {
      return in_array($operation_type, $this->getSupportedOperationTypes());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
      'embeddings',
      'image_classification',
      'text_to_image',
      'speech_to_text',
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    return $generalConfig;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function setAuthentication(mixed $authentication): void {
    $this->apiKey = $authentication;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getSetupData(): array {
    return [
      'key_config_name' => 'api_key',
      'default_models' => [
        'chat' => 'gpt2',
        'embeddings' => 'sentence-transformers/all-MiniLM-L6-v2',
        'image_classification' => 'google/vit-base-patch16-224',
        'text_to_image' => 'stabilityai/stable-diffusion-2-1',
        'speech_to_text' => 'facebook/wav2vec2-base-960h',
      ],
    ];
  }

  /**
   * Ensures the API key is loaded.
   */
  protected function loadClient(): void {
    if (empty($this->apiKey)) {
      $this->apiKey = $this->loadApiKey();
    }
  }

  /**
   * Returns authorization headers for direct HTTP calls.
   *
   * @return array
   *   The HTTP headers.
   */
  protected function getAuthHeaders(): array {
    return [
      'Authorization' => 'Bearer ' . $this->apiKey,
      'Content-Type' => 'application/json',
    ];
  }

  /**
   * Builds the HuggingFace Inference API URL for a model.
   *
   * @param string $model_id
   *   The model ID (e.g., 'sentence-transformers/all-MiniLM-L6-v2').
   *
   * @return string
   *   The full API URL.
   */
  protected function buildApiUrl(string $model_id): string {
    return 'https://api-inference.huggingface.co/models/' . $model_id;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput {
    $this->loadClient();

    $prompt = '';
    if ($input instanceof ChatInput) {
      $messages = $input->getMessages();
      $parts = [];
      foreach ($messages as $message) {
        $parts[] = $message->getText();
      }
      $prompt = implode("\n", $parts);
    }
    elseif (is_string($input)) {
      $prompt = $input;
    }
    elseif (is_array($input)) {
      $parts = [];
      foreach ($input as $message) {
        $parts[] = $message['content'] ?? '';
      }
      $prompt = implode("\n", $parts);
    }

    $url = $this->buildApiUrl($model_id);
    $payload = [
      'inputs' => $prompt,
      'parameters' => $this->configuration,
    ];

    try {
      $response = $this->httpClient->request('POST', $url, [
        RequestOptions::HEADERS => $this->getAuthHeaders(),
        RequestOptions::JSON => $payload,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
    }
    catch (\Exception $e) {
      throw new AiResponseErrorException('Hugging Face chat request failed: ' . $e->getMessage());
    }

    $text = '';
    if (is_array($data)) {
      if (isset($data[0]['generated_text'])) {
        $text = $data[0]['generated_text'];
      }
      elseif (isset($data['generated_text'])) {
        $text = $data['generated_text'];
      }
    }

    $message = new ChatMessage('assistant', $text, []);
    return new ChatOutput($message, $data, []);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function embeddings(string|EmbeddingsInput $input, string $model_id, array $tags = []): EmbeddingsOutput {
    $this->loadClient();

    $prompt = $input instanceof EmbeddingsInput ? $input->getPrompt() : $input;

    $url = $this->buildApiUrl($model_id);
    $payload = [
      'inputs' => $prompt,
    ];

    try {
      $response = $this->httpClient->request('POST', $url, [
        RequestOptions::HEADERS => $this->getAuthHeaders(),
        RequestOptions::JSON => $payload,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
    }
    catch (\Exception $e) {
      throw new AiResponseErrorException('Hugging Face embeddings request failed: ' . $e->getMessage());
    }

    // HuggingFace feature extraction returns nested arrays for sentence
    // transformers. Flatten to a single vector if needed.
    $vector = $data;
    if (is_array($data) && !empty($data) && is_array($data[0] ?? NULL)) {
      // Sentence-transformers return [[vector]]; take the first.
      $vector = $data[0];
      // Some models nest further: [[[vector]]].
      if (is_array($vector) && !empty($vector) && is_array($vector[0] ?? NULL)) {
        $vector = $vector[0];
      }
    }

    return new EmbeddingsOutput($vector, $data, []);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function imageClassification(string|array|ImageClassificationInput $input, string $model_id, array $tags = []): ImageClassificationOutput {
    $this->loadClient();

    $binary = '';
    if ($input instanceof ImageClassificationInput) {
      $binary = $input->getImageFile()->getBinary();
    }
    elseif (is_string($input)) {
      $binary = $input;
    }

    $url = $this->buildApiUrl($model_id);

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->apiKey,
        ],
        RequestOptions::BODY => $binary,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
    }
    catch (\Exception $e) {
      throw new AiResponseErrorException('Hugging Face image classification request failed: ' . $e->getMessage());
    }

    $items = [];
    if (is_array($data)) {
      foreach ($data as $result) {
        $items[] = new ImageClassificationItem(
          $result['label'] ?? '',
          $result['score'] ?? NULL,
        );
      }
    }

    return new ImageClassificationOutput($items, $data, []);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function textToImage(string|TextToImageInput $input, string $model_id, array $tags = []): TextToImageOutput {
    $this->loadClient();

    $prompt = $input instanceof TextToImageInput ? $input->getText() : $input;

    $url = $this->buildApiUrl($model_id);
    $payload = [
      'inputs' => $prompt,
    ];

    try {
      $response = $this->httpClient->request('POST', $url, [
        RequestOptions::HEADERS => $this->getAuthHeaders(),
        RequestOptions::JSON => $payload,
      ]);
      $binary = (string) $response->getBody();
    }
    catch (\Exception $e) {
      throw new AiResponseErrorException('Hugging Face text-to-image request failed: ' . $e->getMessage());
    }

    // HuggingFace text-to-image returns raw binary image data (PNG).
    $images = [new ImageFile($binary, 'image/png', 'huggingface.png')];

    return new TextToImageOutput($images, $binary, []);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function speechToText(string|SpeechToTextInput $input, string $model_id, array $tags = []): SpeechToTextOutput {
    $this->loadClient();

    $binary = '';
    if ($input instanceof SpeechToTextInput) {
      $binary = $input->getBinary();
    }
    elseif (is_string($input)) {
      $binary = $input;
    }

    $url = $this->buildApiUrl($model_id);

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->apiKey,
        ],
        RequestOptions::BODY => $binary,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
    }
    catch (\Exception $e) {
      throw new AiResponseErrorException('Hugging Face speech-to-text request failed: ' . $e->getMessage());
    }

    $text = $data['text'] ?? '';

    return new SpeechToTextOutput($text, $data, []);
  }

}
