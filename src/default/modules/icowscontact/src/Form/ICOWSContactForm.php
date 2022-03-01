<?php

namespace Drupal\icowscontact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class ICOWSContactForm extends FormBase {

    public function getFormId() {
        return 'icows_contact_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Subject'),
            '#maxlength' => 20,
            '#required' => TRUE
        ];
        $form['body'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Body'),
            '#maxlength' => 300,
            '#required' => TRUE
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send Emails'),
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
        $subject = $form_state->getValue('subject');
        $body = $form_state->getValue('body');

        notify_users($subject, $body);
    }

}
