<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Drupal\Core\Field\EntityReferenceFieldItemList;
use \Drupal\node\Entity\Node;


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
        verify_swim_exists($id);
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
        $locked = 'Unlocked';
        if ($swim->locked >= 1) {
            $locked = 'Locked';
        }
        
        $form['override'] = [
            '#type' => 'select',
            '#title' => $this->t('Manual Override'),
            '#options' => [
                'Unlocked' => $this->t('Unlocked'),
                'Locked' => $this->t('Locked'),
            ],
            '#default_value' => $locked,
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
        $query = \Drupal::database()->select('icows_swims', 'i');
        $query->condition('i.swim_id', $form_state->getValue('swim_id'), '=');
        $query->fields('i', ['locked', 'title', 'uid', 'nid']);
        $swim = $query->execute()->fetchAll()[0];
        $locked_status = $swim->locked;

        // locked_status: 0 = Unlocked; 1 = Locked; 2 = Automatically Locked; -1 = Manually Unlocked After 2;
        if ($form_state->getValue('override') == 'Locked') {
            if ($locked_status == 2 || $locked_status == -1) {
                $locked = 2;
            } else {
                $locked = 1;
            }
            
        } else if ($form_state->getValue('override') == 'Unlocked' && $locked_status == 2) {
            $locked = -1;
        }

        $admin = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_first_name->value . " " .  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->field_last_name->value;

        if ($swim->title == $form_state->getValue('title')) {
            log_swim_change($form_state->getValue('swim_id'), $swim->uid, sprintf('Your hosted swim %s has been edited by admin %s.', $swim->title, $admin));
        } else {
            log_swim_change($form_state->getValue('swim_id'), $swim->uid, sprintf('Your hosted swim %s (formerly %s) has been edited by admin %s.', $form_state->getValue('title'), $swim->title, $admin));
        }
        
        $database = \Drupal::database();
        $database->update('icows_swims')->fields(array(
            'title' => $form_state->getValue('title'),
            'description' => $form_state->getValue('description')["value"],
            'date_time' => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            'locked' => $locked,
        ))->condition('swim_id', $form_state->getValue('swim_id'), '=')->execute();


        //set values to filter
        $values = [
            'type' => 'swims',
            'field_swim_id' => $form_state->getValue('swim_id'),
        ];

        // Get the swim nodes
        $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties($values);

        //get specific node
        $node = $nodes[$swim->nid];

        //update swim node
        $node->set('title', $form_state->getValue('title'));
        $node->set('body', $form_state->getValue('description')["value"]);
        $node->set('field_swim_date', $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT));

        //save to update node
        $node->save();

        $form_state->setRedirect('swim.show', ["id" => $form_state->getValue('swim_id')]);
    }

}








