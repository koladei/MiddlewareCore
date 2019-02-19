<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the object list form.
 */
class ObjectSchemaListForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'object_schema_list';
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
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.objects');

        $header = [
            'api_name' => t('API Name'),
            'internal_name' => t('Internal name'),
            'description' => t('Description')
        ];

        $output = [];

        $form['table'] = [
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $output,
            '#empty' => t('No users found'),
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