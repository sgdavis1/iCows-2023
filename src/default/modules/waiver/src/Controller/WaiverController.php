<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\waiver\Controller;

use Drupal\Core\Controller\ControllerBase;


class WaiverController extends ControllerBase {

  public function content() {
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    $query = $userStorage->getQuery();
    $uids = $query->condition('roles', 'swimmer')->execute();
    
    $approved_users = $userStorage->loadMultiple($uids);

    $approved_users_values = array();
  
    foreach($approved_users as $user){
        $name = $user->field_first_name->value . " " . $user->field_last_name->value;
        $user_values = ["name" => $name,
                        "email" => $user->getEmail(),
                        "username" => "username",
                        "picture" => "picture"];
        array_push($approved_users_values, $user_values);
    }

    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    $query = $userStorage->getQuery();
    $uids = $query->condition('roles', 'swimmer', '<>')->execute();
    
    $pending_users = $userStorage->loadMultiple($uids);

    $pending_users_values = array();
  
    foreach($pending_users as $user){
        $name = $user->field_first_name->value . " " . $user->field_last_name->value;
        $user_values = ["name" => $name,
                        "email" => $user->getEmail(),
                        "username" => "username",
                        "picture" => "picture"];
        array_push($pending_users_values, $user_values);
    }

    return [
        '#theme' => 'waivers',
        '#approved_users' => $approved_users_values,
        '#pending_users' => $pending_users_values
      ]; 
  }
}
