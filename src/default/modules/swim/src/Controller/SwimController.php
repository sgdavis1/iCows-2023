<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\swim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Url; 
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SwimController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'swims',
      '#test_var' => $this->t('Test Value'),
    ];
  }

  public function drop_out($id) {
    $query = \Drupal::database()->select('icows_swims', 'i');
  
    // Add extra detail to this query object: a condition, fields and a range
    $query->condition('i.swim_id', $id, '=');

    $query->fields('i', ['uid', 'title']);
    $swim = $query->execute()->fetchAll()[0];

    $num_deleted = \Drupal::database()->delete('icows_attendees')
    ->condition('swim_id', $id)
    ->condition('uid', \Drupal::currentUser()->id())
    ->execute();

    $attendee = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_first_name->value . " " .  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_last_name->value;
    log_email($swim->uid, sprintf('%s has dropped out of your hosted swim %s.', $attendee, $swim->title));

    $response = new RedirectResponse(Url::fromRoute('swim.show', ['id' => $id])->toString());
    $response->send();
    return;
  }

  public function show($id) {
  verify_swim_exists($id);
  # Use dynamic queries instead: https://www.drupal.org/docs/7/api/database-api/dynamic-queries/introduction-to-dynamic-queries
  $query = \Drupal::database()->select('icows_swims', 'i');
  
  // Add extra detail to this query object: a condition, fields and a range
  $query->condition('i.swim_id', $id, '=');

  $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
  $swim = $query->execute()->fetchAll()[0];
  $date = new DrupalDateTime($swim->date_time, 'UTC');

  // get swimmers
  $attendee_swimmer_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_swimmer_query->condition('a.swim_id', $id, '=');
  $attendee_swimmer_query->condition('a.swimmer', 1, '=');

  $attendee_swimmer_query->fields('a', ['uid', 'kayaker', 'number_of_kayaks', 'estimated_pace', 'distance']);
  $swimmers = $attendee_swimmer_query->execute()->fetchAll();

  $current_user_id = \Drupal::currentUser()->id();
  $signed_up = false;
  

  foreach ($swimmers as &$swimmer) {
    $swimmer->name = \Drupal\user\Entity\User::load($swimmer->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($swimmer->uid)->field_last_name->value;
    $swimmer->picture = getProfilePicture($swimmer->uid);
    $swimmer->email = \Drupal\user\Entity\User::load($swimmer->uid)->getEmail();
    $swimmer->username = \Drupal\user\Entity\User::load($swimmer->uid)->getDisplayName();
    if ($swimmer->kayaker == 1) {
      $swimmer->kayaker = "Yes";
    } else {
      $swimmer->kayaker = "No";
    }
    if ($swimmer->uid == $current_user_id) {
      $signed_up = true;
    }
  }

  // get kayakers
  $attendee_kayaker_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_kayaker_query->condition('a.swim_id', $id, '=');
  $attendee_kayaker_query->condition('a.swimmer', 0, '=');
  $attendee_kayaker_query->condition('a.kayaker', 1, '=');


  $attendee_kayaker_query->fields('a', ['uid', 'number_of_kayaks']);
  $kayakers = $attendee_kayaker_query->execute()->fetchAll();

  foreach ($kayakers as &$kayaker) {
    $kayaker->name = \Drupal\user\Entity\User::load($kayaker->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($kayaker->uid)->field_last_name->value;
    $kayaker->picture = getProfilePicture($kayaker->uid);
    $kayaker->email = \Drupal\user\Entity\User::load($kayaker->uid)->getEmail();
    $kayaker->username = \Drupal\user\Entity\User::load($kayaker->uid)->getDisplayName();
    if ($kayaker->uid == $current_user_id) {
      $signed_up = true;
    }
  }  

  return [
    '#theme' => 'show',
    '#id' => $id,
    '#title' => $swim->title,
    '#description' => $swim->description,
    '#locked' => $swim->locked,
    '#date_time' => getFormattedDate($date),
    '#uid' => $swim->uid,
    '#swimmers' => $swimmers,
    '#kayakers' => $kayakers,
    '#signed_up' => $signed_up,
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
  /**
  * Export a CSV of data.
  */
  public function build($id) {
    verify_swim_exists($id);
  
    // Start using PHP's built in file handler functions to create a temporary file.
    $handle = fopen('php://temp', 'w+');
  
    // Set up the header that will be displayed as the first line of the CSV file.
    // Blank strings are used for multi-cell values where there is a count of
    // the "keys" and a list of the keys with the count of their usage.
    $header = [
      'Status',
      'Name',
      'Username',
      'Email',
      'RSVP',
      'Boats',
      'Pace',
      'Distance',
      'Kayak?',
    ];
  
    // Add the header as the first line of the CSV.
    fputcsv($handle, $header);
    
    // get swimmers
    $attendee_query = \Drupal::database()->select('icows_attendees', 'a');
    $attendee_query->condition('a.swim_id', $id, '=');
  
    $attendee_query->fields('a', ['uid', 'swimmer', 'kayaker', 'number_of_kayaks', 'estimated_pace']);
    $attendees = $attendee_query->execute()->fetchAll();
  
    $current_user_id = \Drupal::currentUser()->id();    
  
    foreach ($attendees as &$attendee) {
      $user =  \Drupal\user\Entity\User::load($attendee->uid);
      $csv_row = [];
  
      $csv_row["status"] = ($attendee->swimmer == 1 ? "Swimmer" : "Kayaker");
      $csv_row["name"] = $user->field_first_name->value . " " . $user->field_last_name->value;
      $csv_row["username"] = $user->getDisplayName();
      $csv_row["email"] = $user->getEmail();
      $csv_row["rsvp"] = "TODO";
      $csv_row["boats"] = $attendee->number_of_kayaks;
  
      if ($attendee->swimmer == 1) {
        $csv_row["pace"] = $attendee->estimated_pace;
        $csv_row["distance"] = "TODO"; // $attendee->distance;
        if ($attendee->kayaker == 1) {
          $csv_row["kayaker"] = "Yes";
        } else {
          $csv_row["kayaker"] = "No";
        }
      } else {
        $csv_row["pace"] = "";
        $csv_row["distance"] = "";
        $csv_row["kayaker"] = "";
      }
  
      // Add the data we exported to the next line of the CSV>
      fputcsv($handle, array_values($csv_row));
    }
  
    // Reset where we are in the CSV.
    rewind($handle);
    
    // Retrieve the data from the file handler.
    $csv_data = stream_get_contents($handle);
  
    // Close the file handler since we don't need it anymore.  We are not storing
    // this file anywhere in the filesystem.
    fclose($handle);
  
    // This is the "magic" part of the code.  Once the data is built, we can
    // return it as a response.
    $response = new Response();
  
    // By setting these 2 header options, the browser will see the URL
    // used by this Controller to return a CSV file called "article-report.csv".
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="icows-attendee-list.csv"');
  
    // This line physically adds the CSV data we created 
    $response->setContent($csv_data);
  
    return $response;
  }
}

function getProfilePicture($user_id) {
  if (\Drupal\user\Entity\User::load($user_id)->user_picture->entity == NULL) {
    $field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
    $default_image = $field->getSetting('default_image');
    $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid']);
    return $file->getFileUri();
  }
  else {
    return \Drupal\user\Entity\User::load($user_id)->user_picture->entity->getFileUri();
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
    case 0:
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

function verify_swim_exists($id) {
  $query = \Drupal::database()->select('icows_swims', 'i');
    $query->condition('i.swim_id', $id, '=');

  $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
  $swim = $query->execute()->fetchAll()[0];
  if (!$swim) {
    $response = new RedirectResponse("/");
    $response->send();
    return;
  }
}