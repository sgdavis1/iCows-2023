<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;
use \Drupal\Core\Field\EntityReferenceFieldItemList;
use \Drupal\node\Entity\Node;


/**
 * Implements an example form.
 */
class SwimStatisticsForm extends FormBase {
    public function getFormId() {
        return 'swim_statistics_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
        $current_user_id = \Drupal::currentUser()->id();
        if ($id != NULL) {
            verify_swim_exists($id);

            $query = \Drupal::database()->select('icows_swims', 'i');
        
            // Add extra detail to this query object: a condition, fields and a range
            $query->condition('i.swim_id', $id, '=');
            $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
            $query->range(0, 1);
            $swim = $query->execute()->fetchAll()[0];
    
            $date = new DrupalDateTime($swim->date_time);

            $now = DrupalDateTime::createFromTimestamp(time())->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
            
            $attendee_swimmer_query = \Drupal::database()->select('icows_attendees', 'a');
            $attendee_swimmer_query->condition('a.swim_id', $id, '=');
            $attendee_swimmer_query->condition('a.swimmer', 1, '=');
            $attendee_swimmer_query->condition('a.uid', $current_user_id, '=');
          
            $attendee_swimmer_query->fields('a', ['uid', 'kayaker', 'number_of_kayaks', 'estimated_pace', 'distance']);
            $swimmer = $attendee_swimmer_query->execute()->fetchAll()[0];

            $query = \Drupal::database()->select('icows_stats', 'i');
            $query->condition('i.swim_id', $id, '=');
            $query->condition('i.uid', $current_user_id, '=');
            $query->fields('i', ['uid']);
            $previously_submitted = count($query->execute()->fetchAll()) > 0;

            if ($date <= $now) {
                if ($swimmer) {
                    if (!$previously_submitted) {
                        $form['date_time'] = [
                            '#type' => 'datetime',
                            '#title' => $this->t('Date and Time'),
                            '#default_value' => $date,
                            '#required' => FALSE
                        ];
                
                        $form['pace'] = [
                            '#type' => 'textfield',
                            '#title' => $this->t('Pace'),
                            '#maxlength' => 10,
                            '#required' => TRUE
                        ];
                
                        $form['distance'] = [
                            '#type' => 'textfield',
                            '#title' => $this->t('Distance'),
                            '#maxlength' => 10,
                            '#required' => TRUE
                        ];
                
                        $form['swim_id'] = array(
                            '#value' => $id,
                            '#type' => 'hidden',
                        );

                        $form['swim_title'] = $swim->title;
                        $form['swim_date'] = getFormattedDate($date);
                
                        $form['actions']['#type'] = 'actions';
                        $form['actions']['submit'] = [
                            '#type' => 'submit',
                            '#value' => $this->t('Save'),
                            '#button_type' => 'primary',
                        ];
                    } else {
                        \Drupal::messenger()->addError(t('You have already submited statistics for this swim.'));
                    }
                } else {
                    \Drupal::messenger()->addError(t('You were not signed up as a swimmer for this swim and cannot submit statistics for it.'));
                }
            } else {
                \Drupal::messenger()->addError(t('Swim statistics can only be added for past swims.'));
            }
        }

        $form['#cache'] = ['max-age' => 0];
    
        $query = \Drupal::database()->select('icows_stats', 'i');
        $query->condition('i.uid', $current_user_id, '=');
        $query->fields('i', ['swim_id', 'pace', 'distance']);
        $swim_stats = $query->execute()->fetchAll();

        $average_pace_in_seconds = 0;

        $average_distance = 0;
        $counter = 0;
        
        foreach ($swim_stats as &$stat) {
            $counter += 1;
            $query = \Drupal::database()->select('icows_swims', 'i');
            $query->condition('i.swim_id', $stat->swim_id, '=');
            $query->fields('i', ['date_time', 'title']);
            $swim = $query->execute()->fetchAll()[0];

            $average_distance += intval($stat->distance);
            $pace = explode(":", $stat->pace);

            $average_pace_in_seconds += 60 * intval($pace[0]) + intval($pace[1]);
            
            $date = new DrupalDateTime($swim->date_time, 'CST');
            $stat->date =  getFormattedDate($date);
            $stat->swim_name = $swim->title;
        }

        if ($counter > 0) {
            $average_pace_in_seconds = $average_pace_in_seconds / $counter;
            $average_pace_minutes = intdiv($average_pace_in_seconds, 60);
            $average_pace_seconds = $average_pace_in_seconds % 60;

            $average_distance = round($average_distance/$counter, 2);
            
            if ($average_pace_seconds < 10) {
                $form['average_pace'] = strval($average_pace_minutes) . ":0" . strval($average_pace_seconds);
            } else {
                $form['average_pace'] = strval($average_pace_minutes) . ":" . strval($average_pace_seconds);
            }
            
            $form['average_distance'] = $average_distance;
        }
        
        
        $form['#theme'] = 'swim_statistics_form';
        $form['data'] = $swim_stats;

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $swim_id = $form_state->getValue('swim_id');
        $swim_id_as_int = (int)$swim_id;
        $uid = \Drupal::currentUser()->id();

        $query = \Drupal::database()->select('icows_stats', 'i');
        $query->condition('i.swim_id', $swim_id_as_int, '=');
        $query->condition('i.uid', $uid, '=');
        $query->fields('i', ['uid']);
        $result = $query->execute()->fetchAll();
        if(count($result) > 0){
            $form_state->setErrorByName('previously_submitted', $this->t('You have already submited statistics for this swim'));
        }

        $pace = $form_state->getValue('pace');
        $distance = $form_state->getValue('distance');
        if(!isValidPace($pace)){
            $form_state->setErrorByName('invalid_pace', $this->t('Invalid pace. Please use the format MM:SS'));
        }
        if(!isValidNumber($distance)){
            $form_state->setErrorByName('invalid_distance', $this->t("Invalid distance. Please enter a number for the 'distance' field."));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $current_user_id = \Drupal::currentUser()->id();
        $value = [
            'uid' => $current_user_id,
            'swim_id' => $form_state->getValue('swim_id'),
            'pace' => $form_state->getValue('pace'),
            'distance' => $form_state->getValue('distance'),
        ];
        
        $database = \Drupal::database();
        $query = $database->insert('icows_stats')->fields(['uid', 'swim_id', 'pace', 'distance']);
        $query->values($value);
        $query->execute();
        $form_state->setRedirect('swim.statistics_index');
    }

}
