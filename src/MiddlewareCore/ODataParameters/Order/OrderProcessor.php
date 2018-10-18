<?php


namespace Drupal\middleware_core\MiddlewareCore\ODataParameters\Order;

use Drupal\middleware_core\MiddlewareCore\ODataParameters\Order\Order;
use Drupal\middleware_core\MiddlewareCore\EntityDefinitionBrowser;

/**
 * Description of OrderProcessor
 *
 * @author Kolade.Ige
 */
class OrderProcessor {

    private $orderSegments = [];

    public static function convert(EntityDefinitionBrowser $entityDefinition, $expression, callable $translator){
        $orderProcessor = new OrderProcessor($entityDefinition, $expression);
        $translation = '';
        foreach($orderProcessor->getOrderSegments() as $segment){
            $translation .= "{$translator($segment)},";
        }

        return trim($translation, ',');
    }

    private function __construct(EntityDefinitionBrowser $entityDefinition, $expression) {

        // In operator
        $matchs = [];
        
        preg_match_all('/([\w][\w\d]*)\s*((asc|desc)\s*[\,]?)?/i', $expression, $matchs, PREG_SET_ORDER);
        foreach ($matchs as $mat) {
            $key = $mat[1];
            $this->orderSegments[$key] = new Order($entityDefinition, $mat[1], $mat[3]);
        }
    }

    private function getOrderSegments(){
        return $this->orderSegments;
    }
}
