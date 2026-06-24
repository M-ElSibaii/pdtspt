@props(['status'])
@php
    // Maps the 3-state status to its existing pill class + Portuguese label.
    // Centralises what was duplicated (and inconsistently labelled) across the views,
    // and ensures Preview renders as Preview rather than falling through to "Inativa".
    $map = [
        'Active'   => ['cls' => 'status-tag-active',   'label' => 'Ativa'],
        'InActive' => ['cls' => 'status-tag-inactive', 'label' => 'Inativa'],
        'Preview'  => ['cls' => 'status-tag-preview',  'label' => 'Preview'],
    ];
    $s = $map[$status] ?? ['cls' => 'status-tag-inactive', 'label' => $status];
@endphp
<span {{ $attributes->merge(['class' => 'status-tag ' . $s['cls']]) }}>{{ $s['label'] }}</span>
