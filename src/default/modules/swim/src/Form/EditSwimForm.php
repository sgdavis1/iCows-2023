<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;


/**
 * Implements an example form.
 */
class EditSwimForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'swim_edit_swim_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
        $query = \Drupal::database()->select('icows_swims', 'i');

        // Add extra detail to this query object: a condition, fields and a range
        $query->condition('i.swim_id', $id, '=');
        $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
        $query->range(0, 1);
        $swim = $query->execute()->fetchAll()[0];

        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#maxlength' => 128,
            '#required' => TRUE,
            '#default_value' => $swim->title,
        ];
        $date = new DrupalDateTime($swim->date_time);
        $form['date_time'] = [
            '#type' => 'datetime',
            '#title' => $this->t('Date and Time'),
            '#default_value' => $date,
            '#required' => TRUE
        ];
        $form['description'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Description'),
            '#default_value' => $swim->description,
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
            '#default_value' => $swim->locked,
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
        $locked = 0;
        if ($form_state->getValue('override') == 'Locked') {
            $locked = 1;
        }
        $database = \Drupal::database();
        $database->update('icows_swims')->fields(array(
            'title' => $form_state->getValue('title'),
            'description' => $form_state->getValue('description')["value"],
            'date_time' => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            'locked' => $locked,
        ))->condition('swim_id', $form_state->getValue('swim_id'), '=')->execute();
    }
}







