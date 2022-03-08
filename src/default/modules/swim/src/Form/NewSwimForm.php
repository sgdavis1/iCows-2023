<?php

namespace Drupal\swim\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use \Drupal\node\Entity\Node;



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
            '#type' => 'text_format',
            '#title' => $this->t('Description'),
            '#required' => TRUE
        ];
        $form['override'] = [
            '#type' => 'select',
            '#title' => $this->t('Manual Override'),
            '#options' => [
                'Unlocked' => $this->t('Unlocked'),
                'Locked' => $this->t('Locked'),
            ],
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
        // make sure not in the past
        $swim_date = $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        $current_date = DrupalDateTime::createFromTimestamp(time())->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        if($swim_date < $current_date){
            //throw error
            $form_state->setErrorByName('swim_in_the_past', $this->t('You cannot create a swim for a past date.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $locked = 0;
        if ($form_state->getValue('override') == 'Locked') {
            $locked = 1;
        }

        $values = [
            [
            'title' => $form_state->getValue('title'),
            'description' => $form_state->getValue('description')["value"],// only saving value for now, can save format later
            'locked' => $locked,
            // https://drupal.stackexchange.com/questions/204103/inserting-the-value-from-datetime-field-form
            // would need to add timezones here if we ever wanted to support multiple timezones
            'date_time' => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            // must add uid, ask steve if uid determined by creator or from dropdowm
            'uid' => \Drupal::currentUser()->id(),
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_swims')->fields(['uid','title','description','locked','date_time']); //->values($values)->execute();
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

        $query = \Drupal::database()->select('icows_swims', 'i');
        $num_rows = $query->countQuery()->execute()->fetchField();

        // create node for calendar
        $node = Node::create([
            'type'              => 'swims',
            'body'              => $values[0]['description'],
            'title'             => $values[0]['title'],
            'field_swim_id'     => $num_rows,
            'field_swim_date'   => $values[0]['date_time'],
            'field_swim_link'   => 'http://127.0.0.1:8080/swims/'.$num_rows,
        ]);
        $node->save();


        $form_state->setRedirect('swim.show', ["id" => $num_rows]);
    }

}







