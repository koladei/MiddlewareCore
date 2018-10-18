<?php

namespace Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter;

use Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter\FilterBase;
use Drupal\middleware_core\MiddlewareCore\EntityDefinitionBrowser;
use Drupal\middleware_core\MiddlewareCore\EncoderDecoder;

class Filter extends FilterBase {

    protected $parent = NULL;
    public $field;
    public $value;
    public $operator;
    private $quote = '';
    private $fieldInfo = NULL;
    private $ors = [];
    private $ands = [];
    private $processor = NULL;
    private $entityDefinition = NULL;

    const EQUAL_TO = 'eq';
    const LESS_THAN = 'lt';
    const LESS_THAN_EQUAL_TO = 'le';
    const GREATER_THAN = 'gt';
    const GREATER_THAN_EQUAL_TO = 'ge';
    const NOT_EQUAL_TO = 'ne';
    const STARTS_WITH = 'startswith';
    const ENDS_WITH = 'endswith';
    const SUBSTRING_OF = 'substringof';
    const IN = 'in';

    public function __construct(EntityDefinitionBrowser $entityDefinition = NULL, $field, $value, $operator = EQUAL_TO, $quote = '', $formater = '', $context = NULL, $behaviour = self::DEFAULT_STRINGER, callable $stringer = NULL) {
        parent::__construct($behaviour, $stringer);
        $this->entityDefinition = $entityDefinition;

        if ($operator == self::IN) {
            if (!is_array($value)) {
                throw new \Exception("IN filter expects an array value, {gettype($value)} given.");
            }
        }

        // Get the internal name of the field
        $fieldInfo = !is_null($entityDefinition) ? $entityDefinition->getFieldByDisplayName($field) : NULL;
        $this->fieldInfo = $fieldInfo;
        $this->field = !is_null($fieldInfo) ? $fieldInfo->getQueryName() : $field;
        $this->quote = $quote;

        if ($formater == 'datetime') {
            $this->value = $this->getDateTime($value);
        } else if ($formater == 'field') {
            // Get the field
            $this->value = $this->getField($value);
        } else if (is_string($value) && strtolower($value) == '$now$') {
            $this->value = new \DateTime();
            $this->quote = '\'';
        } else if (is_string($value) && strtolower($value) == '$true$') {
            $this->value = TRUE;
            $this->quote = '';
        } else if (is_string($value) && strtolower($value) == '$false$') {
            $this->value = FALSE;
            $this->quote = '';
        } else if (is_string($value) && strtolower($value) == '$today$') {
            $this->value = new \DateTime();
            $this->value->setTime(0, 0);
            $this->quote = '\'';
        } else if (is_string($value) && strtolower($value) == '$null$') {
            $this->value = NULL;
        } else if (is_string($value) && strtolower($value) == '$blank$') {
            $this->value = '';
            $this->quote = '\'';
        } else {
            $this->value = $value;
        }
        $this->operator = strtolower($operator);

        if (is_null($fieldInfo)) {
            $this->quote = (strlen($quote) > 0) ? '\'' : '';
        } else {
            if (!is_null($this->value)) {
                if (in_array($fieldInfo->getDataType(), ['int', 'bigint', 'decimal']) && strlen($this->quote) > 0) {
                    throw new \Exception("Field {$fieldInfo->getDisplayName()} is either an integer or decimal field. Quotes are not allowed for integer fields.");
                } else if ($fieldInfo->getDataType() == 'boolean' && strlen($this->quote) > 0) {
                    throw new \Exception("Field {$fieldInfo->getDisplayName()} is a boolean field. Quotes are not allowed for boolean fields.");
                } else if (!in_array($fieldInfo->getDataType(), ['int', 'bigint', 'boolean']) && strlen($this->quoteValue()) < 1) {
                    throw new \Exception("Field {$fieldInfo->getDisplayName()} requires that it's values be quoted. {$value}");
                } else if ((!in_array($fieldInfo->getDataType(), ['int', 'bigint', 'boolean', 'decimal']) && strlen($this->quote) > 1) || (!in_array($fieldInfo->getDataType(), ['int', 'bigint', 'boolean', 'decimal']) && ($this->quote != '"' && $this->quote != '\''))) {
                    throw new \Exception("Field {$fieldInfo->getDisplayName()} {$fieldInfo->getDataType()} only supports qoutes of type ''' or '\"'.");
                } else {
                    $this->quote = (strlen($this->quote) > 0) ? '\'' : '';
                }
            }
        }
    }

    private function quoteValue() {
        // Implement checking if field is meant to be a string or otherwise
        $backslash = '\\';
        if (is_array($this->value)) {
            $im = implode("_x0027_,_x0027_", $this->value);
            $im = str_replace("{$this->quote}", "{$backslash}{$this->quote}", $im);
            $im = str_replace("_x0027_", "{$this->quote}", $im);

            return "{$this->quote}{$im}{$this->quote}";
        } else if ($this->value instanceof \DateTime) {
            return $this->value->format('Y-m-d\\TH:i:s');
        } else {
            $return = "_x0027_{$this->value}_x0027_";
            $return = str_replace("{$this->quote}", "{$backslash}{$this->quote}", $return);
            $return = "{$this->quote}{$this->value}{$this->quote}";

            return $return;
        }
    }

    private function quoteValueIn(){
        // Implement checking if field is meant to be a string or otherwise
        if (is_array($this->value)) {
            $im = implode("{$this->quote},{$this->quote}", $this->value);
            $im = str_replace('\'\',', '', $im);
            $im = str_replace('\'\'', '', $im);
            return $im != '\'\''? "{$this->quote}{$im}{$this->quote}":'';
        } else if ($this->value instanceof \DateTime) {
            return $this->value->format('Y-m-d\\TH:i:s');
        } else {
            return "{$this->quote}{$this->value}{$this->quote}";
        }
    }
    
    private function getDateTime($value) {
        $type_1 = '/^(([\d]{4})\-([\d]{2})\-([\d]{2})(T([\d]{2})\:([\d]{2})(\:([\d]{2}))?)?)$/';
        $type_2 = '/^(([\d]{4})\-([\d]{2})\-([\d]{2})(T([\d]{2})\:([\d]{2})))$/';
        $type_3 = '/^([\d]{4})\\-([\d]{2})\-([\d]{2})$/';

        if (preg_match($type_3, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d', $value);
        } else if (preg_match($type_2, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d\\TH:i', $value);
        } else if (preg_match($type_1, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d\\TH:i:s', $value);
        }

        throw new \Exception("The time format is not known. Class Filter {$value}");
    }
    
    private function getField($field) {
        $fieldInfo = NULL;
        $sign = substr($field, 0, 1);
        
        if(($sign == '-') || ($sign == '+')){
            $fieldInfo = $this->entityDefinition->getFieldByDisplayName(substr($field, 1));
            return "{$sign}(_xENTITYNAME_{$fieldInfo->getInternalName()})";
        } else {
            $fieldInfo = $this->entityDefinition->getFieldByDisplayName($field);
            return "_xENTITYNAME_{$fieldInfo->getInternalName()}";
        }
    }

    // $processor(Filter $e);
    public function setStringifier(callable $processor = NULL) {
        $this->stringifier = $processor;
    }

    protected function LDAPStringer(FilterBase &$context) {
        $ret = '';

        $value = str_replace("\\'", "'", EncoderDecoder::unescape($this->value));
        if ($value instanceof \DateTime) {
            // $value = $value->format('Y-m-d\\TH:i:s');
            $epoch = new \DateTime('1601-01-01');
            $interval = $epoch->diff($value);
            $value = ($interval->days * 24 * 60 * 60);
        } else {
            if (is_null($value)) {
                $value = '\\00';
            }
        }

        switch ($context->operator) {
            case self::STARTS_WITH: {
                    $ret = "{$context->field}={$value}*";
                    break;
                }
            case self::ENDS_WITH: {
                    $ret = "{$context->field}=*{$value}";
                    break;
                }
            case self::SUBSTRING_OF: {
                    $ret = "{$context->field}=*{$value}*";
                    break;
                }
            case self::EQUAL_TO: {
                    if (is_null($value)) {
                        $ret = "!{$context->field}=*";
                    } else {
                        $ret = "{$context->field}={$value}";
                    }
                    break;
                }
            case self::NOT_EQUAL_TO: {
                    if (is_null($value)) {
                        $ret = "{$context->field}=*";
                    } else {
                        $ret = "!{$context->field}={$value}";
                    }
                    break;
                }
            case self::GREATER_THAN: {
                    $ret = "{$context->field}>{$value}";
                    break;
                }
            case self::GREATER_THAN_EQUAL_TO: {
                    $ret = "{$context->field}>={$value}";
                    break;
                }
            case self::LESS_THAN: {
                    $ret = "{$context->field}<{$value}";
                    break;
                }
            case self::LESS_THAN_EQUAL_TO: {
                    $ret = "{$context->field}>={$value}";
                    break;
                }
            case self::IN: {
                    $im = implode(")({$this->field}=", $value);
                    if (count($value) > 1) {
                        $ret = "(|({$this->field}={$im}))";
                    } else {
                        $ret = "({$this->field}={$im})";
                    }
                    break;
                }
            default: {
                    $ret = "{$context->field} {$context->operator} {$value}";
                }
        }

        $ret = EncoderDecoder::unescape($ret);

        return $ret;
    }

    protected function DEFAULTStringer(FilterBase &$context) {
        $ret = '';

        $value = $context->value;
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d\\TH:i:s');
        }

        if (is_null($value)) {
            $value = '0';
        }

        switch ($this->operator) {
            case self::STARTS_WITH:
            case self::ENDS_WITH:
            case self::SUBSTRING_OF: {
                    $ret = "{$this->operator}({$this->field},{$value})";
                    break;
                }
            case self::IN: {
                    $ret = "{$this->field}{$this->operator}({$this->quoteValue()})";
                    break;
                }
            default: {
                    $ret = "{$this->field} {$this->operator} {$value}";
                }
        }
        $ret = EncoderDecoder::unescape($ret);

        return $ret;
    }

    protected function SOQLStringer(FilterBase &$scope) {
        $ret = '';

        $value = $this->value;
        $q = "'";

        if(!is_null($this->fieldInfo)){
            switch($this->fieldInfo->getDataType()){
                case 'int':
                case 'bigint':
                case 'boolean':
                case 'decimal': {
                    $q = '';
                    break;
                }
            }
        }

        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d\\TH:i:s\\Z');
        } else if (is_null($value)) {
            $value = 'NULL';
        } else if (is_bool($value)) {
            $q = '';
            $value = $value ? 'TRUE':'FALSE';
        }

        switch ($this->operator) {
            case self::STARTS_WITH: {
                    $ret = "{$this->field} LIKE {$q}{$value}%{$q}";
                    break;
                }
            case self::ENDS_WITH: {
                    $ret = "{$this->field} LIKE {$q}%{$value}{$q}";
                    break;
                }
            case self::SUBSTRING_OF: {
                    $ret = "{$this->field} LIKE {$q}%{$value}%{$q}";
                    break;
                }
            case self::NOT_EQUAL_TO: {
                    $ret = "{$this->field} != {$q}{$value}{$q}";
                    break;
                }
            case self::EQUAL_TO: {
                    $ret = "{$this->field} = {$q}{$value}{$q}";
                    break;
                }
            case self::GREATER_THAN: {
                    $ret = "{$this->field} = {$q}{$value}{$q}";
                    break;
                }
            case self::GREATER_THAN_EQUAL_TO: {
                    $ret = "{$this->field} >= {$value}";
                    break;
                }
            case self::LESS_THAN: {
                    $ret = "{$this->field} < {$value}";
                    break;
                }
            case self::LESS_THAN_EQUAL_TO: {
                    $ret = "{$this->field} <= {$value}";
                    break;
                }
            case self::IN: {
                    $ret = "{$this->field} IN({$this->quoteValueIn()})";
                    break;
                }
            default: {
                    throw new \Exception('Unknown query operand encountered.');
                }
        }
        $ret = EncoderDecoder::unescape($ret);

        return $ret;
    }

    protected function BMCStringer(FilterBase &$scope) {
        $ret = '';

        $field = $this->field;
        $value = $this->value;
        if ($value instanceof \DateTime) {
            $value = $value->format('"m/d/Y H:i:s"');
        } else if (is_null($value)) {
            $value = 'NULL';
        } else if(is_string($value)){
            $value = "\"{$value}\"";
        }else{
            $value = $this->quoteValue();
        }

        switch ($this->operator) {
            case self::STARTS_WITH: {
                    $value = is_string($value)?substr($value, 1, strlen($value) - 2):$value;
                    $ret = "'{$field}' LIKE \"{$value}%\"";
                    break;
                }
            case self::ENDS_WITH: {
                    $value = is_string($value)?substr($value, 1, strlen($value) - 2):$value;
                    $ret = "'{$field}' LIKE \"%{$value}\"";
                    break;
                }
            case self::SUBSTRING_OF: {
                    $value = is_string($value)?substr($value, 1, strlen($value) - 2):$value;
                    $ret = "'{$field}' LIKE \"%{$value}%\"";
                    break;
                }
            case self::NOT_EQUAL_TO: {
                    $ret = "'{$field}' != {$value}";
                    break;
                }
            case self::EQUAL_TO: {
                    $ret = "'{$field}' = {$value}";
                    break;
                }
            case self::GREATER_THAN: {
                    $ret = "'{$field}' > {$value}";
                    break;
                }
            case self::GREATER_THAN_EQUAL_TO: {
                    $ret = "'{$field}' >= {$value}";
                    break;
                }
            case self::LESS_THAN: {
                    $ret = "'{$field}' < {$value}";
                    break;
                }
            case self::LESS_THAN_EQUAL_TO: {
                    $ret = "'{$field}' <= {$value}";
                    break;
                }
            case self::IN: {
                    $ret = "";
                    foreach($this->value as $v){
                        $ret = "{$ret}'{$field}' = \"{$v}\" OR ";
                    }
                    $ret = strlen($ret)>0?substr($ret, 0, strlen($ret) - 3):"";
                    break;
                }
            default: {
                    throw new \Exception('Unknown query operand encountered.');
                }
        }

        // Restore excaped quotes
        $ret = EncoderDecoder::unescape($ret);

        return $ret;
    }

    protected function SQLStringer(FilterBase &$scope) {
        $ret = '';

        $value = $this->value;
        $q = "'";
        if ($value instanceof \DateTime) {
            $value = $value->format('\'Y-m-d H:i:s\'');
        } else if (is_null($value)) {
            $value = 'NULL';
        } else if (is_bool($value)) {
            $q = '';
            $value = $value ? '1':'0';
        } else {
            $value = $this->quoteValue();
        }

        switch ($this->operator) {
            case self::STARTS_WITH: {
                    $ret = "{$this->field} LIKE '{$this->value}%'";
                    break;
                }
            case self::ENDS_WITH: {
                    $ret = "{$this->field} LIKE '%{$this->value}'";
                    break;
                }
            case self::SUBSTRING_OF: {

                    $ret = "{$this->field} LIKE '%{$this->value}%'";
                    break;
                }
            case self::NOT_EQUAL_TO: {
                    if(is_null($value) || strtolower($value) == 'null'){
                        $ret = "{$this->field} IS NOT NULL";
                    } else {                        
                        $ret = "{$this->field} != {$value}";
                    }
                    break;
                }
            case self::EQUAL_TO: {
                    $ret = "{$this->field} = {$value}";
                    break;
                }
            case self::GREATER_THAN: {
                    $ret = "{$this->field} > {$value}";
                    break;
                }
            case self::GREATER_THAN_EQUAL_TO: {
                    $ret = "{$this->field} >= {$value}";
                    break;
                }
            case self::LESS_THAN: {
                    $ret = "{$this->field} < {$value}";
                    break;
                }
            case self::LESS_THAN_EQUAL_TO: {
                    $ret = "{$this->field} <= {$value}";
                    break;
                }
            case self::IN: {
                    $ret = "{$this->field} {$this->operator}({$this->quoteValue()})";
                    break;
                }
            default: {
                    throw new \Exception('Unknown query operand encountered.');
                }
        }

        // Restore excaped quotes
        $ret = EncoderDecoder::unescape($ret);
        return $ret;
    }

    protected function XPPStringer(FilterBase &$context) {
        $ret = '';

        $value = $this->value;
        if ($value instanceof \DateTime) {
            if($this->fieldInfo->isDate()){
                $value = "{$value->format('d\\Tm\\TY')}";
                $value = \str_replace('T', '\\', $value);
            } else {
                $value = "{$value->format('Y-m-d\\TH:i:s')}";
            }
        } else if (is_null($value)) {
            if($this->fieldInfo->isDate()){
                $value = 'datenull()';
            } else if($this->fieldInfo->isDateTime()){
                $value = 'utcdatetimenull()';
            } else {
                $value = '\'\'';
            }
        } else {
            $value = $this->quoteValue();
        }

        switch ($this->operator) {
            case self::STARTS_WITH:
            case self::ENDS_WITH:
            case self::SUBSTRING_OF: {
                    $ret = "{$this->operator}({$this->field},{$value})";
                    break;
                }
            // case self::STARTS_WITH: {
            //         $ret = "{$this->field} LIKE '{$this->value}*'";
            //         break;
            //     }
            // case self::ENDS_WITH: {
            //         $ret = "{$this->field} LIKE '*{$this->value}'";
            //         break;
            //     }
            // case self::SUBSTRING_OF: {
            //         $ret = "{$this->field} LIKE '*{$this->value}*'";
            //         break;
            //     }
            case self::NOT_EQUAL_TO:
            case self::EQUAL_TO:
            case self::GREATER_THAN:
            case self::GREATER_THAN_EQUAL_TO:
            case self::LESS_THAN:
            case self::LESS_THAN_EQUAL_TO: {
                    $ret = "{$this->field} {$this->operator} {$value}";
                    break;
                }
            case self::IN: {
                    $ret = "{$this->field} {$this->operator}({$this->quoteValue()})";
                    break;
                }
            default: {
                    throw new \Exception('Unknown query operand encountered.');
                }
        }

        // Restore excaped quotes
        $ret = EncoderDecoder::unescape($ret);
        return $ret;
    }

}
