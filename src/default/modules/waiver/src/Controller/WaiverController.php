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
use Drupal\Core\Archiver\Zip;
use Drupal\node\Entity\Node;
use Drupal\Core\StreamWrapper\PublicStream;


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
    $uids = $query->condition('field_current_waiver_id', -1, '<>')
                  ->execute();
    
    $pending_users = $userStorage->loadMultiple($uids);

    $pending_users_values = array();
  
    foreach($pending_users as $user){
        if (!in_array("swimmer", $user->getRoles())) {
          $name = $user->field_first_name->value . " " . $user->field_last_name->value;
          $user_values = ["name" => $name,
                          "email" => $user->getEmail(),
                          "username" => $user->getDisplayName(),
                          "picture" => $user->id(),
                          "waiver_id" => $user->field_current_waiver_id->value];
          array_push($pending_users_values, $user_values);
        }
    }

    $query = $userStorage->getQuery();
    $uids = $query->condition('field_current_waiver_id', -1, '=')
        ->execute();
    $not_submitted_users = $userStorage->loadMultiple($uids);

    $not_submitted_users_values = array();
    foreach($not_submitted_users as $user){
      if (!in_array("swimmer", $user->getRoles())) {
          $name = $user->field_first_name->value . " " . $user->field_last_name->value;
          $user_values = ["name" => $name,
              "email" => $user->getEmail(),
              "username" => $user->getDisplayName(),
              "picture" => $user->id(),];
          array_push($not_submitted_users_values, $user_values);
      }
    }

    return [
        '#theme' => 'waivers',
        '#approved_users' => $approved_users_values,
        '#pending_users' => $pending_users_values,
        '#not_submitted_users' => $not_submitted_users_values,
        '#cache' => array('max-age' => 0),
      ]; 
  }

  public function approvalPage($id){
    $params = Url::fromUri("internal:/waiver/view")->getRouteParameters();
    $entity_type = key($params);
    $node = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
    $node->set('field_waiver_id', $id);
    $query = \Drupal::database()->select('icows_waivers', 'i');
    $query->condition('i.waiver_id', $id, '=');
    $query->fields('i', ['waiver_url']);
    $waivers = $query->execute()->fetchAll();

    if ($waivers == NULL) {
        $node->set('field_waiver_display', NULL);
    }
    else {
        $waiver = $waivers[0];
        $file = File::load($waiver->waiver_url);
        $file->setPermanent();
        $file->save();
        $node->set('field_waiver_display', $file);
    }
    $node->save();

    $response = new RedirectResponse(Url::fromUri("internal:/waiver/view")->toString());
    $response->send();

    return;
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
      $user = \Drupal\user\Entity\User::load($uid);
      $user->addRole('swimmer');
      $user->save();
      $response = new RedirectResponse(Url::fromRoute('waiver.content')->toString());
      $response->send();
  }

  public function exportWaivers() {
    $tmp_file = tmpfile(); //temp file in memory
    $tmp_location = stream_get_meta_data($tmp_file)['uri']; //"location" of temp file 

    $zip = new Zip($tmp_location);
    $zip = $zip->getArchive();

    // https://fivejars.com/blog/how-zip-files-drupal-8
    $query = \Drupal::database()->select('icows_waivers', 'i');
    $query->condition('i.approved', 1, '=');
    $query->fields('i', ['waiver_url']);
    $waivers = $query->execute()->fetchAll();

    foreach ($waivers as &$waiver) {
      $uri = File::load($waiver->waiver_url)->getFileUri();;
      $url = \Drupal\Core\Url::fromUri(file_create_url($uri))->toString();
      $dir_uri = \Drupal::service('stream_wrapper_manager')->getViaUri($uri)->getDirectoryPath();
      $base_name = basename($url);
      $file_path = $dir_uri . "/files/" . $base_name;

      if (file_exists($file_path)) {
        $zip->addFile($file_path, "waivers/" . $base_name);
      } else {
        \Drupal::messenger()->addError(t("PDF missing for : " . basename($url)));
      }
    }
    
    // $zip->addFile(PublicStream::basePath() . "/test.pdf", "/waivers/test.pdf");

    $zip_data = stream_get_contents($tmp_file);

    // All files are added, so close the zip file.
    $zip->close();
    $handle = fopen($tmp_location, 'r');
    $csv_data = stream_get_contents($handle);
  
    // Close the file handler since we don't need it anymore.  We are not storing
    // this file anywhere in the filesystem.
    fclose($handle);

    $response = new Response();

    // By setting these 2 header options, the browser will see the URL
    // used by this Controller to return a CSV file called "article-report.csv".
    $response->headers->set('Content-Type', 'application/zip'); //'application/octet-stream');
    $response->headers->set('Content-Disposition', 'attachment; filename="waivers.zip"');
  
    $response->setContent($csv_data);
  
    return $response;




    // https://fivejars.com/blog/how-zip-files-drupal-8
    // $query = \Drupal::database()->select('icows_waivers', 'i');
    // $query->condition('i.approved', 1, '=');
    // $query->fields('i', ['waiver_url']);
    // $waivers = $query->execute()->fetchAll();

    // foreach ($waivers as &$waiver) {
    //   $file = File::load($waiver->waiver_url);
    //   if ($file != NULL) {
    //     $zip->add(PublicStream::basePath() . "/test.pdf");
    //     \Drupal::messenger()->addError(t("Good: " . $waiver->waiver_url . " " . $file->toUrl()));
    //   } else {
    //     \Drupal::messenger()->addError(t("Bad: " . $waiver->waiver_url));
    //   }
    // }

    $zip->addFile(PublicStream::basePath() . "/test.pdf", "/waivers/test.pdf");

    $zip_data = stream_get_contents($tmp_file);

    // All files are added, so close the zip file.
    $zip->close();
    $handle = fopen($tmp_location, 'r');
    $csv_data = stream_get_contents($handle);
  
    // Close the file handler since we don't need it anymore.  We are not storing
    // this file anywhere in the filesystem.
    fclose($handle);

    $response = new Response();

    // By setting these 2 header options, the browser will see the URL
    // used by this Controller to return a CSV file called "article-report.csv".
    $response->headers->set('Content-Type', 'application/zip'); //'application/octet-stream');
    $response->headers->set('Content-Disposition', 'attachment; filename="waivers.zip"');
  
    $response->setContent($csv_data);
  
    return $response;
  }
}

//   public function exportWaivers() {
//     $tmp_file = tmpfile(); //temp file in memory
//     $tmp_location = stream_get_meta_data($tmp_file)['uri']; //"location" of temp file 

//     $zip = new Zip($tmp_location);
//     $zip = $zip->getArchive();

//     // https://fivejars.com/blog/how-zip-files-drupal-8
//     $query = \Drupal::database()->select('icows_waivers', 'i');
//     $query->condition('i.approved', 1, '=');
//     $query->fields('i', ['waiver_url']);
//     $waivers = $query->execute()->fetchAll();

//     // foreach ($waivers as &$waiver) {
//     //   $file = File::load($waiver->waiver_url);
//     //   if ($file != NULL) {
//     //     $zip->add(PublicStream::basePath() . "/test.pdf");
//     //     \Drupal::messenger()->addError(t("Good: " . $waiver->waiver_url . " " . $file->toUrl()));
//     //   } else {
//     //     \Drupal::messenger()->addError(t("Bad: " . $waiver->waiver_url));
//     //   }
//     // }

//     $zip->addFile(PublicStream::basePath() . "/test.pdf", "/waivers/test.pdf");

//     $zip_data = stream_get_contents($tmp_file);

//     // All files are added, so close the zip file.
//     $zip->close();
//     $handle = fopen($tmp_location, 'r');
//     $csv_data = stream_get_contents($handle);
  
//     // Close the file handler since we don't need it anymore.  We are not storing
//     // this file anywhere in the filesystem.
//     fclose($handle);

//     $response = new Response();

//     // By setting these 2 header options, the browser will see the URL
//     // used by this Controller to return a CSV file called "article-report.csv".
//     $response->headers->set('Content-Type', 'application/zip'); //'application/octet-stream');
//     $response->headers->set('Content-Disposition', 'attachment; filename="waivers.zip"');
  
//     $response->setContent($csv_data);
  
//     return $response;
//   }
// }

