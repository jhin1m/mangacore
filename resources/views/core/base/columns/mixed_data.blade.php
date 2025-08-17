{{-- Mixed data column - handles arrays, strings, and objects gracefully --}}
@php
    $value = data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 50;
    $column['suffix'] = $column['suffix'] ?? '...';
    $column['separator'] = $column['separator'] ?? ', ';

    // Handle different data types gracefully
    if (is_array($value)) {
        $value = implode($column['separator'], array_filter(array_map('trim', $value)));
    } elseif (is_object($value)) {
        if (method_exists($value, '__toString')) {
            $value = (string) $value;
        } elseif (method_exists($value, 'toArray')) {
            $arrayValue = $value->toArray();
            $value = implode($column['separator'], array_filter(array_map('trim', $arrayValue)));
        } else {
            $value = '';
        }
    } elseif (is_null($value)) {
        $value = '';
    } else {
        $value = (string) $value;
    }
    
    // Trim and apply limit if not empty
    $value = trim($value);
    if (!empty($value)) {
        $value = Str::limit(strip_tags($value), $column['limit'], $column['suffix']);
    }
    
    // Escape output if needed
    if ($column['escaped']) {
        $value = e($value);
    }
@endphp

<span title="{{ $value }}">{{ $value }}</span>