<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;


/**
 * Implements an example form.
 */
class NewSwimForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'swim_new_swim_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#maxlength' => 128,
            '#required' => TRUE
        ];
        $form['date_time'] = [
            '#type' => 'datetime',
            '#title' => $this->t('Date and Time'),
            '#required' => TRUE
        ];
        $form['description'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Description'),

            //Need to add summary option

            '#required' => TRUE
        ];
        $form['override'] = [
            '#type' => 'select',
            '#title' => $this->t('Manual Override'),
            '#options' => [
                'Unlocked' => $this->t('Unlocked'),
                'Locked' => $this->t('Locked'),
            ],
            '#required' => TRUE
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
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
            // would need to add timezones here if we ever wanted to support multiple timezones
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







