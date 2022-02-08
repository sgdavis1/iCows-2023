<?php

namespace Drupal\waiver\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class NewWaiverForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'waiver_new_waiver_form';
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

        //need to add sidebar option-selector thing

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

    }
}
