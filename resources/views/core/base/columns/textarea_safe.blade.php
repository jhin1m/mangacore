{{-- Enhanced textarea column with array data handling --}}
@php
    $value = data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 50;
    $column['suffix'] = $column['suffix'] ?? '...';

    // Defensive type checking - handle array data gracefully
    if (is_array($value)) {
        $value = implode(', ', array_filter($value));
    }
    
    // Ensure we have a string
    $value = (string) $value;
    
    // Apply limit and strip tags if not empty
    if (!empty($value)) {
        $value = Str::limit(strip_tags($value), $column['limit'], $column['suffix']);
    }
    
    // Escape output if needed
    if ($column['escaped']) {
        $value = e($value);
    }
@endphp

<span>{{ $value }}</span>