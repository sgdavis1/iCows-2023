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

        // TODO: verify that the date is in the past
        // TODO: verify that the user was register for the swim
        // TODO: Allow the route to work without the form portion when no ID is given
        // TODO: remove blank rows in the table
        // TODO: Validate the pace and distance format and add description on the form
        // TODO: format twig better

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
        }

        $form['#theme'] = 'swim_statistics_form';
        $form['data'] = $swim_stats;

        return $form;
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
        return true;
    }

}
