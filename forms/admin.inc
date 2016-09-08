<?php

function mware_admin() {
    
    $form['general_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('General settings'),
        '#description' => t('A sample configuration value'),
    );    

    $form['general_settings']['MWARE_ENDPOINT_PATH'] = array(
        '#type' => 'textfield',
        '#title' => t('Web service End-Point Path'),
        '#default_value' => variable_get('MWARE_ENDPOINT_PATH', '_api'),
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => t('This is the path relative to the server where the financial REST api functions will be enabled.<br/>e.g. _x to host the service at http://server/_x'),
        '#required' => TRUE,
    );

    return system_settings_form($form);
}
