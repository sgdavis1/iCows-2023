<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\waiver\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


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
                        "username" => $user->getDisplayName(),
                        "picture" => getProfilePicture($user->id()),
                        "waiver_id" => $user->field_current_waiver_id->value];
        array_push($approved_users_values, $user_values);
    }

    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    $query = $userStorage->getQuery();
    $uids = $query->condition('roles', 'swimmer', '<>')
                  ->condition('field_current_waiver_id', -1, '<>')
                  ->execute();
    
    $pending_users = $userStorage->loadMultiple($uids);

    $pending_users_values = array();
  
    foreach($pending_users as $user){
        $name = $user->field_first_name->value . " " . $user->field_last_name->value;
        $user_values = ["name" => $name,
                        "email" => $user->getEmail(),
                        "username" => $user->getDisplayName(),
                        "picture" => $user->id(),
                        "waiver_id" => $user->field_current_waiver_id->value];
        array_push($pending_users_values, $user_values);
    }

    return [
        '#theme' => 'waivers',
        '#approved_users' => $approved_users_values,
        '#pending_users' => $pending_users_values
      ]; 
  }

  public function approvalPage($id){

      $query = \Drupal::database()->select('icows_waivers', 'i');

      // Add extra detail to this query object: a condition, fields and a range
      $query->condition('i.waiver_id', $id, '=');
      $query->fields('i', ['waiver_url']);
      $query->range(0, 1);
      $waiver = $query->execute()->fetchAll()[0];

      $file = File::load($waiver->waiver_url);
      $uri = $file->uri;

      $url = file_create_url($uri->value);

      return [
          '#theme' => 'waiver',
          '#waiver_url' => $url,
          '#id' => $id
      ];
  }
  public function approve($id){
      $database = \Drupal::database();
      $database->update('icows_waivers')->fields(array(
          'approved' => 1))
      ->condition('waiver_id', $id, '=')->execute();

      $query = \Drupal::database()->select('icows_waivers', 'i');
      $query->condition('i.waiver_id', $id, '=');
      $query->fields('i', ['uid']);
      $waiver = $query->execute()->fetchAll()[0];
      $uid = $waiver->uid;


  }
}
