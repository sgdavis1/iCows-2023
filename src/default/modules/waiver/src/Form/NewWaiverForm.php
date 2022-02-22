<?php

namespace Drupal\waiver\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

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
        $form_state->disableCache();
        $form['waiver'] = array(
            '#type' => 'managed_file',
            '#title' => t('waiver'),
            '#description' => t('Swim waiver'),
            '#upload_location' => 'public://files',
            '#upload_validators' => [
                'file_validate_extensions' => array('pdf'),
                'file_validate_size' => array(25600000)
            ],
            '#required' => TRUE,
        );
        //only allow pdfs for now

        //need to add sidebar option-selector thing
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

        $file = file_save_upload('waiver', array(), FALSE, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $waiver = $form_state->getValue('waiver');
        if ($waiver) {
            $file = File::load(reset($waiver));
            $file->setPermanent();
            $file->save();
            $values = [
                [
                    'approved' => 0,
                    'uid' => 1,
                    'current_waiver' => 1,
                    'waiver_url' => $waiver[0],
                ],                                          //time not working rn
            ];

            $connection = \Drupal::service('database');
            $query = $connection->insert('icows_waivers')->fields(['approved','uid', 'current_waiver', 'waiver_url']);
            foreach ($values as $value){
                $query->values($value);
            }
            $query->execute();
        }
    }
}