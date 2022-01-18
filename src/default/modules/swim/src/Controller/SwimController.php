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
      '#theme' => 'swim',
      '#test_var' => $this->t('Test Value'),
    ];
 
  }
}