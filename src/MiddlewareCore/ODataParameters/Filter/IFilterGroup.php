<?php

namespace Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter;

use Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter\FilterBase;

interface IFilterGroup {
   
    const FRAGMENT_OR = 0;
    const FRAGMENT_AND = 1;

    public function addPart(FilterBase &$fragment, $type = self::FRAGMENT_AND);
    public function removePart(FilterBase &$fragment);
}
