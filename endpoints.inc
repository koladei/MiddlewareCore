<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function mware_default_services_endpoint() {

    $prefered_name = variable_get('MWARE_ENDPOINT_PATH');
    $prefered_soap_name = variable_get('MWARE_SOAP_ENDPOINT_PATH');

    $endpoints = [];

    if (module_exists('rest_server')) {

        //Define the REST endpoint.
        $endpoint_rest = new stdClass();
        $endpoint_rest->disabled = FALSE;

        /* Edit this to true to make a default endpoint disabled initially */
        $endpoint_rest->api_version = 3;
        $endpoint_rest->name = 'Middleware: REST';
        $endpoint_rest->server = 'rest_server';
        $endpoint_rest->path = $prefered_name;

        $endpoint_rest->authentication = array(
            'services' => 'services',
        );

        $endpoint_rest->server_settings = array(
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

        $endpoint_rest->resources = [
            'system' => [
                'actions' => [
                    'connect' => [
                        'enabled' => '1',
                    ],
                ],
            ],
        ];

        //Load all REST endpoint resources
        drupal_alter('mware_default_service_operations', $endpoint_rest->resources);

        $endpoint_rest->debug = 0;
        $endpoints[$prefered_name] = $endpoint_rest;
    }

    if (module_exists('soap_server')) {
        //Define the SOAP endpoint.
        $endpoint_soap = new stdClass();
        $endpoint_soap->disabled = FALSE;

        /* Edit this to true to make a default endpoint disabled initially */
        $endpoint_soap->api_version = 3;
        $endpoint_soap->name = 'Middleware: SOAP';
        $endpoint_soap->server = 'soap_server';
        $endpoint_soap->path = $prefered_soap_name;

        $endpoint_soap->authentication = array(
            'services' => 'services',
        );

        $endpoint_soap->resources = [
            'system' => [
                'actions' => [
                    'connect' => [
                        'enabled' => '1',
                    ],
                ],
            ],
        ];

        //Load all SOAP endpoint resources
        drupal_alter('mware_default_service_operations', $endpoint_soap->resources);

        $endpoint_soap->debug = 0;
        $endpoints[$prefered_soap_name] = $endpoint_soap;
    }

    return $endpoints;
}
