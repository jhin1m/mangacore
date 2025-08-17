<?php

namespace Ophim\Core\Traits;

trait HandlesDataTypes
{
    /**
     * Safely convert mixed data to string for display
     *
     * @param mixed $value
     * @param string $separator
     * @return string
     */
    public function safeToString($value, $separator = ', ')
    {
        if (is_array($value)) {
            return implode($separator, array_filter(array_map('trim', $value)));
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            if (method_exists($value, 'toArray')) {
                return $this->safeToString($value->toArray(), $separator);
            }
            return '';
        }
        
        if (is_null($value)) {
            return '';
        }
        
        return trim((string) $value);
    }

    /**
     * Safely convert string to array
     *
     * @param mixed $value
     * @param string $separator
     * @return array
     */
    public function safeToArray($value, $separator = ',')
    {
        if (is_array($value)) {
            return array_filter(array_map('trim', $value));
        }
        
        if (is_string($value) && !empty(trim($value))) {
            return array_filter(array_map('trim', explode($separator, $value)));
        }
        
        return [];
    }

    /**
     * Sanitize field value for safe HTML output
     *
     * @param mixed $value
     * @param bool $escape
     * @return string
     */
    public function sanitizeFieldValue($value, $escape = true)
    {
        $stringValue = $this->safeToString($value);
        
        return $escape ? e($stringValue) : $stringValue;
    }

    /**
     * Validate and convert value to appropriate type for database storage
     *
     * @param mixed $value
     * @param string $expectedType
     * @return mixed
     */
    public function validateAndConvertType($value, $expectedType)
    {
        switch ($expectedType) {
            case 'string':
                return $this->safeToString($value);
            
            case 'array':
                return $this->safeToArray($value);
            
            case 'integer':
                if (is_array($value)) {
                    $value = $this->safeToString($value);
                }
                return is_numeric($value) ? (int) $value : 0;
            
            case 'float':
                if (is_array($value)) {
                    $value = $this->safeToString($value);
                }
                return is_numeric($value) ? (float) $value : 0.0;
            
            case 'boolean':
                if (is_array($value)) {
                    return !empty($value);
                }
                return (bool) $value;
            
            default:
                return $value;
        }
    }

    /**
     * Check if value is safe for HTML output without conversion errors
     *
     * @param mixed $value
     * @return bool
     */
    public function isSafeForHtml($value)
    {
        // Arrays and objects need conversion before HTML output
        if (is_array($value) || is_object($value)) {
            return false;
        }
        
        // Null values are safe
        if (is_null($value)) {
            return true;
        }
        
        // Scalar values are generally safe
        return is_scalar($value);
    }

    /**
     * Prepare value for CRUD field display
     *
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    public function prepareForCrudField($value, $fieldType = 'text')
    {
        switch ($fieldType) {
            case 'textarea':
            case 'text':
            case 'email':
            case 'url':
                return $this->sanitizeFieldValue($value, false);
            
            case 'select':
            case 'select2':
                // For select fields, ensure we return the raw value
                return is_array($value) ? $value : [$value];
            
            case 'number':
                return $this->validateAndConvertType($value, 'integer');
            
            case 'checkbox':
                return $this->validateAndConvertType($value, 'boolean');
            
            default:
                return $this->isSafeForHtml($value) ? $value : $this->sanitizeFieldValue($value, false);
        }
    }
}