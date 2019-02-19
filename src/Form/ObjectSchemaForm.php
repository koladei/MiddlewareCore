<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class ObjectSchemaForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'object_schema';
    }
  

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'middleware_core.objects',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $system_id = NULL, $object_id = NULL) {
        $config = $this->config('middleware_core.objects');
        $form['api_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Name'),
        ];
        
        $form['internal_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Internal Name'),
        ];

        
        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
        ];
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (strlen($form_state->getValue('phone_number')) < 3) {
            $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        drupal_set_message($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
    }
}