<?php

namespace Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter;

use Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter\FilterBase;
use Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter\IFilterGroup;

class FilterGroup extends FilterBase implements IFilterGroup {

    public $parts = [];
    
    public function __construct($behaviour = FilterBase::DEFAULT_STRINGER, callable $stringer = NULL){
        parent::__construct($behaviour, $stringer);        
    }

    public function addPart(FilterBase &$fragment, $type = self::FRAGMENT_AND){
        $this->parts[] = [
            $type, $fragment
        ];
    }

    public function removePart(FilterBase &$fragment){
        // TODO: write code to find the fragment and then remove it.
        return $this;
    }

    protected function LDAPStringer(FilterBase &$context){
        $ret = '';

        $partCount = count($context->parts);
        if($partCount > 0){
            $x = '';
            $y = 0;
            $prev = FilterGroup::FRAGMENT_AND;
            if($partCount > 1){
                for($index = 0; $index < $partCount; $index++){
                    $part = $context->parts[$index];
                    $conjection = ($part[0] == FilterGroup::FRAGMENT_OR) ? '|': (($part[0] == FilterGroup::FRAGMENT_AND) ? '&' : '');
                    $fragment = $part[1];
                    
                    if($index == 1){
                        $prev = $conjection;
                    }

                    if($conjection == $prev){
                        $x .= substr($fragment, 0, 1) == '('? $fragment: "({$fragment})";

                        // There a no further part components
                        if(($index + 1) >= $partCount){
                            $ret = ($y > 0)?"({$prev}{$x})":"{$x}";
                            $y = 0;
                        } else {
                            $y += 1;
                        }
                    } 
                    else{
                        $ret = ($y > 1)?"({$prev}{$ret}{$x})":"{$x}";
                        $x = "({$fragment})";
                        $prev = $conjection;
                        $y += 1;

                        if(($index + 1) >= $partCount){
                            $ret = ($y > 0)?"({$conjection}{$ret}{$x})":"{$x}";
                            $y = 0;
                        } else {
                            $y += 1;
                        }
                    }
                }
            } else {
                $part = $context->parts[0];
                $ret = "{$part[1]}";
            }
        }

        return $ret;
    }

    protected function DEFAULTStringer(FilterBase &$context){
        $ret = '';
        foreach($this->parts as $index => $part){
            $conjection = ($part[0] == self::FRAGMENT_OR) ? 'or': (($part[0] == self::FRAGMENT_AND) ? 'and' : '');
            $fragment = $part[1];
            $ret .= ($index == 0) ? "({$fragment}" : " {$conjection} {$fragment}";
        }
        $ret .= (strlen($ret) > 0) ? ')' : '';

        return $ret;
    }

    protected function SOQLStringer(FilterBase &$scope){
        $ret = '';

        foreach($this->parts as $index => $part){
            $conjection = ($part[0] == self::FRAGMENT_OR) ? 'OR': (($part[0] == self::FRAGMENT_AND) ? 'AND' : '');
            $fragment = $part[1];
            $ret .= ($index == 0) ? "({$fragment})" : " {$conjection} ({$fragment})";
        }

        return $ret;
    }

    protected function BMCStringer(FilterBase &$scope){
        $ret = '';

        foreach($this->parts as $index => $part){
            $conjection = ($part[0] == self::FRAGMENT_OR) ? 'OR': (($part[0] == self::FRAGMENT_AND) ? 'AND' : '');
            $fragment = $part[1];
            $ret .= ($index == 0) ? "({$fragment})" : " {$conjection} ({$fragment})";
        }

        return $ret;
    }

    protected function SQLStringer(FilterBase &$scope){
        $ret = '';

        foreach($this->parts as $index => $part){
            $conjection = ($part[0] == self::FRAGMENT_OR) ? 'OR': (($part[0] == self::FRAGMENT_AND) ? 'AND' : '');
            $fragment = $part[1];
            $ret .= ($index == 0) ? "({$fragment})" : " {$conjection} ({$fragment})";
        }

        return $ret;
    }

    protected function XPPStringer(FilterBase &$scope){
        $ret = '';

        foreach($this->parts as $index => $part){
            $conjection = ($part[0] == self::FRAGMENT_OR) ? 'or': (($part[0] == self::FRAGMENT_AND) ? 'and' : '');
            $fragment = $part[1];
            // $ret .= ($index == 0) ? "({$fragment}" : " {$conjection} {$fragment}";
            $ret .= ($index == 0) ? "({$fragment})" : " {$conjection} ({$fragment})";
        }
        // $ret .= (strlen($ret) > 0) ? ')' : '';

        return $ret;
    }

    // $processor(Filter $e);
    public function setStringifier(callable $processor = NULL){
        $this->stringer = $processor;
    }
}
