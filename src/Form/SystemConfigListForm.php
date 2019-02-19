<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;

/**
 * Implements the object list form.
 */
class SystemConfigListForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'system_list';
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
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.systems');
        $driver_list = [];
        Drupal::moduleHandler()->alter(['middleware_driver', 'middleware_driver_list'], $driver_list);
        $config->delete();
        $config->set('psql',
        [
            'name' => 'Primary SQL Server',
            'description' => 'Enables various API interactions with the primary SQL Server.',
            'driver_name' => 'sql_mssql',
            'settings' => [],
            'api_name' => 'psql'
        ])
        ->save();
        $config->set('ssql',
        [
            'name' => 'Primary SQL Server',
            'description' => 'Enables various API interactions with the primary SQL Server.',
            'driver_name' => 'sql_mysql',
            'settings' => [],
            'api_name' => 'ssql'
        ])
        ->save();
        // $config->set('sql', [
        //         'name' => 'SQL Server 1',
        //         'description' => 'Enables various API interactions with a Salesforce org.',
        //         'driver_name' => 'sql',
        //         'api_name' => 'sql'
        //     ])
        // ->save();

        $header = [
            'name' => t('Name'),
            'api_name' => t('API Name'),
            'description' => t('Description')
        ];

        $output = [];

        // Get the list of systems for rendering.
        foreach($config->get() as $key => $system){
            $output[] = [
                'name' => $this->l($system['name'], Url::fromRoute('middleware_core.system', [
                    'system_id' => $system['api_name']
                ])),
                'api_name' => $system['api_name'],
                'description' => $system['description'],
            ];
        }

        // Dispay the table.
        $form['table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $output,
            '#empty' => $this->t('No systems found'),
        ];

        // Display a form to add more systems
        $form['add_system'] = [
            '#type' => 'fieldset',
            '#title' => t('Add system'),
        ];
        
        $form['add_system']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#weight' => 0,
        ];
        
        $form['add_system']['driver_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Driver'),
            '#options' => $driver_list,
            '#weight' => 2,
        ];
        
        $form['add_system']['api_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Name'),
            '#weight' => 1,
        ];
        
        $form['add_system']['description'] = [
            '#type' => 'textarea',
            '#title' => t('Description'),
            '#weight' => 4,
        ];

        $form['add_system']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add +'),
            '#button_type' => 'primary',
            '#weight' => 5,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];
        $form['#attached']['library'][] = 'middleware_core/configuration.systems';

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.systems');

        // If the name is too short
        if (strlen($form_state->getValue('name')) < 1) {
            $form_state->setErrorByName('name', $this->t('The <b>Label</b> of a system cannot be blank'));
        }

        // If the API Name is too short
        else if (strlen($form_state->getValue('api_name')) < 1) {
            $form_state->setErrorByName('api_name', $this->t('The <b>API Name</b> of a system cannot be blank'));
        } 
        
        // If the name is not unique
        else if(!is_null($config->get($form_state->getValue('api_name')))) {
            $form_state->setErrorByName('api_name', $this->t('The <b>API Name</b> is already in use.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('middleware_core.systems');
        $config->set($form_state->getValue('api_name'), [
            'name' => $form_state->getValue('name'),
            'api_name' => $form_state->getValue('api_name'),
            'description' => $form_state->getValue('description'),
            'settings' => []
        ])->save();
    }
}