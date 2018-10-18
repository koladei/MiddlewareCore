<?php

namespace Drupal\middleware_core\MiddlewareCore;

use Drupal\middleware_core\MiddlewareCore\EntityDefinitionBrowser;
use Drupal\middleware_core\MiddlewareCore\EncoderDecoder;
use Drupal\middleware_core\MiddlewareCore\InvalidFieldSelectedException;

class MiddlewareComplexEntity extends MiddlewareEntity
{
    public function getByKey($key, $isMany = false)
    {
        return $isMany?[]:null;
    }
}
