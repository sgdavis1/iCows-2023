<?php

namespace Drupal\calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
 
class CalendarController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'calendar',
      '#test_var' => $this->t('Test Value'),
    ];
  }
}