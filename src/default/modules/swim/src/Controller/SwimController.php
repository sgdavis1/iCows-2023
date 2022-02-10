<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\swim\Controller;

use Drupal\Core\Controller\ControllerBase;
 
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
   $query->fields('i', ['uid', 'swim_id', 'field_date', 'title', 'description', 'locked']);
   $query->range(0, 50);
  $result = $query->execute()->fetchAll()[0];
  return [
    '#theme' => 'show',
    '#id' => $id,
    '#title' => $result->title,
    '#description' => $result->description,
    '#locked' => $result->locked,
    '#date_time' => $result->field_date,
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