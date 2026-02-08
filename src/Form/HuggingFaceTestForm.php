<?php

namespace Drupal\huggingface\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huggingface\HuggingFaceConstants;
use Drupal\huggingface\HuggingFaceServiceInterface;
use Drupal\multivalue_form_element\Element\MultiValue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the HuggingFaceTestForm class.
 */
class HuggingFaceTestForm extends FormBase {

  /**
   * Provides the constructor method.
   *
   * @param \Drupal\huggingface\HuggingFaceServiceInterface $service
   *   The HuggingFace service.
   */
  public function __construct(
    protected HuggingFaceServiceInterface $service,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('huggingface'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'huggingface_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $rebuilding = $form_state->isRebuilding();

    $task = $form_state->get('task') ?? NULL;
    $response = $form_state->get('response');

    $options = $this->service->getTaskOptions();

    $form['task'] = [
      '#type' => 'select',
      '#title' => $this->t('Task'),
      '#options' => $options,
    ];

    $key = 'text_classification';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Text Classification'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $args = [];

      $label = $response->label ?? $this->t('unknown');
      $label = strtolower($label);

      $args['@label'] = $label;

      $markup = $this->t('The text classification is @label', $args);

      $response_details['formatted'] = [
        '#type' => 'item',
        '#title' => $this->t('Formatted'),
        '#markup' => $markup,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inputs'),
    ];

    $details['parameters'] = [
      '#type' => 'container',
    ];

    $parameters = &$details['parameters'];

    $parameters['top_k'] = [
      '#type' => 'number',
      '#title' => $this->t('Top K'),
    ];

    $key = 'zero_shot_classification';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Zero Shot Classification'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['label'] = $this->t('Label');
      $header['score'] = $this->t('Score');

      $rows = [];

      foreach ($response->labels as $key => $label) {
        $row = [];

        $row['label'] = $label;
        $row['score'] = $response->scores[$key] ?? '';

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    $details['parameters'] = [
      '#type' => 'container',
    ];

    $parameters = &$details['parameters'];

    $parameters['candidate_labels'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Candidate Labels'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
      ],
    ];

    $key = 'token_classification';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Token Classification'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $markup = '';

      foreach ($response as $group) {
        $markup .= $group->word . ' (' . $group->entity_group . ') ';
      }

      $markup = trim($markup);

      $response_details['formatted'] = [
        '#type' => 'item',
        '#title' => $this->t('Formatted'),
        '#markup' => $markup,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inputs'),
    ];

    $details['parameters'] = [
      '#type' => 'container',
    ];

    $parameters = &$details['parameters'];

    $options = $this->service->getAggregationStrategies();

    $parameters['aggregation_strategy'] = [
      '#type' => 'select',
      '#title' => $this->t('Aggregation Strategy'),
      '#options' => $options,
    ];

    $key = 'question_answering';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Question Answering'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $markup = $response->answer ?? '';

      $response_details['formatted'] = [
        '#type' => 'item',
        '#title' => $this->t('Formatted'),
        '#markup' => $markup,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'container',
    ];

    $inputs = &$details['inputs'];

    $inputs['question'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Question'),
    ];

    $inputs['context'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Context'),
    ];

    $key = 'fill_mask';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Fill Mask'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['label'] = $this->t('Label');
      $header['score'] = $this->t('Score');

      $rows = [];

      foreach ($response as $suggestion) {
        $row = [];

        $row['label'] = $suggestion->token_str;
        $row['score'] = $suggestion->score ?? '';

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    $key = 'summarization';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Summarization'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['label'] = $this->t('Label');
      $header['score'] = $this->t('Score');

      $rows = [];

      foreach ($response as $suggestion) {
        $row = [];

        $row['label'] = $suggestion->token_str;
        $row['score'] = $suggestion->score ?? '';

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    // @todo Implement translation, text to text generation, text generation.
    $key = 'feature_extraction';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Feature Extraction'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      foreach ($response as $k => $rows) {
        $total = count($rows[0]);

        $header = [];

        for ($i = 0; $i <= $total; $i++) {
          $header[] = $i;
        }

        $response_details['formatted_' . $k] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    $key = 'sentence_embeddings';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Sentence Embeddings'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    $key = 'sentence_similarity';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Sentence Similarity'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['sentence'] = $this->t('Sentence');
      $header['score'] = $this->t('Score');

      $rows = [];

      $parents = [
        $key,
        'inputs',
        'sentences',
      ];

      $sentences = $form_state->getValue($parents);

      foreach ($response->similarities as $key => $score) {
        $row = [];

        $row['sentence'] = $sentences[$key]['input'];
        $row['score'] = $score;

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'container',
    ];

    $inputs = &$details['inputs'];

    $inputs['source_sentence'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Source Sentence'),
    ];

    $inputs['sentences'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Sentences'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      'input' => [
        '#type' => 'textfield',
        '#title' => $this->t('Sentence'),
      ],
    ];

    $key = 'ranking';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Ranking'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['input'] = $this->t('Input');
      $header['score'] = $this->t('Score');

      $rows = [];

      $parents = [
        $key,
        'inputs',
      ];

      $inputs = $form_state->getValue($parents);

      foreach ($response->scores as $key => $score) {
        $row = [];

        $row['input'] = $inputs[$key]['input'];
        $row['score'] = $score;

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Inputs'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      'input' => [
        '#type' => 'textfield',
        '#title' => $this->t('Input'),
      ],
    ];

    $keys = [
      'image_classification' => [
        'title' => $this->t('Image Classification'),
        'extensions' => ['gif png jpg jpeg'],
      ],
      'automatic_speech_recognition' => [
        'title' => $this->t('Automatic Speech Recognition'),
        'extensions' => ['mp3 wav ogg m4a flac'],
      ],
      'audio_classification' => [
        'title' => $this->t('Audio Classification'),
        'extensions' => ['mp3 wav ogg m4a flac'],
      ],
      'object_detection' => [
        'title' => $this->t('Object Detection'),
        'extensions' => ['gif png jpg jpeg'],
      ],
      'image_segmentation' => [
        'title' => $this->t('Image Segmentation'),
        'extensions' => ['gif png jpg jpeg'],
      ],
    ];

    foreach ($keys as $key => $meta) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $meta['title'],
        '#states' => [
          'visible' => [
            ':input[name="task"]' => ['value' => $key],
          ],
        ],
        '#open' => TRUE,
      ];

      $details = &$form[$key];

      if ($rebuilding && $response && $task === $key) {
        $details['response'] = [
          '#type' => 'details',
          '#title' => $this->t('Response'),
          '#open' => TRUE,
        ];

        $response_details = &$details['response'];

        switch ($key) {
          case 'image_classification':
          case 'audio_classification':
            $header = [];

            $header['label'] = $this->t('Label');
            $header['score'] = $this->t('Score');

            $rows = [];

            foreach ($response as $data) {
              $row = [];

              $row['label'] = $data->label;
              $row['score'] = $data->score;

              $rows[] = $row;
            }

            $response_details['formatted'] = [
              '#type' => 'table',
              '#header' => $header,
              '#rows' => $rows,
            ];

            break;
        }

        $response_details['json'] = [
          '#type' => 'item',
          '#title' => $this->t('JSON'),
          'value' => [
            '#type' => 'html_tag',
            '#tag' => 'pre',
            '#value' => print_r($response, TRUE),
          ],
        ];
      }

      $details['inputs'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Inputs'),
        '#upload_location' => 'private://hugging-face/' . $key,
        '#upload_validators' => [
          'file_validate_extensions' => $meta['extensions'],
        ],
      ];
    }

    $key = 'table_question_answering';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Table Question Answering'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['sentence'] = $this->t('Sentence');
      $header['score'] = $this->t('Score');

      $rows = [];

      $parents = [
        $key,
        'inputs',
        'sentences',
      ];

      $sentences = $form_state->getValue($parents);

      foreach ($response->similarities as $key => $score) {
        $row = [];

        $row['sentence'] = $sentences[$key]['input'];
        $row['score'] = $score;

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'container',
    ];

    $inputs = &$details['inputs'];

    $inputs['query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query'),
    ];

    $inputs['table'] = [
      '#type' => 'tablefield',
      '#title' => $this->t('Table'),
    ];

    $key = 'conversational';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Conversational'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $header = [];

      $header['sentence'] = $this->t('Sentence');
      $header['score'] = $this->t('Score');

      $rows = [];

      $parents = [
        $key,
        'inputs',
        'sentences',
      ];

      $sentences = $form_state->getValue($parents);

      foreach ($response->similarities as $key => $score) {
        $row = [];

        $row['sentence'] = $sentences[$key]['input'];
        $row['score'] = $score;

        $rows[] = $row;
      }

      $response_details['formatted'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'container',
    ];

    $inputs = &$details['inputs'];

    $inputs['past_user_inputs'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Past User Inputs'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      'input' => [
        '#type' => 'textfield',
        '#title' => $this->t('Input'),
      ],
    ];

    $inputs['generated_responses'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Generated Responses'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      'input' => [
        '#type' => 'textfield',
        '#title' => $this->t('Response'),
      ],
    ];

    $inputs['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
    ];

    $key = 'text_to_image';

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->t('Text To Image'),
      '#states' => [
        'visible' => [
          ':input[name="task"]' => ['value' => $key],
        ],
      ],
      '#open' => TRUE,
    ];

    $details = &$form[$key];

    if ($rebuilding && $response && $task === $key) {
      $details['response'] = [
        '#type' => 'details',
        '#title' => $this->t('Response'),
        '#open' => TRUE,
      ];

      $response_details = &$details['response'];

      $response_details['formatted'] = [
        '#type' => 'html_tag',
        '#tag' => 'img',
        '#attributes' => [
          'src' => 'https://placeholder.com/150x150.png',
        ],
      ];

      $response_details['json'] = [
        '#type' => 'item',
        '#title' => $this->t('JSON'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => print_r($response, TRUE),
        ],
      ];
    }

    $details['inputs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $actions = &$form['actions'];

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $task = $values['task'];
    $parameters = $values[$task];

    switch ($task) {
      case 'fill_mask':
        if (!str_contains($parameters['inputs'], HuggingFaceConstants::TOKEN_MASK)) {
          $parents = [
            'fill_mask',
            'inputs',
          ];

          $element = NestedArray::getValue($form, $parents);

          $args = [];

          $args['@mask'] = HuggingFaceConstants::TOKEN_MASK;

          $message = $this->t('Please include the @mask token in your input.', $args);

          $form_state->setError($element, $message);
        }

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $task = $values['task'];
    $parameters = $values[$task];

    try {
      switch ($task) {
        case 'zero_shot_classification':
          $labels = &$parameters['parameters']['candidate_labels'];

          foreach ($labels as &$label) {
            $label = $label['label'];
          }

          break;

        case 'sentence_similarity':
          $sentences = &$parameters['inputs']['sentences'];

          foreach ($sentences as &$sentence) {
            $sentence = $sentence['input'];
          }

          break;

        case 'ranking':
          $inputs = &$parameters['inputs'];

          foreach ($inputs as &$input) {
            $input = $input['input'];
          }

          break;

        case 'table_question_answering':
          $table = &$parameters['inputs']['table'];

          $rows = &$parameters['inputs']['table']['tablefield']['table'];

          $new_rows = [];

          foreach ($rows as $row) {
            $new_row = [];

            $key = '';

            foreach ($row as $k => $v) {
              if (!is_numeric($k)) {
                continue;
              }

              if ($k === 0) {
                if (empty($v)) {
                  continue 2;
                }

                $key = $v;
              }
              elseif (!empty($v)) {
                $new_row[] = $v;
              }
            }

            $new_rows[$key] = $new_row;
          }

          $table = $new_rows;

          break;

        case 'conversational':
          $past_user_inputs = &$parameters['inputs']['past_user_inputs'];

          foreach ($past_user_inputs as &$past_user_input) {
            $past_user_input = $past_user_input['input'];
          }

          $generated_responses = &$parameters['inputs']['generated_responses'];

          foreach ($generated_responses as &$generated_response) {
            $generated_response = $generated_response['input'];
          }

          break;
      }

      $response = $this->service->performTask($task, $parameters);

      $form_state->set('task', $task);
      $form_state->set('response', $response);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }

    $form_state->setRebuild();
  }

}
