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
     * Validates Time in MM:SS
     *
     * @param string $time  Name of time to be checked.
     */
    public function isValidPace(string $time)
    {
        return preg_match("#^(([0-5][0-9])|[0-9]):[0-5][0-9]$#", $time);
    }

    /**
     * Validates that a string is a number
     *
     * @param string number  Name of number to be checked.
     */
    public function isValidNumber(string $number)
    {
        return preg_match("#^[0-9]+$#", $number);
    }

//    /**
//     * Groups swimmers by pace
//     *
//     * @param int $swim_id is the id of the swim that the grouping is taking place for
//     * @param array $swimmers is a sorted list of lists (by fastest to slowest pace (in seconds)) of uid and pace for each swimmer
//     * @param int $num_groups is the number of groups we need to have
//     * @param int $per_group is the ideal number of swimmers per group
//     * @param int $remainder is the number groups that will have more than the ideal number
//     */
//    public function groupSwimmers(int $swim_id, array $swimmers, int $num_groups, int $per_group, int $remainder)
//    {
//        $groupings = array();
//
//        //make grouping
//        $optimized_grouping = $this->groupingHelper($swimmers, $groupings, $num_groups, $remainder, 0, $per_group);
//
//        //get grouping
//        $grouping = $optimized_grouping[0];
//
//        //update attendees to have their new group
//        $database = \Drupal::database();
//
//        $grouping_num = 0;
//        foreach ($grouping as &$group) {
//            $grouping_num++;
//            foreach ($group as &$swimmer) {
//                $database->update('icows_attendees')->fields(array(
//                    'group' => $grouping_num,
//                ))->condition('icows_attendees.swim_id', $swim_id, '=')
//                    ->condition('icows_attendees.uid', $swimmer[0], '=')
//                    ->execute();
//            }
//        }
//    }
//
//    /**
//     * Groups swimmers by pace
//     *
//     * @param array $swimmers is a sorted array of arrays (by fastest to slowest pace in seconds) of uid and pace for each swimmer
//     * @param array $grouping [0] is the returned array of groupings, [1] is the dif for the grouping
//     * @param int $groups_remaining is the number of groups left that still need to be made
//     * @param int $per_group is the ideal number of swimmers per group
//     * @param int $remainder is the number groups that will have more than the ideal number
//     * @param int $dif is the total number of seconds between each group's pacing differences
//     */
//    public function groupingHelper(array $swimmers, array $grouping, int $groups_remaining,
//                                   int $remainder, int $dif, int $per_group): array
//    {
//        if ($groups_remaining == 0){
//            $final = array();
//            array_push($final, $grouping);
//            array_push($final, $dif);
//            return $final;
//        }
//
//        $groups_remaining--;
//
//        $group = array($swimmers[0]);
//        $group_start_pace = $swimmers[0][1];
//        $group_end_pace = $swimmers[0][1];
//
//        for ($i = 1; $i < $per_group; $i++) {
//            array_push($group, $swimmers[$i]);
//            $group_end_pace = $swimmers[$i][1];
//        }
//
//        // calculate difference
//        $group_dif = $group_end_pace - $group_start_pace;
//
//        //remove swimmers that just got grouped
//        for ($i = 0; $i < $per_group; $i++) {
//            array_shift($swimmers);
//        }
//
//        //add grouping
//        $reg_groupings = $grouping;
//        array_push($reg_groupings, $group);
//
//        //make regular-sized group (without a remainder)
//        $reg_group = $this->groupingHelper($swimmers, $reg_groupings, $groups_remaining, $remainder, ($group_dif + $dif), $per_group);
//
//        if ($remainder > 0) {
//            //make bigger group (with a remainder)
//            $remainder--;
//
//            //add extra swimmer
//            array_push($group, $swimmers[0]);
//            $group_end_pace = $swimmers[0][1];
//
//            //remove swimmer from list of remaining swimmers
//            array_shift($swimmers);
//
//            // calculate difference
//            $group_dif = $group_end_pace - $group_start_pace;
//
//            //add grouping
//            $extra_groupings = $grouping;
//            array_push($extra_groupings, $group);
//
//            $extra_group = $this->groupingHelper($swimmers, $extra_groupings, $groups_remaining, $remainder, ($group_dif + $dif), $per_group);
//
//            if($reg_group[1] < $extra_group[1]){
//                return $reg_group;
//            }
//            else {
//                return $extra_group;
//            }
//        }
//        else {
//            return $reg_group;
//        }
//    }
//
//
//    /**
//     * Converts a pace/distance to pace/1 km
//     *
//     * @param string $pace is the pace of a swimmer
//     * @param int $distance is the distance for the pace given
//     */
//    public function getStandardPace(string $pace, int $distance): int
//    {
//        //convert pace from min:sec to seconds
//        $time = explode(":", $pace);
//        $pace_in_sec = ($time[0] * 60) + $time[1];
//
//        if ($distance == 1) {
//            return $pace_in_sec;
//        }
//        else {
//            return (1 / $distance) * $pace_in_sec;
//        }
//    }

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
        if(!$this->isValidPace($pace)){
            $form_state->setErrorByName('invalid_pace', $this->t('Invalid pace. Please use the format MM:SS'));
        }
        if(!$this->isValidNumber($distance)){
            $form_state->setErrorByName('invalid_distance', $this->t("Invalid distance. Please enter a number for the 'distance' field."));
        }
        if(!$this->isValidNumber($boats)){
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

        //if more than 1 group is available, get the swimmers for grouping
        if ($num_kayakers > 1) {
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
            $group_num =  $num_kayakers / $swimmer_count;
            $remainder = $num_kayakers % $swimmer_count;

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

