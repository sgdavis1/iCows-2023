<?php

namespace Drupal\waiver\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

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
        $form['waiver_title'] = array(
            '#markup' => '<h1>Submit a Waiver</h1><br>'
        );

        $form['waiver'] = array(
            '#type' => 'managed_file',
            '#upload_location' => 'public://files',
            '#upload_validators' => [
                'file_validate_size' => array(25600000)
            ],
            '#required' => TRUE,
        );

        $form['waiver_description'] = array(
            '#markup' => '<p>Submit a photo or PDF scan of your ICOWS swim waiver here.</p><p>This form must be submitted once per year to be elegible to sign up as a swimmer for future swim events.</p><br>'
        );

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

            $uid = \Drupal::currentUser()->id();

            $values = [
                [
                    'approved' => 0,
                    'uid' => $uid,
                    'waiver_url' => $waiver[0],
                ],                                          //time not working rn
            ];

            $connection = \Drupal::service('database');
            $query = $connection->insert('icows_waivers')->fields(['approved','uid', 'waiver_url']);
            foreach ($values as $value){
                $query->values($value);
            }
            
            $id = $query->execute();
            $current_user = \Drupal\user\Entity\User::load($uid);
            $current_user->set('field_current_waiver_id', $id);
            $current_user->save();

            $ids = \Drupal::entityQuery('user')
                ->condition('roles', 'administrator')
                ->execute();
            $users = User::loadMultiple($ids);
            foreach($users as $user){
                $waiver_opt = $user->field_waiver_upload_email_opt_in->value;
                $first_name = $current_user->field_first_name->value;
                $last_name = $current_user->field_last_name->value;
                if($waiver_opt != null and $waiver_opt != 0){
                    log_email($user->id(), "A new waiver was uploaded by $first_name $last_name");
                }
            }
        }
    }
}
