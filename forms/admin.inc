<?php

function mware_admin() {
    
    $form['general_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('General settings'),
        '#description' => t('A sample configuration value'),
    );    

    $form['general_settings']['MWARE_ENDPOINT_PATH'] = array(
        '#type' => 'textfield',
        '#title' => t('Web service End-Point Path (REST)'),
        '#default_value' => variable_get('MWARE_ENDPOINT_PATH', '_rest'),
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => t('This is the path relative to the server where the financial REST api functions will be enabled.<br/>e.g. _x to host the service at http://server/_x'),
        '#required' => TRUE,
    );

    $form['general_settings']['MWARE_SOAP_ENDPOINT_PATH'] = array(
        '#type' => 'textfield',
        '#title' => t('Web service End-Point Path (SOAP)'),
        '#default_value' => variable_get('MWARE_SOAP_ENDPOINT_PATH', '_soap'),
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => t('This is the path relative to the server where the financial REST api functions will be enabled.<br/>e.g. _x to host the service at http://server/_x'),
        '#required' => TRUE,
    );

    $form['general_settings']['MWARE_INSTANCE_TYPE'] = array(
        '#type' => 'radios',
        '#title' => t('Instance type'),
        '#default_value' => variable_get('MWARE_INSTANCE_TYPE', 'sandbox'),
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => t('Define what this instance represents'),
        '#required' => TRUE,
        '#options' => ['sandbox' => t('Sandbox'),'staging' => t('Staging'),'production' => t('Production')],
    );

    $form['general_settings']['MWARE_INSTANCES'] = [
        '#type' => 'fieldset',
        '#title' => t('Related instances'),
        '#description' => t('Relate this instance to other instances')
    ];

    $form['general_settings']['MWARE_INSTANCES']['new_instance'] = [
        '#type' => 'button',
        '#value' => t('Add instance'),
        '#description' => t('Add a new instance')
    ];

    return system_settings_form($form);
}
