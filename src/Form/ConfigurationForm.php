<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ConfigurationForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'middleware_core_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'middleware_core.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.settings');
        

        // $form['in'] = [
        //     '#type' => 'text_format',
        //     '#title' => $this->t('Label'),
        //     '#format' => 'js',
        // ];

        $form['information'] = [
            '#type' => 'vertical_tabs',
            '#default_tab' => 'sql'              
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $this->config('middleware_core.settings')
        //   ->set('connections', $values['connections'])
          ->save();
        parent::submitForm($form, $form_state);
    }
}