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
        verify_swim_exists($id);
        $query = \Drupal::database()->select('icows_swims', 'i');
    
        // Add extra detail to this query object: a condition, fields and a range
        $query->condition('i.swim_id', $id, '=');
        $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
        $query->range(0, 1);
        $swim = $query->execute()->fetchAll()[0];

        $stats = ["test", "1", "2"];

        $date = new DrupalDateTime($swim->date_time);
        $form['date_time'] = [
            '#type' => 'datetime',
            '#title' => $this->t('Date and Time'),
            '#default_value' => $date,
            '#required' => TRUE
        ];

        $form['pace'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Pace'),
            '#maxlength' => 10,
            '#required' => TRUE
        ];

        $form['pace_description'] = array(
            '#markup' => '<p>Enter your sustained pace per 100m for a long swim (at least 500m length). Ex] 1:55</p><br>'
        );

        $form['distance'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Distance'),
            '#maxlength' => 10,
            '#required' => TRUE
        ];

        $form['distance_description'] = array(
            '#markup' => '<p>Enter your desired distance that you would like to swim. Ex] 1km</p><br>
            <p>Some useful approximate distances to remember (short course first / long course second)</p>
            <ul>
              <li><strong>Brown boathouse</strong> 1.2K / 2.0K</li>
              <li><strong>Diamond Window boathouse</strong> 1.6K / 2.4K</li>
              <li><strong>The point</strong> 2.1K / 2.9K</li>
              <li><strong>The dam</strong> 3.0K / 3.8K</li>
            </ul>
            <p>Short course is the direct route to the landmarks, long course includes a jog across the lake
            at the start and end of the swim.</p><br>'
        );

        $form['swim_id'] = array(
            '#value' => $id,
            '#type' => 'hidden',
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];

        $form['#cache'] = ['max-age' => 0];

        $current_user_id = \Drupal::currentUser()->id();
        $query = \Drupal::database()->select('icows_stats', 'i');
        $query->condition('i.uid', $current_user_id, '=');
        $query->fields('i', ['swim_id', 'pace', 'distance']);
        $swim_stats = $query->execute()->fetchAll();
        
        foreach ($swim_stats as &$stat) {
            $query = \Drupal::database()->select('icows_swims', 'i');
            $query->condition('i.swim_id', $stat->swim_id, '=');
            $query->fields('i', ['date_time', 'title']);
            $swim = $query->execute()->fetchAll()[0];

            
            $date = new DrupalDateTime($swim->date_time, 'UTC');
            $stat->date =  getFormattedDate($date);
            $stat->swim_name = $swim->title;
            var_dump($swim->title);
        }

        return array(
            '#theme' => 'swim_statistics_form',
            '#form' => $form,
            '#data' => $swim_stats,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->messenger()->addStatus("TEST123");
        $current_user_id = \Drupal::currentUser()->id();
        $value = [
            'uid' => $recipient_uid,
            'swim_id' => $form_state->getValue('swim_id'),
            'pace' => $form_state->getValue('pace'),
            'distance' => $form_state->getValue('distance'),
        ];
        
        $database = \Drupal::database();
        $query = $database->insert('icows_stats')->fields(['uid', 'swim_id', 'pace', 'distance']);
        $query->values($value);
        $query->execute();
        return true;

        // Redirect to home
        // $form_state->setRedirect('<front>');
          
    }

}
