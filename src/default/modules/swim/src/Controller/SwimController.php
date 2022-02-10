<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\swim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateHelper;

class SwimController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'swims',
      '#test_var' => $this->t('Test Value'),
    ];
  }

  public function show($id) {

  # Use dynamic queries instead: https://www.drupal.org/docs/7/api/database-api/dynamic-queries/introduction-to-dynamic-queries
  $query = \Drupal::database()->select('icows_swims', 'i');
  
  // Add extra detail to this query object: a condition, fields and a range
  $query->condition('i.swim_id', $id, '=');
   $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
   $query->range(0, 50);
  $result = $query->execute()->fetchAll()[0];
  $date = new DrupalDateTime($result->field_date, 'UTC');

  return [
    '#theme' => 'show',
    '#id' => $id,
    '#title' => $result->title,
    '#description' => $result->description,
    '#locked' => $result->locked,
    '#date_time' => getFormattedDate($date),
    '#uid' => $result->uid,
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
    case 7:
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