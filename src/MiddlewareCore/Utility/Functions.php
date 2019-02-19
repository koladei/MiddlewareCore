<?php

namespace Drupal\middleware_core\MiddlewareCore\Utility;

use Drupal\Core\Logger\RfcLogLevel;

class Functions {
    public static function GetMiddlewareDriver($name){
        return middleware_core__get_driver($name);
    }

    public static function Log($title, $message, $severity = RfcLogLevel::NOTICE){
        \Drupal::logger($title)->log($severity, $message, []);
    }
}