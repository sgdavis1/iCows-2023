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
            '#title' => $this->t('Event Lock'),
            '#options' => [
                'Unlocked' => $this->t('Unlocked'),
                'Locked' => $this->t('Locked'),
            ],
            '#default_value' => $this->t('Unlocked'),
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

        $query = \Drupal::database()->select('icows_swims', 'i');
        $num_rows = $query->countQuery()->execute()->fetchField();
        $num_rows += 1;

        $host = \Drupal::request()->getSchemeAndHttpHost();

        // create node for calendar
        $node = Node::create([
            'type'              => 'swims',
            'body'              => $form_state->getValue('description')["value"],
            'title'             => $form_state->getValue('title'),
            'field_swim_id'     => $num_rows,
            'field_swim_date'   => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            'field_swim_link' => [
                'uri' => $host.'/swims/'.$num_rows,
                'title' => $form_state->getValue('title'),
            ]
        ]);
        $node->save();
        $nid = $node->id();

        $values = [
            [
            'title' => $form_state->getValue('title'),
            'description' => $form_state->getValue('description')["value"],// only saving value for now, can save format later
            'locked' => $locked,
            // https://drupal.stackexchange.com/questions/204103/inserting-the-value-from-datetime-field-form
            'date_time' => $form_state->getValue('date_time')->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            'uid' => \Drupal::currentUser()->id(),
            'nid' => $nid,
            ],
        ];
        $database = \Drupal::database();
        $query = $database->insert('icows_swims')->fields(['uid','title','description','locked','date_time', 'nid']); //->values($values)->execute();
        foreach ($values as $developer) {
            $query->values($developer);
        }
        $query->execute();

        $form_state->setRedirect('swim.show', ["id" => $num_rows]);
    }

}







