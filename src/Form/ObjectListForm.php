<?php

namespace Drupal\middleware_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures forms module settings.
 */
class ObjectListForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'middleware_core_object_settings';
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

        $form['information'] = [
            '#type' => 'vertical_tabs',
            '#default_tab' => 'sql'              
        ];

        $object_list = $config->get('objects');
        
        $options = [];
        foreach($object_list as $name => $data)
        {
            $options[$name] = [
                'Name' => $this->l(strtoupper($name), Url::fromUri("internal:/middleware_core/objects/sql/{$data['internal_name']}")), 
                'InternalName' => $data['internal_name'],// Url::fromUri('internal:/reports/search')), 
                'Description' => $data['description'],
                'Actions' => [
                    '#type' => 'markup',
                    // 'delete' => [
                    //     '#type' => [
                    //         '#type' => 'submit',
                    //         '#value' => $this->t('Delete')
                    //     ]
                    // ]
                ]
            ];
        }

        $form['table'] = [
            '#type' => 'table',
            '#header' => [
                'Name' => $this->t('Object name'),
                'InternalName' => $this->t('Internal name'),
                'Description' => $this->t('Description'),
                'Actions' => $this->t('Actions'),
            ],
            '#rows' => $options,   
        ];

        // This section allows you to add new configuration node.
        $form['add_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add new configuration'),
            '#prefix' => '<hr/>',
            '#tree' => TRUE
        ];

        $form['add_group']['display_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Object label'),
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Name of the entity')
            ],
        ];

        $form['add_group']['internal_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Internal name'),
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Internal name of the entity')
            ],
        ];

        $form['add_group']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#format' => 'plain',
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Describe this object')
            ]
        ];

        $form['add_group']['add'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add +'),
        ];

        // $form['add_group']['add']['#submit'][] =[$this, 'addObject'];
        // $form['add_group']['add']['#validate'][] = [$this, 'validateAddObject'];

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