<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function mware_default_services_endpoint() {

    $prefered_name = variable_get('MWARE_ENDPOINT_PATH', '_api');

    $endpoints = [];

    $endpoint = new stdClass();
    $endpoint->disabled = FALSE;

    /* Edit this to true to make a default endpoint disabled initially */
    $endpoint->api_version = 3;
    $endpoint->name = 'Middleware';
    $endpoint->server = 'rest_server';
    $endpoint->path = $prefered_name;

    $endpoint->authentication = array(
        'services' => 'services',
    );

    $endpoint->server_settings = array(
        'formatters' => array(
            'bencode' => TRUE,
            'json' => TRUE,
            'jsonp' => FALSE,
            'php' => FALSE,
            'xml' => FALSE,
        ),
        'parsers' => array(
            'application/json' => TRUE,
            'application/vnd.php.serialized' => FALSE,
            'application/xml' => FALSE,
            'multipart/form-data' => TRUE,
            'text/xml' => FALSE,
            'application/x-www-form-urlencoded' => FALSE,
        ),
    );

    $endpoint->resources = [
        'system' => [
            'actions' => [
                'connect' => [
                    'enabled' => '1',
                ],
            ],
        ],
    ];

    drupal_alter('mware_default_service_operations', $endpoint->resources);

    $endpoint->debug = 0;
    $endpoints[$prefered_name] = $endpoint;

    return $endpoints;
}
