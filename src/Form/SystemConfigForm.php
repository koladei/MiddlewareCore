<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;

/**
 * Implements an example form.
 */
class SystemConfigForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'system_config';
    }
  

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'middleware_core.systems',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $system_id = NULL) {
        $config = $this->config("middleware_core.systems");
        $system = $config->get($system_id);
        $driver_list = [];

        if(is_null($system)) {
            return $this->redirect('middleware_core.systems');
        }

        // Get the info about the 
        Drupal::moduleHandler()->alter(
            ['middleware_driver_settings_template',"middleware_driver_settings_template_{$system['driver_name']}"], 
            $system['driver_name'], 
            $driver_list,
            $form_state
        );
        $form_state->setTemporaryValue('system_id', $system_id);

        $form['#tree'] = true;
        $form['system_info'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('System information'),
        ];

        $form['system_info']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#default_value' => $system['name']
        ];
        
        $form['system_info']['api_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Name'),
            '#default_value' => $system['api_name']
        ];
        
        $form['system_info']['driver_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Driver name'),
            '#options' => [$system['driver_name'] => $driver_list[$system['driver_name']]['name']],
            '#default_value' => $system['driver_name'],
            '#disabled' => true
        ];
        
        $form['system_info']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#default_value' => $system['description']
        ];

        $form['settings'] = [
            '#type' => 'fieldset',
            '#title' => 'Settings',
        ];
        $form['settings'] = array_merge($form['settings'], $driver_list[$system['driver_name']]['settings']);

        $form['#attached']['library'][] = 'middleware_core/configuration.system';

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (strlen($form_state->getValue(['system_info', 'name'])) < 1) {
            $form_state->setErrorByName(['system_info', 'name'], $this->t('The system <b>Name</b> you specified is too short'));
        }
        else if (strlen($form_state->getValue(['system_info', 'api_name'])) < 1) {
            $form_state->setErrorByName(['system_info', 'api_name'], $this->t('The system <b>API Name</b> you specified is too short'));
        } 
        else if($form_state->getTemporaryValue('system_id') != $form_state->getValue(['system_info', 'api_name'])) {
            $config = $this->config("middleware_core.systems");
            $system = $config->get($form_state->getValue(['system_info', 'api_name']));

            // If the new value is already in use by another system
            if(!is_null($form_state->getValue(['system_info', 'api_name']))){
                $form_state->setErrorByName(['system_info', 'api_name'], $this->t('The <b>API Name</b> you specified is already in use.'));
            }
        }

        Drupal::moduleHandler()->alter(
            ['middleware_driver_settings_template_validate'], 
            $form_state->getValue(['system_info', 'api_name']),
            $form_state
        );
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.systems');
        
        $settings = $form_state->getValue('settings');
        $system_info = $form_state->getValue('system_info');
        $system_info['settings'] = $settings;

        $config->set($system_info['api_name'], $system_info)->save();
    }
}