<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;


/**
 * Implements an example form.
 */
class SwimSignUpForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'swim_swim_signup_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
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
        $form['kayaks'] = [
            '#type' => 'number',
            '#title' => $this->t('Boat(s)'),
            '#default_value' => 0,
            '#min' => 0,
            '#required' => TRUE
        ];
        $form['kayak_description'] = array(
            '#markup' => '<p>If you have kayaks or other water vessels available, please let us know how many
            you will be able to bring</p><br>'
        );
        $form['if_needed'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('If needed'),
            '#required' => TRUE
        ];
        $form['if_needed_description'] = array(
            '#markup' => "<p>If you are willing to switch your role to a kayaker in the case we don't have
            enough kayakers in attendance, please indicate this here.</p><br>"
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];
//        Still need a  cancel button
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        // TODO: add validation
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $locked = 0;
        if ($form_state->getValue('override') == 'Locked') {
            $locked = 1;
        }

        $values = [
            [
                'title' => $form_state->getValue('title'),
                'description' => $form_state->getValue('description')["value"],// only saving value for now, can save format later
                'locked' => $locked,
                // https://drupal.stackexchange.com/questions/204103/inserting-the-value-from-datetime-field-form
                'field_date' => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
                // must add uid, ask steve if uid determined by creator or from dropdowm
                'uid' => \Drupal::currentUser()->id(),
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_swims')->fields(['title','description','locked','field_date']); //->values($values)->execute();
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

    }

}
