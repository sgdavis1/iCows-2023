<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;
use Drupal\Core\Url; 
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
        verify_swim_status($id);
        $form['swim_id'] = array(
            '#value' => $id,
            '#type' => 'hidden'
        );
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
        ];
        $form['if_needed_description'] = array(
            '#markup' => "<p>If you are willing to switch your role to a kayaker in the case we don't have
            enough kayakers in attendance, please indicate this here.</p><br>"
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Sign up'),
            '#button_type' => 'primary',
        ];
        $form['actions']['cancel'] = [      //FIXME
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#weight' => 20,
            '#executes_submit_callback' => TRUE,
            '#submit' => array('mymodule_form_cancel'),
        ];
        return $form;
    }

    public function mymodule_form_cancel(){     //FIXME
        drupal_goto('destinationpage');
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
        $willing_to_kayak = 0;
        if ($form_state->getValue('if_needed') == 1) {  //assuming 1 means box is checked
            $willing_to_kayak = 1;
        }

        $values = [
            [
                'kayaker' => $willing_to_kayak,
                'swimmer' => 1,
                'uid' => intval(\Drupal::currentUser()->id()),
                'swim_id' => $form_state->getValue('swim_id'),
                'number_of_kayaks' => $form_state->getValue('kayaks'),
                'estimated_pace' => $form_state->getValue('pace'),
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_attendees')->fields(['swim_id', 'uid', 'swimmer', 'kayaker', 'number_of_kayaks', 'estimated_pace']);
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

        $id = $form_state->getValue('swim_id');
        $form_state->setRedirect('swim.show', ["id" => $id]);
    }

}

