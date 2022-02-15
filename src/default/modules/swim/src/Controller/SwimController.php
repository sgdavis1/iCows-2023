<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\swim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SwimController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'swims',
      '#test_var' => $this->t('Test Value'),
    ];
  }

  public function show($id) {

  verify_swim_exists($id);
  # Use dynamic queries instead: https://www.drupal.org/docs/7/api/database-api/dynamic-queries/introduction-to-dynamic-queries
  $query = \Drupal::database()->select('icows_swims', 'i');
  
  // Add extra detail to this query object: a condition, fields and a range
  $query->condition('i.swim_id', $id, '=');

  $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
  $swim = $query->execute()->fetchAll()[0];
  $date = new DrupalDateTime($swim->date_time, 'UTC');

  // get swimmers
  $attendee_swimmer_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_swimmer_query->condition('a.swim_id', $id, '=');
  $attendee_swimmer_query->condition('a.swimmer', 1, '=');

  $attendee_swimmer_query->fields('a', ['uid', 'kayaker', 'number_of_kayaks', 'estimated_pace']);
  $swimmers = $attendee_swimmer_query->execute()->fetchAll();
  

  foreach ($swimmers as &$swimmer) {
    $swimmer->name = \Drupal\user\Entity\User::load($swimmer->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($swimmer->uid)->field_last_name->value;
    $swimmer->picture = getProfilePicture($swimmer->uid);
    $swimmer->email = \Drupal\user\Entity\User::load($swimmer->uid)->getEmail();
    $swimmer->username = \Drupal\user\Entity\User::load($swimmer->uid)->getDisplayName();
    if ($swimmer->kayaker == 1) {
      $swimmer->kayaker = "Yes";
    } else {
      $swimmer->kayaker = "No";
    }
  }

  // get kayakers
  $attendee_kayaker_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_kayaker_query->condition('a.swim_id', $id, '=');
  $attendee_kayaker_query->condition('a.swimmer', 0, '=');
  $attendee_kayaker_query->condition('a.kayaker', 1, '=');


  $attendee_kayaker_query->fields('a', ['uid', 'number_of_kayaks']);
  $kayakers = $attendee_kayaker_query->execute()->fetchAll();

  foreach ($kayakers as &$kayaker) {
    $kayaker->name = \Drupal\user\Entity\User::load($kayaker->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($kayaker->uid)->field_last_name->value;
    $kayaker->picture = getProfilePicture($kayaker->uid);
    $kayaker->email = \Drupal\user\Entity\User::load($kayaker->uid)->getEmail();
    $kayaker->username = \Drupal\user\Entity\User::load($kayaker->uid)->getDisplayName();
  }

  return [
    '#theme' => 'show',
    '#id' => $id,
    '#title' => $swim->title,
    '#description' => $swim->description,
    '#locked' => $swim->locked,
    '#date_time' => getFormattedDate($date),
    '#uid' => $swim->uid,
    '#swimmers' => $swimmers,
    '#kayakers' => $kayakers,
  ];    
  }

  public function edit($id) {
    return [
      '#theme' => 'edit',
      '#id' => $id,
    ];
  }

  public function attendance_list($id) {
    return [
      '#theme' => 'attendance_list',
      '#id' => $id,
    ];
  }
}

function getProfilePicture($user_id) {
  if (\Drupal\user\Entity\User::load($user_id)->user_picture->entity == NULL) {
    $field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
    $default_image = $field->getSetting('default_image');
    $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid']);
    return $file->getFileUri();
  }
  else {
    return \Drupal\user\Entity\User::load($user_id)->user_picture->entity->getFileUri();
  }
}

function getFormattedDate($date) {
  $day_index = DateHelper::dayOfWeek($date);
  $day = "";
  switch ($day_index) {
    case 1:
      $day = "Monday";
      break;
    case 2:
      $day = "Tuesday";
      break;
    case 3:
      $day = "Wednesday";
      break;
    case 4:
      $day = "Thursday";
      break;
    case 5:
      $day = "Friday";
      break;
    case 6:
      $day = "Saturday";
      break;
    case 0:
      $day = "Sunday";
      break;
    default:
      $day = "Unknown";
      break;
  }

  $month_index = intval($date->format('m'));
  $month = strval(DateHelper::monthNames()[$month_index]);
  return $day . ", " . $month . $date->format(' d, Y - g:ia');
}

function verify_swim_exists($id) {
  $query = \Drupal::database()->select('icows_swims', 'i');
    $query->condition('i.swim_id', $id, '=');

  $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
  $swim = $query->execute()->fetchAll()[0];
  if (!$swim) {
    $response = new RedirectResponse("/");
    $response->send();
    return;
  }
}