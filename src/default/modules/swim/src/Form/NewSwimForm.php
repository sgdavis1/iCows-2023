<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
            '#type' => 'textarea',
            '#title' => $this->t('Description'),

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
        if (strlen($form_state->getValue('phone_number')) < 3) {
            $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
    }

}







