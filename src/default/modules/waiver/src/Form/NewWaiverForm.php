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
        $form['waiver'] = array(
            '#type' => 'managed_file',
            '#title' => t('waiver'),
            '#description' => t('Swim waiver'),
            '#upload_location' => 'public://files',
        );
        //only allow pdfs for now

        //need to add sidebar option-selector thing

        $form['#submit'][] = 'mymodule_set_default_header_image_form_submit';
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
