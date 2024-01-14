<?php


namespace Drupal\jix_interface\Plugin\views\field;


use Drupal;
use Drupal\node\NodeInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Class JobSubmissionsCountViewsField
 * @package Drupal\jix_interface\Plugin\views\field
 *
 * @ingroup views_field_handlers
 * @ViewsField("job_submissions_count_views_field")
 */
class JobSubmissionsCountViewsField extends FieldPluginBase
{

  protected string $currentDisplay;

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL): void
  {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  public function query()
  {
    // Empty because this field does not need to be used in query.
  }

  protected function defineOptions(): array
  {
    $options = parent::defineOptions();
    $options['hide_alter_empty'] = ['default' => false];
    return $options;
  }

  public function render(ResultRow $values): int
  {
    $job = $values->_entity;
    if (!is_null($job) and $job instanceof NodeInterface and $job->bundle() === 'job') {
      $count = Drupal::database()
        ->select('webform_submission_data', 'wsd')
        ->fields('wsd', array('sid'))
        ->condition('wsd.webform_id', 'default_job_application_form')
        ->condition('wsd.name', 'job_application_job')
        ->condition('wsd.value', $job->id())
        ->countQuery()
        ->execute()
        ->fetchField();
      return intval($count);
    }
    return 0;
  }
}
