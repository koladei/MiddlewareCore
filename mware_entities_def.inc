<?php

include_once 'UserAttemptEntity.php';
include_once 'MiddlewareEventPropagationLogController.php';

/**
 * Implements hook_entity_info().
 */
function mware_entity_info() {

    $info = [];

    $info['mware_propagation_log'] = [
        'label' => t('PostIt Note'),
        'controller class' => 'MiddlewareEventPropagationLogController',
        'base table' => 'mware_event_propagation_log',
        'uri callback' => 'mware_propagation_log_uri',
        'fieldable' => TRUE,
        'entity keys' => [
            'id' => 'id',
        ],
        'static cache' => TRUE,
        'bundles' => [
            'mware_propagation_log' => [
                'label' => 'PostIt',
                'admin' => [
                    'path' => 'admin/structure/mware_propagation_log/manage',
                    'access arguments' => ['administer mware_propagation_logs'],
                ],
            ],
        ],
        'view modes' => [
            'full' => [
                'label' => t('Full PostIt'),
                'custom settings' => FALSE,
            ],
        ]
    ];

    return $info;
}

/**
 * Implements hook_entity_property_info().
 */
function mware_entity_property_info() {

    $info = [];

    $info['mware_propagation_log']['properties']['id'] = [
        'label' => t('Project ID'),
        'description' => t('The ID of the project.'),
        'type' => 'integer',
        'schema field' => 'id',
    ];
    
    $info['mware_propagation_log']['properties']['parent'] = [
        'label' => t('Parent ID'),
        'description' => t('The ID of the parent.'),
        'type' => 'integer',
        'schema field' => 'parent',
    ];

    $info['mware_propagation_log']['properties']['attempts'] = [
        'label' => t('Attempts'),
        'description' => t('The number of attempts'),
        'type' => 'integer',
        'schema field' => 'attempts',
    ];

    $info['mware_propagation_log']['properties']['action'] = [
        'label' => t('Action performed'),
        'description' => t('The action performed against the AD Username.'),
        'type' => 'text',
        'schema field' => 'action',
    ];

    $info['mware_propagation_log']['properties']['created'] = [
        'label' => t('Created'),
        'description' => t('When the action was attempted.'),
        'type' => 'date',
        'schema field' => 'created',
    ];

    $info['mware_propagation_log']['properties']['modified'] = [
        'label' => t('Modified'),
        'description' => t('When the action was last attempted.'),
        'type' => 'date',
        'schema field' => 'modified',
    ];

    return $info;
}

function mware_propagation_log_uri($mware_propagation_log) {
    return [
        'path' => 'mware_propagation_log/' . $mware_propagation_log->id,
    ];
}

function mware_propagation_log_load($id = NULL, $reset = FALSE) {
    $ids = (isset($id) ? [$id] : []);
    $mware_propagation_log = mware_propagation_log_load_multiple($ids, $reset);
    return $mware_propagation_log ? reset($mware_propagation_log) : FALSE;
}

function mware_propagation_log_load_multiple($ids = [], $conditions = [], $reset = FALSE) {
    return entity_load('mware_propagation_log', $ids, $conditions, $reset);
}

function mware_propagation_log_info() {

    $header = ['ID', 'Parent', 'Event Name', 'Attempts', 'Post Content', 'Reason', 'Status', 'Created', 'Modified'];
    $rows = [];

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'mware_propagation_log', '=');
    $result = $query->execute();
    if (isset($result['mware_propagation_log'])) {
        $mware_propagation_log_ids = array_keys($result['mware_propagation_log']);
        $results = entity_load('mware_propagation_log', $mware_propagation_log_ids);

        foreach ($results as $result) {
            $rows[] = [
                $result->id,
                $result->parent,
                l($result->event_name, 'mware_propagation_log/' . $result->id . '/edit'),
                $result->attempts,
                $result->post_content,
                $result->status_reason,
                $result->event_status,
                format_date($result->created, 'custom', 'Y-m-d h:i:s'),
                format_date($result->modified, 'custom', 'Y-m-d h:i:s')];
        }
    }

    return theme('table', array('header' => $header, 'rows' => $rows));
}

function mware_propagation_log_page_title($mware_propagation_log) {
    return $mware_propagation_log->event_name;
}

function mware_propagation_log_page_view($mware_propagation_log, $view_mode = 'full') {
    $mware_propagation_log->content = [];

    // Build fields content.
    field_attach_prepare_view('mware_propagation_log', [$mware_propagation_log->id => $mware_propagation_log], $view_mode);
    entity_prepare_view('mware_propagation_log', [$mware_propagation_log->id => $mware_propagation_log]);
    $mware_propagation_log->content += field_attach_view('mware_propagation_log', $mware_propagation_log, $view_mode);

    return $mware_propagation_log->content;
}

function mware_propagation_log_field_extra_fields() {
    $return = [];
    $return['mware_propagation_log']['mware_propagation_log'] = [
        'form' => [
            'attempts' => [
                'label' => t('Attempts'),
                'description' => t('PostIt Note'),
            ],
        ],
    ];

    return $return;
}

function mware_propagation_log_add() {
    $mware_propagation_log = (object) array(
                'id' => '',
                'type' => 'mware_propagation_log',
                'note' => '',
    );

    return drupal_get_form('mware_propagation_log_add_form', $mware_propagation_log);
}

function mware_propagation_log_add_form($form, &$form_state, $mware_propagation_log) {
    $form['note'] = array(
        '#type' => 'textfield',
        '#title' => t('Note'),
        '#required' => TRUE,
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
    );

    field_attach_form('mware_propagation_log', $mware_propagation_log, $form, $form_state);

    return $form;
}

function mware_propagation_log_add_form_validate($form, &$form_state) {
    $mware_propagation_log_submisttion = (object) $form_state['values'];
    field_attach_form_validate('mware_propagation_log', $mware_propagation_log_submisttion, $form, $form_state);
}

function mware_propagation_log_add_form_submit($form, &$form_state) {
    $mware_propagation_log_submission = (object) $form_state['values'];
    field_attach_submit('mware_propagation_log', $mware_propagation_log_submission, $form, $form_state);
    $mware_propagation_log = mware_propagation_log_save($mware_propagation_log_submission);
    $form_state['redirect'] = "mware_propagation_log/$mware_propagation_log->id";
}

function mware_propagation_log_save(&$mware_propagation_log) {
    return entity_get_controller('mware_propagation_log')->save($mware_propagation_log);
}
