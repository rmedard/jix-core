<?php


namespace Drupal\jix_notifier\Utils;


interface NotificationType
{
  const NEW_JOB_SAVED = 'new_job_saved';
  const NEW_JOB_PUBLISHED = 'new_job_published';
  const CREDIT_THRESHOLD_REACHED = 'credit_threshold_reached';
}
