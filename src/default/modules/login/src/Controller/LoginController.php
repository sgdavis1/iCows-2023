<?php
/**
 * @file
 * Contains \Drupal\login\Controller\LoginController.
 */
 
namespace Drupal\login\Controller;

use Drupal\Core\Controller\ControllerBase;
 
class LoginController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'login',
      '#test_var' => $this->t('Test Value'),
    ];
 
  }
}