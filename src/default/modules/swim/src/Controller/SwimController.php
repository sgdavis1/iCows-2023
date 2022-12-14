<?php
/**
 * @file
 * Contains \Drupal\swim\Controller\SwimController.
 */
 
namespace Drupal\swim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SwimController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'swims',
      '#test_var' => $this->t('Test Value'),
      '#cache' => array('max-age' => 0),
    ];
  }

  public function admins_info() {
    return [
        '#theme' => 'admins_info',
        '#cache' => array('max-age' => 0),
    ];
  }

  public function change_auto_grouping($id) {
      //get the current value
      $query = \Drupal::database()->select('icows_swims', 'i');
      $query->condition('i.swim_id', $id, '=');
      $query->fields('i', ['auto_grouping']);
      $res = $query->execute()->fetchAll()[0];

      //get field
      $current_value = $res->auto_grouping;

      //if it's a string, convert to int
      if ($current_value == '0') {
          $current_value = 0;
      } elseif ($current_value == '1') {
          $current_value = 1;
      }

      //switch 0 --> 1 or 1--> 0
      $current_value = abs($current_value - 1);


      //set it to the opposite of the current value
      $database = \Drupal::database();
      $database->update('icows_swims')->fields(array(
          'auto_grouping' => $current_value,
      ))->condition('swim_id', $id, '=')->execute();

      $response = new RedirectResponse(Url::fromRoute('swim.show', ['id' => $id])->toString());
      $response->send();
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
    log_swim_change($id, $swim->uid, sprintf('%s has dropped out of your hosted swim %s.', $attendee, $swim->title));


      //get necessary info for the grouping algo
      $swim_id = $id;

      //get num of kayakers (this will be the number of groups)
      $query = \Drupal::database()->select('icows_attendees', 'i');
      $query->condition('i.swim_id', $swim_id, '=');
      $query->condition('i.kayaker', 1, '=');
      $query->condition('i.swimmer', 0, '=');
      $query->fields('i', ['group']);
      $kayakers = $query->execute()->fetchAll();

      $num_kayakers = 0;
      foreach ($kayakers as &$kayaker) {
          $num_kayakers += 1;
      }

      //see if auto-grouping is on
      $query = \Drupal::database()->select('icows_swims', 'i');
      $query->condition('i.swim_id', $swim_id, '=');
      $query->fields('i', ['auto_grouping']);
      $swim_grouping = $query->execute()->fetchAll()[0];

      $grouping_setting = $swim_grouping->auto_grouping;

      if ($grouping_setting == '1' || $grouping_setting == 1) {
          $grouping_setting = true;
      } else {
          $grouping_setting = false;
      }

      //if more than 1 group is available, get the swimmers for grouping
      if ($num_kayakers > 1 && $grouping_setting) {
          $query = \Drupal::database()->select('icows_attendees', 'i');
          $query->condition('i.swim_id', $swim_id, '=');
          $query->condition('i.swimmer', 1, '=');
          $query->fields('i', ['uid', 'estimated_pace', 'distance', 'group']);
          $swimmers = $query->execute()->fetchAll();

          //create array of arrays where each array is the uid, and pace (in seconds) for 1 km
          $swimmers_info = array();

          //get number of swimmers for swim
          $swimmer_count = 0;
          foreach ($swimmers as &$swimmer) {
              $swimmer_count += 1;
              $new_pace = getStandardPace($swimmer->estimated_pace, $swimmer->distance);
              $swimmer_info = array($swimmer->uid, $new_pace);
              array_push($swimmers_info, $swimmer_info);
          }

          if ($swimmer_count > 0) {
            $group_num =  $num_kayakers / $swimmer_count;
            $remainder = $num_kayakers % $swimmer_count;

            //sort by pace from fastest (shortest num of seconds to swim 1km) to slowest (longest time)
            usort($swimmers_info, function ($swimmer1, $swimmer2) {
                return $swimmer1[1] <=> $swimmer2[1];
            });

            //call the grouping algorithm for re-grouping
            groupSwimmers($swim_id, $swimmers_info, $num_kayakers, $group_num, $remainder);
        }
      }


    $response = new RedirectResponse(Url::fromRoute('swim.show', ['id' => $id])->toString());
    $response->send();
  }

  public function leaderboard($id) {
    verify_swim_exists($id);
    $current_user_id = \Drupal::currentUser()->id();

    $query = \Drupal::database()->select('icows_swims', 'i');
    $query->condition('i.swim_id', $id, '=');
    $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
    $query->range(0, 1);
    $swim = $query->execute()->fetchAll()[0];

    $date = new DrupalDateTime($swim->date_time, 'America/Chicago');
    $now = DrupalDateTime::createFromTimestamp(time());

    if ($date <= $now) {
      $attendee_swimmer_query = \Drupal::database()->select('icows_attendees', 'a');
      $attendee_swimmer_query->condition('a.swim_id', $id, '=');
      $attendee_swimmer_query->condition('a.swimmer', 1, '=');
      $attendee_swimmer_query->fields('a', ['uid']);
      $swimmers = $attendee_swimmer_query->execute()->fetchAll();

      foreach ($swimmers as &$swimmer) {
        $user = \Drupal\user\Entity\User::load($swimmer->uid);
        $swimmer->name = $user->field_first_name->value . " " . $user->field_last_name->value;
        $swimmer->picture = getProfilePicture($swimmer->uid);

        $query = \Drupal::database()->select('icows_stats', 'i');
        $query->condition('i.uid', $swimmer->uid, '=');
        $query->fields('i', ['swim_id', 'pace', 'distance']);
        $swim_stats = $query->execute()->fetchAll();
        if ($swim_stats[0]) {
          $swimmer->actual_pace = $swim_stats[0]->pace;
          $swimmer->actual_distance = $swim_stats[0]->distance;
        }
      }

      return [
        '#theme' => 'leaderboard',
        '#id' => $id,
        '#title' => $swim->title,
        '#description' => $swim->description,
        '#date_time' => getFormattedDate($date),
        '#swimmers' => $swimmers
      ];

    } else {
      \Drupal::messenger()->addError(t('The leaderboard cannot be shown for future swims.'));
      $response = new RedirectResponse(Url::fromRoute('swim.content')->toString() . '/' . $id);
      $response->send();
      return;
    }   
  }

  public function show($id) {
  verify_swim_exists($id);
  $query = \Drupal::database()->select('icows_swims', 'i');
  
  // Add extra detail to this query object: a condition, fields and a range
  $query->condition('i.swim_id', $id, '=');

  $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked', 'auto_grouping']);
  $swim = $query->execute()->fetchAll()[0];
  $date_of_swim = new DrupalDateTime($swim->date_time, 'America/Chicago');
  $now = DrupalDateTime::createFromTimestamp(time());
  $checked = intval($swim->auto_grouping);
  $past_swim = $date_of_swim < $now;

  // host id is $swim->uid
  $host_name = \Drupal\user\Entity\User::load($swim->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($swim->uid)->field_last_name->value;
  $host_picture = getProfilePicture($swim->uid);
  $host_email = \Drupal\user\Entity\User::load($swim->uid)->getEmail();

  // get swimmers
  $attendee_swimmer_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_swimmer_query->condition('a.swim_id', $id, '=');
  $attendee_swimmer_query->condition('a.swimmer', 1, '=');

  $attendee_swimmer_query->fields('a', ['uid', 'kayaker', 'number_of_kayaks', 'estimated_pace', 'distance', 'group']);
  $swimmers = $attendee_swimmer_query->execute()->fetchAll();

  $current_user_id = \Drupal::currentUser()->id();
  $signed_up = false;
  $isAdmin = false;
  $isSwimAdmin = false;
  $roles = \Drupal::currentUser()->getRoles();
  if(in_array( 'administrator', $roles)){
      $isAdmin = true;
  }
  if(in_array( 'swim_admin', $roles)){
      $isSwimAdmin = true;
  }

  $query = \Drupal::database()->select('icows_waivers', 'i');
  $query->condition('i.uid', $current_user_id, '=');
  $query->fields('i', ['approved']);
  $waiver = $query->execute()->fetchAll()[0];
  $isApproved = $waiver->approved;

  if(!in_array( 'swimmer', $roles)){
      $isApproved = false;
  }
  $isKayaker = false;
  $isSwimmer = false;
  foreach ($swimmers as &$swimmer) {
    $swimmer->name = \Drupal\user\Entity\User::load($swimmer->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($swimmer->uid)->field_last_name->value;
    $swimmer->picture = getProfilePicture($swimmer->uid);
    $swimmer->email = \Drupal\user\Entity\User::load($swimmer->uid)->getEmail();
    $swimmer->username = \Drupal\user\Entity\User::load($swimmer->uid)->getDisplayName();
    $date = new DrupalDateTime($swimmer->date_time, 'America/Chicago');
    $swimmer->rsvp = getFormattedDate($date);

    if ($swimmer->uid == $current_user_id) {
      $signed_up = true;
      $isSwimmer= true;
    }

    if ($swimmer->kayaker == 1) {
      $swimmer->kayaker = "Yes";
    } else {
      $swimmer->kayaker = "No";
    }
  }

  // get kayakers
  $attendee_kayaker_query = \Drupal::database()->select('icows_attendees', 'a');
  $attendee_kayaker_query->condition('a.swim_id', $id, '=');
  $attendee_kayaker_query->condition('a.swimmer', 0, '=');
  $attendee_kayaker_query->condition('a.kayaker', 1, '=');


  $attendee_kayaker_query->fields('a', ['uid', 'number_of_kayaks', 'group']);
  $kayakers = $attendee_kayaker_query->execute()->fetchAll();

  foreach ($kayakers as &$kayaker) {
    $kayaker->name = \Drupal\user\Entity\User::load($kayaker->uid)->field_first_name->value . " " . \Drupal\user\Entity\User::load($kayaker->uid)->field_last_name->value;
    $kayaker->picture = getProfilePicture($kayaker->uid);
    $kayaker->email = \Drupal\user\Entity\User::load($kayaker->uid)->getEmail();
    $kayaker->username = \Drupal\user\Entity\User::load($kayaker->uid)->getDisplayName();
    $date = new DrupalDateTime($kayaker->date_time, 'America/Chicago');
    $kayaker->rsvp = getFormattedDate($date);
    if ($kayaker->uid == $current_user_id) {
      $signed_up = true;
      $isKayaker= true;
    }
  }

  return [
    '#theme' => 'show',
    '#id' => $id,
    '#title' => $swim->title,
    '#description' => $swim->description,
    '#locked' => $swim->locked,
    '#date_time' => getFormattedDate($date_of_swim),
    '#uid' => $swim->uid,
    '#swimmers' => $swimmers,
    '#kayakers' => $kayakers,
    '#signed_up' => $signed_up,
    '#host_name' => $host_name,
    '#host_email' => $host_email,
    '#host_picture' => $host_picture,
    '#isAdmin' => $isAdmin,
    '#isSwimAdmin' => $isSwimAdmin,
    '#isApproved' => $isApproved,
    '#isKayaker' => $isKayaker,
    '#isSwimmer' => $isSwimmer,
    '#cache' => array('max-age' => 0),
    '#past_swim' => $past_swim,
    '#is_checked' => $checked,
  ];    
  }


  public function update_groupings($id) {
      //get swimmers for the swim
      $query = \Drupal::database()->select('icows_attendees', 'i');
      $query->condition('i.swim_id', $id, '=');
      $query->fields('i', ['uid', 'group']);
      $swimmers = $query->execute()->fetchAll();

      //number of swimmers
      $num_swimmers = count($swimmers);

      for ($i = 0; $i < $num_swimmers; $i++) {
          //user
          $swimmer_uid = $swimmers[$i]->uid;

          //get new group for swimmer
          $new_group = \Drupal::request()->query->get($swimmer_uid);

          //update swimmer's group
          $database = \Drupal::database();
          $database->update('icows_attendees')->fields(array(
              'group' => $new_group,
          ))->condition('icows_attendees.swim_id', $id, '=')
              ->condition('icows_attendees.uid', $swimmer_uid, '=')
              ->execute();
      }

      $response = new RedirectResponse(Url::fromRoute('swim.show', ['id' => $id])->toString());
      $response->send();
  }


  public function edit($id) {
    return [
      '#theme' => 'edit',
      '#id' => $id,
      '#cache' => array('max-age' => 0),
    ];
  }

  public function send_list($id) {
      verify_swim_exists($id);
      $email = \Drupal::config('swim.settings');
      send_list_to_dnr($id, $email->get('dnr_email'));
      $response = new RedirectResponse(Url::fromRoute('swim.content')->toString() . '/' . $id);
      $response->send();
  }

  public function attendance_list($id) {
    return [
      '#theme' => 'attendance_list',
      '#id' => $id,
      '#cache' => array('max-age' => 0),
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
  
    $attendee_query->fields('a', ['uid', 'swimmer', 'kayaker', 'number_of_kayaks', 'estimated_pace', 'distance']);
    $attendees = $attendee_query->execute()->fetchAll();
  
    $current_user_id = \Drupal::currentUser()->id();    
  
    foreach ($attendees as &$attendee) {
      $user =  \Drupal\user\Entity\User::load($attendee->uid);
      $csv_row = [];
  
      $csv_row["status"] = ($attendee->swimmer == 1 ? "Swimmer" : "Kayaker");
      $csv_row["name"] = $user->field_first_name->value . " " . $user->field_last_name->value;
      $csv_row["username"] = $user->getDisplayName();
      $csv_row["email"] = $user->getEmail();
      $date = new DrupalDateTime($attendee->date_time, 'America/Chicago');
      $csv_row["rsvp"] = getFormattedDate($date);
      $csv_row["boats"] = $attendee->number_of_kayaks;
  
      if ($attendee->swimmer == 1) {
        $csv_row["pace"] = $attendee->estimated_pace;
        $csv_row["distance"] = $attendee->distance;
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

  /**
  * Delete a swim
  */
  public function delete($id) {
      //get swim nid
      $query = \Drupal::database()->select('icows_swims', 'i');
      $query->condition('i.swim_id', $id, '=');
      $query->fields('i', ['nid']);
      $swim = $query->execute()->fetchAll()[0];

      //set values to filter
      $values = [
          'type' => 'swims',
          'field_swim_id' => $id,
      ];

      // Get the swim nodes
      $nodes = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties($values);

      // get specific node
      $node = $nodes[$swim->nid];

      //delete swim node
      if ($node) {
          $node->delete();
      }

      // delete database entries
      \Drupal::database()->delete('icows_swims')
          ->condition('swim_id', $id)
          ->execute();
      \Drupal::database()->delete('icows_attendees')
          ->condition('swim_id', $id)
          ->execute();
      \Drupal::database()->delete('icows_swim_groups')
          ->condition('swim_id', $id)
          ->execute();

      // redirect
      $response = new RedirectResponse(Url::fromRoute('swim.content')->toString());
      $response->send();
      return;
  }
}


