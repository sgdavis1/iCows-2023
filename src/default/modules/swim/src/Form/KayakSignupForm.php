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
        $values = [
            [
                'kayaker' => 1,
                'swimmer' => 0,
                'uid' => intval(\Drupal::currentUser()->id()),
                'swim_id' => $form_state->getValue('swim_id'),
                'number_of_kayaks' => $form_state->getValue('kayaks'),
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_attendees')->fields(['swim_id', 'uid', 'swimmer', 'kayaker', 'number_of_kayaks']);
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();
        $id = $form_state->getValue('swim_id');
        //$url = \Drupal\Core\Url::fromRoute('swim.show', ["id" => $id]);

        $form_state->setRedirect('swim.show', ["id" => $id]);
    }

}


// 0 = Unlocked; 1 = Locked; 2 = Automatically Locked; -1 = Manually Unlocked After 2;
// This code is very SIMILAR to some in the controller
// This code is duplicated in other signup forms
function verify_swim_status($id) {
    $query = \Drupal::database()->select('icows_swims', 'i');
    
    $query->condition('i.swim_id', $id, '=');
  
    $query->fields('i', ['uid', 'swim_id', 'date_time', 'title', 'description', 'locked']);
    $swim = $query->execute()->fetchAll()[0];
    if ($swim->locked == 1 || $swim->locked == 2) {
        $response = new RedirectResponse(Url::fromRoute('swim.show', ['id' => $id])->toString());
        $response->send();
        return;
    }
    else if (!$swim) {
        $response = new RedirectResponse("/");
        $response->send();
        return;
    }
}
