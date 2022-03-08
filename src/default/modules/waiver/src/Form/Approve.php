<?php

namespace Drupal\waiver\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Implements an example form.
 */
class Approve extends FormBase{
    public function getFormId() {
        return 'approve_waiver_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

        $form['description'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Description'),
            '#default_value' => "",

            '#required' => TRUE
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];
        return $form;
    }
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

    }
}