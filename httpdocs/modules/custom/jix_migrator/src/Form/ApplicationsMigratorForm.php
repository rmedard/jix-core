<?php


namespace Drupal\jix_migrator\Form;


use DateTime;
use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Exception;

/**
 * Provides a form for deleting a batch_import_example entity.
 *
 * @ingroup batch_import_news
 */
class ApplicationsMigratorForm extends FormBase
{

  private string $channel;
  private int $counter;

  public function __construct()
  {
    $this->channel = 'jix_migrator';
  }

  public function getFormId(): string
  {
    return 'batch_news_migrator_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Proceed',
      ),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $batch = [
      'title' => t('Importing applications'),
      'operations' => [],
      'init_message' => t('Import applications is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.')
    ];

    if (($handle = fopen("public://applications.csv", "r")) !== FALSE) {
      $rowsCount = 0;
      while (($row_data = fgetcsv($handle, 0, "|", '"')) !== FALSE) {
        ++$rowsCount;
        $batch['operations'][] = [['\Drupal\jix_migrator\Form\ApplicationsMigratorForm', 'process'], [$row_data]];
      }
      batch_set($batch);
      $form_state->setRebuild(TRUE);
      fclose($handle);
      Drupal::messenger()->addMessage('Imported ' . $rowsCount . ' applications!');
    }

  }

  public static function process($item, &$context)
  {
    $channel = 'jix_migrator';
    $old_nid = $item[11];
    $job_id = 0;
    if (isset($old_nid)) {
      $job_id = Drupal::entityTypeManager()
        ->getStorage('node')->getQuery()
        ->condition('field_job_old_nid', intval($old_nid))
        ->execute();
      $job_id = reset($job_id);
    }

    $now = (new Datetime('now'))->format('YmdHis');
    $random_string = md5(self::generateRandomString(10));
    $extension = substr($item[2], strripos($item[2], '.'));
    $cv_filename = $random_string . '_' . $now . '_cv_file' . $extension;

    $cv_data = file_get_contents($item[2]);
    $cv_file = file_save_data($cv_data, 'public://webform/default_job_application_form/' . $cv_filename, FileSystemInterface::EXISTS_REPLACE);
    $languages = self::getLanguages($item[17]);

    $other_files_file = '';
    if (isset($item[1])) {
      $other_files_filename = '';
      $other_files_data = file_get_contents($item[2]);
      $other_files_file = file_save_data($other_files_data, 'public://webform/default_job_application_form/' . $other_files_filename, FileSystemInterface::EXISTS_REPLACE);
    }

    $study = isset($item[8]) ? self::getTerm('category', $item[8]) : '';
    $degree = isset($item[10]) ? self::getTerm('education_level', $item[10]) : '';
    $values = [
      'webform_id' => 'default_job_application_form',
      'data' => [
        'field_application_sync' => trim($item[14]),
        'job_application_cover' => [
          'format' => 'basic_html',
          'value' => $item[3]
        ],
        'job_application_cv_resume_file' => $cv_file !== false ? $cv_file->id() : [],
        'job_application_cv_resume_title' => $item[4],
        'job_application_dob' => '',
        'job_application_email' => trim($item[6]),
        'job_application_experience' => self::getCareerExp(intval($item[7]))->id(),
        'job_application_field_study' => $study instanceof TermInterface ? $study->id() : '',
        'job_application_firstname' => $item[9],
        'job_application_highest_degree' => $degree instanceof TermInterface ? $degree->id() : '',
        'job_application_job' => $job_id === 0 ? '' : $job_id,
        'job_application_lastname' => $item[12],
        'job_application_nationality' => trim($item[13]),
        'job_application_other_files' => empty($other_files_file) ? '' : $other_files_file->id(),
        'job_application_sex' => trim($item[15]),
        'job_application_spoken_languages' => [
          'chinese' => array_key_exists('chinese', $languages) ? $languages['chinese'] : 'none',
          'english' => array_key_exists('english', $languages) ? $languages['english'] : 'none',
          'french' => array_key_exists('french', $languages) ? $languages['french'] : 'none',
          'german' => array_key_exists('german', $languages) ? $languages['german'] : 'none',
          'italian' => array_key_exists('italian', $languages) ? $languages['italian'] : 'none',
          'kinyarwanda' => array_key_exists('kinyarwanda', $languages) ? $languages['kinyarwanda'] : 'none',
          'spanish' => array_key_exists('spanish', $languages) ? $languages['spanish'] : 'none',
          'swahili' => array_key_exists('swahili', $languages) ? $languages['swahili'] : 'none',
        ],
        'job_application_telephone' => $item[18],
      ],
    ];

    /** @var WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $errors = WebformSubmissionForm::validateWebformSubmission($webform_submission);
    if (!empty($errors)) {
      Drupal::logger($channel)->error(Json::encode($errors));
    } else {
      WebformSubmissionForm::submitWebformSubmission($webform_submission);
    }


    $context['results'][] = $item;
    $context['message'] = t('Processed @count applications', array('@count' => count($context['results'])));
    Drupal::logger($channel)->debug($context['message']);
  }

  private static function generateRandomString($length): string
  {
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($permitted_chars);
    $random_string = '';
    try {
      for ($i = 0; $i < $length; $i++) {
        $random_character = $permitted_chars[random_int(0, $input_length - 1)];
        $random_string .= $random_character;
      }
    } catch (Exception $e) {
      Drupal::logger('jix_migrator')->error('Utils Random Generator Failed: ' . $e->getMessage());
    }
    return $random_string;
  }

  private static function getLanguages(string $langString): array
  {
    $languages = explode(',', $langString);
    $langSet = [];
    foreach ($languages as $language) {
      $values = explode(':', $language);
      $level = strtolower(trim($values[1]));
      $langSet[strtolower(trim($values[0]))] = $level === 'no' ? 'none' : $level;
    }
    return $langSet;
  }

  private static function getCareerExp(int $term_id): EntityInterface|EntityBase|Term|null
  {
    return match ($term_id) {
      43 => Term::load(58),
      44 => Term::load(59),
      45 => Term::load(60),
      46 => Term::load(62),
      47 => Term::load(61),
      48 => Term::load(56),
      49 => Term::load(57),
      default => Term::load(63),
    };
  }

  private static function getTerm(string $vocabulary, string $value): TermInterface|string
  {
    try {
      $term = Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vocabulary, 'name' => $value]);
      $term = reset($term);
      if ($term instanceof TermInterface) {
        return $term;
      }
    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      Drupal::logger('jix_migrator')->error('Term storage error: ' . $e->getMessage());
    }
    return '';
  }
}
