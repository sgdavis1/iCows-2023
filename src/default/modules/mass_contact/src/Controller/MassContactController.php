<?php
/**
 * @file
 * Contains \Drupal\mass_contact\Controller\MassContactController.
 */
 
namespace Drupal\mass_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
 
class MassContactController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'mass_contact',
      '#test_var' => $this->t('Test Value'),
    ];
 
  }
}