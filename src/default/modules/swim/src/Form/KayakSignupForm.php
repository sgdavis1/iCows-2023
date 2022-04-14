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
            '#value' => $id,
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
