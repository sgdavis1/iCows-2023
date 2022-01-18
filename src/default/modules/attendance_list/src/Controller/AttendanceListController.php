<?php

namespace Drupal\attendance_list\Controller;

use Drupal\Core\Controller\ControllerBase;
 
class AttendanceListController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'attendance_list',
      '#test_var' => $this->t('Test Value'),
    ];
  }
}