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
class KayakSignupForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'swim_kayak_signup_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
        verify_swim_status($id);
        $form['swim_id'] = array(
            '#default_value' => $id,
            '#type' => 'hidden'
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
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Sign up'),
            '#button_type' => 'primary',
        ];
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#weight' => 20,
            '#executes_submit_callback' => TRUE,
            '#submit' => array('swims_form_cancel'),
            '#limit_validation_errors' => array()
        );

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
        $swim_id = $form_state->getValue('swim_id');

        $query = \Drupal::database()->select('icows_attendees', 'i');
        $query->condition('i.swim_id', $swim_id, '=');
        $query->condition('i.kayaker', 1, '=');
        $query->condition('i.swimmer', 0, '=');
        $query->fields('i', ['group']);
        $kayakers = $query->execute()->fetchAll();

        $group_num = 1;
        foreach ($kayakers as &$kayaker) {
            if ($kayaker->group >= $group_num) {
                $group_num = $kayaker->group + 1;
            }
        }

        $values = [
            [
                'kayaker' => 1,
                'swimmer' => 0,
                'uid' => intval(\Drupal::currentUser()->id()),
                'swim_id' => $swim_id,
                'number_of_kayaks' => $form_state->getValue('kayaks'),
                'group' => $group_num,
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_attendees')->fields(['swim_id', 'uid', 'swimmer', 'kayaker', 'number_of_kayaks', 'group']);
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

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

        if ($group_num > 1 && $grouping_setting){
            $num_kayakers = $group_num;

            //get the necessary info for the grouping algo
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

        $id = $form_state->getValue('swim_id');
        //$url = \Drupal\Core\Url::fromRoute('swim.show', ["id" => $id]);

        $query = \Drupal::database()->select('icows_swims', 'i');
        $query->condition('i.swim_id', $form_state->getValue('swim_id'), '=');
        $query->fields('i', ['uid', 'title']);
        $swim = $query->execute()->fetchAll()[0];

        $attendee = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_first_name->value . " " .  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_last_name->value;

        log_swim_change($form_state->getValue('swim_id'), $swim->uid, sprintf('%s has signed up as a kayaker for your hosted swim %s.', $attendee, $swim->title));

        $form_state->setRedirect('swim.show', ["id" => $id]);
    }

}
