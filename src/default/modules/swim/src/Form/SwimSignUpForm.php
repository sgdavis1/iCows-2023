<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
        $pace = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_pace->value;

        $form['swim_id'] = array(
            '#value' => $id,
            '#type' => 'hidden'
        );
        $form['pace'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Pace'),
            '#default_value' => $pace,
            '#maxlength' => 10,
            '#required' => TRUE
        ];
        $form['pace_description'] = array(
            '#markup' => '<p>Enter your sustained pace per 100m for a long swim (at least 500m length). Ex] 1:55</p><br>'
        );
        $form['distance'] = [
            '#type' => 'number',
            '#title' => $this->t('Distance'),
            '#step' => '.01',
            'precision' => 2, 
            '#required' => TRUE
        ];
        $form['distance_description'] = array(
            '#markup' => '<p>Enter your desired distance that you would like to swim. Ex: 1 = 1km</p><br>
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
        $swim_id = $form_state->getValue('swim_id');
        $swim_id_as_int = (int)$swim_id;
        $uid = \Drupal::currentUser()->id();
        $database = \Drupal::database();
        $select = $database->select('icows_attendees')
            ->fields('icows_attendees', ['uid'])
            ->condition('icows_attendees.swim_id', $swim_id_as_int, '=');

        $query = \Drupal::database()->select('icows_attendees', 'i');
        $query->condition('i.swim_id', $swim_id_as_int, '=');
        $query->condition('i.uid', $uid, '=');
        $query->fields('i', ['uid']);
        $result = $query->execute()->fetchAll();
        if(count($result) > 0){
            $form_state->setErrorByName('signed_up', $this->t('You are already signed up for this swim'));
        }

        $pace = $form_state->getValue('pace');
        $distance = $form_state->getValue('distance');
        $boats = $form_state->getValue('kayaks');
        if(!isValidPace($pace)){
            $form_state->setErrorByName('invalid_pace', $this->t('Invalid pace. Please use the format MM:SS'));
        }
        if(!isValidNumber($boats)){
            $form_state->setErrorByName('invalid_boats', $this->t("Invalid boats entry. Please enter a number for the 'boats' field."));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $swim_id = $form_state->getValue('swim_id');

        $willing_to_kayak = 0;
        if ($form_state->getValue('if_needed') == 1) {  //assuming 1 means box is checked
            $willing_to_kayak = 1;
        }

        $values = [
            [
                'kayaker' => $willing_to_kayak,
                'swimmer' => 1,
                'distance' => $form_state->getValue('distance'),
                'uid' => intval(\Drupal::currentUser()->id()),
                'swim_id' => $swim_id,
                'number_of_kayaks' => $form_state->getValue('kayaks'),
                'estimated_pace' => $form_state->getValue('pace'),
                'group' => 1,
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_attendees')->fields(['swim_id', 'uid', 'swimmer', 'kayaker', 'number_of_kayaks', 'estimated_pace', 'distance', 'group']);
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

        //get swimmers and kayakers for regrouping by pace

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

            $group_num =  floor($swimmer_count / $num_kayakers);
            $remainder = $swimmer_count % $num_kayakers;

            //sort by pace from fastest (shortest num of seconds to swim 1km) to slowest (longest time)
            usort($swimmers_info, function ($swimmer1, $swimmer2) {
                return $swimmer1[1] <=> $swimmer2[1];
            });

            //call the grouping algorithm
            groupSwimmers($swim_id, $swimmers_info, $num_kayakers, $group_num, $remainder);
        }


        //log changes

        $query = \Drupal::database()->select('icows_swims', 'i');
        $query->condition('i.swim_id', $form_state->getValue('swim_id'), '=');
        $query->fields('i', ['uid', 'title']);
        $swim = $query->execute()->fetchAll()[0];

        $attendee = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_first_name->value . " " .  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_last_name->value;

        log_swim_change($form_state->getValue('swim_id'), $swim->uid, sprintf('%s has signed up as a swimmer for your hosted swim %s.', $attendee, $swim->title));

        //re-direct user

        $id = $form_state->getValue('swim_id');
        $form_state->setRedirect('swim.show', ["id" => $id]);
    }

}

