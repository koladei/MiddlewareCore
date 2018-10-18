<?php

namespace Drupal\middleware_core\MiddlewareCore;

use Drupal\middleware_core\MiddlewareCore\EntityDefinitionBrowser;
use Drupal\middleware_core\MiddlewareCore\EncoderDecoder;
use Drupal\middleware_core\MiddlewareCore\InvalidFieldSelectedException;


class MiddlewareEntity extends \stdClass
{

    public function __construct(\stdClass $val = null)
    {
        if (!is_null($val)) {
            foreach ($val as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }
}