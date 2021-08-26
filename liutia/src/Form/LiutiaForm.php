<?php

namespace Drupal\liutia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class main LiutiaForm.
 */
class LiutiaForm extends FormBase {

  /**
   * @var string[]
   *  Array of tables headers.
   */
  private $headers = [
    'Year',
    'Jan', 'Feb', 'Mar', 'Q1',
    'Apr', 'May', 'Jun', 'Q2',
    'Jul', 'Aug', 'Sep', 'Q3',
    'Oct', 'Nov', 'Dec', 'Q4',
    'YTD',
  ];

  /**
   * @var string[]
   */
  private $tableRows;

  /**
   * @var string[]
   */
  private $chetverts = [
    1 => ['Jan', 'Feb', 'Mar'],
    2 => ['Apr', 'May', 'Jun'],
    3 => ['Jul', 'Aug', 'Sep'],
    4 => ['Oct', 'Nov', 'Dec'],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'liutia_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->tableRows = is_null($this->tableRows) ? [
      1 => 1,
    ] : $this->tableRows;
    $form['tables_container'] = [
      '#type' => 'container',
    ];
    $form['tables_container']['#attributes']['id'] = 'tables_container';
    foreach ($this->tableRows as $table => $rows) {
      $this->buildTable($form, $table);
    }
    $form['#attributes'] = [
      'id' => 'my-form',
    ];

    $form['#tree'] = TRUE;

    $form['actions'] = [];

    $form['actions']['addtable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::formAddTable'],
      '#name' => 'add-table',
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'my-form',
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'my-form',
      ],
    ];
    $form_state->set('tableRows', $this->tableRows);
    return $form;
  }

  /**
   * Build a table in form.
   */
  private function buildTable(array &$form, int $tableNomer) {
    $form['tables_container'][$tableNomer] = [
      'table_year' => [
        '#type' => 'table',
        '#header' => $this->headers,
        '#attributes' => [
          'id' => $tableNomer,
        ],
        '#rows' => [],
      ],
      'actions' => [
        '#type' => 'actions',
        'add_row' => [
          '#type' => 'submit',
          '#data' => $tableNomer,
          '#name' => 'addButton_' . $tableNomer,
          '#value' => $this->t('Add row'),
          '#submit' => ['::formAddRow'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'my-form',
          ],
        ],
      ],
    ];
    $this->buildRows($form, $tableNomer);
    return $form;
  }

  /**
   * Builds rows in table.
   */
  private function buildRows(&$form, $tableNomer) {
    $year = date('Y');
    for ($i = $this->tableRows[$tableNomer]; $i > 0; $i--) {
      $result = [];
      $result['year'] = [
        '#markup' => $year - $i + 1,
      ];
      for ($j = 1; $j <= 4; $j++) {
        foreach ($this->chetverts[$j] as $month) {
          $result[$month] = [
            '#type' => 'number',
          ];
        }
        $result[$j] = [
          '#prefix' => '<span class="result">',
          '#suffix' => '</span>',
          '#markup' => '',
        ];
      }
      $result['result'] = [
        '#prefix' => '<span class="result">',
        '#suffix' => '</span>',
        '#markup' => '',
      ];
      $form['tables_container'][$tableNomer]['table_year'][$i] = $result;
    }
    return $form;
  }

  /**
   * Bez this function AJAX ne work.
   */
  function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX function to add rows.
   */
  function formAddRow(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#data'];
    $this->tableRows = $form_state->get('tableRows');
    ++$this->tableRows[$id];
    $form_state->set('tableRows', $this->tableRows);
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX function to add another table.
   */
  function formAddTable(array &$form, FormStateInterface $form_state) {
    $this->tableRows = $form_state->get('tableRows');
    $this->tableRows[] = 1;
    $form_state->set('tableRows', $this->tableRows);
    $form_state->setRebuild();
  }

  /**
   * Form validation before processing form results.
   */
  private function validateTables(FormStateInterface $form_state) {
    $tablesMergedRows = [];
    $tablesFirstNLastNotBlank = [];
    $filteredTables = [];
    $values = $form_state->getValues()['tables_container'];

    foreach ($values as $table_key => $table) {
      $tablesMergedRows[$table_key] = [];
      for ($blankRow = count($table['table_year']); (count(array_flip($table['table_year'][$blankRow])) === 1
        && end($table['table_year'][$blankRow]) === "") && $blankRow > 0; $blankRow--) {
        unset($table['table_year'][$blankRow]);
      }

      if (empty($table['table_year'])) {
        return FALSE;
      }

      foreach ($table['table_year'] as $row) {
        $tablesMergedRows[$table_key] = array_merge($tablesMergedRows[$table_key], array_values($row));
      }

      $filteredTables[$table_key] = array_filter($tablesMergedRows[$table_key], function ($item) {
        return $item !== "";
      });

    }

    foreach ($tablesMergedRows as $table_key => $table) {
      $arr_length = count($table);

      for ($firstNotBlank = 0; $firstNotBlank < $arr_length && $table[$firstNotBlank] === ""; $firstNotBlank++);
      for ($lastNotBlank = $arr_length - 1; $lastNotBlank >= 0 && $table[$lastNotBlank] === ""; $lastNotBlank--);

      $tablesFirstNLastNotBlank[$table_key]['first'] = $firstNotBlank == $arr_length ? NULL : $firstNotBlank;
      $tablesFirstNLastNotBlank[$table_key]['last'] = $lastNotBlank == -1 ? NULL : $lastNotBlank;

      if ((array_key_last($filteredTables[$table_key]) - array_key_first($filteredTables[$table_key]) + 1) != count($filteredTables[$table_key])) {
        return FALSE;
      }

      if ((count($tablesMergedRows[1]) != count($tablesMergedRows[$table_key]))
        || ($tablesFirstNLastNotBlank[1]['first'] != $tablesFirstNLastNotBlank[$table_key]['first'])
        || ($tablesFirstNLastNotBlank[1]['last'] != $tablesFirstNLastNotBlank[$table_key]['last'])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Table logik.
   */
  private function tableLogik(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['tables_container'];
    foreach ($values as $table_key => &$table) {
      $table = $table['table_year'];
      foreach ($table as $year_key => $year) {
        $year_value = 0;
        foreach ($this->chetverts as $chetvert_key => $chetvert) {
          $chetvert_values = 0;
          foreach ($chetvert as $month) {
            $chetvert_values += (int) $year[$month];
          }
          $chetvert_values = $chetvert_values === 0
            ? $chetvert_values : ($chetvert_values + 1) / 3;
          $year_value += $chetvert_values;
          $form['tables_container'][$table_key]['table_year'][$year_key][$chetvert_key]['#markup'] = round($chetvert_values, 2);
        }
        $year_value = $year_value === 0
          ? $year_value : ($year_value + 1) / 4;
        $form['tables_container'][$table_key]['table_year'][$year_key]['result']['#markup'] = round($year_value, 2);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valid = $this->validateTables($form_state);
    $messenger = \Drupal::messenger();
    $messenger->addMessage($valid ? "Valid" : "Invalid", $valid ? $messenger::TYPE_STATUS : $messenger::TYPE_ERROR);
    if ($valid) {
      $this->tableLogik($form, $form_state);
    }
    return $form;
  }

}
