<?php 

namespace Drupal\middleware_core\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class MiddlewarePathProcessor implements InboundPathProcessorInterface {

    public function processInbound($path, Request $request) {
        
        if (strpos($path, '/rest/') === 0) {
            $names = preg_replace('|^\/rest\/|', '', $path);
            $names = str_replace('/',':', $names);

            return "/rest/$names";
        }

        return $path;
    }
}