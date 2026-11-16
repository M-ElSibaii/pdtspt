{{-- Property origin filter. Wraps the list it filters: inside the slot, elements with
     data-origin="<key>" (a group or a property row) or data-origins="<key> <key>" (a heading
     that merges groups of several origins) are hidden when their origin is unchecked.
     $filter comes from PdtInheritanceService::origins(). Display only: downloads and the API
     are never filtered. Behaviour: resources/js/origin-filter.js. --}}
@props(['filter'])
@php
    $config = [
        'origins' => collect($filter['origins'])->map(fn($o) => ['key' => $o['key'], 'count' => $o['count']])->values(),
        'total' => $filter['total'],
        'shownTemplate' => Lg::t('A mostrar :shown de :total propriedades'),
    ];
@endphp
<div x-data="originFilter({{ \Illuminate\Support\Js::from($config) }})" {{ $attributes }}>
    <style>
        .origin-off { display: none !important; }
        .origin-bar { display:flex; flex-wrap:wrap; align-items:center; gap:8px 18px; padding:10px 12px; margin:0 0 10px;
                      border:1px solid #cbd5e1; border-radius:8px; background:#f8fafc; font-size:0.9rem; }
        .origin-bar legend { float:left; font-weight:600; margin-right:4px; padding:0; }
        .origin-bar label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; }
        .origin-bar input[type=checkbox] { width:16px; height:16px; }
        .origin-bar input[type=checkbox]:focus-visible, .origin-bar button:focus-visible { outline:2px solid #2563eb; outline-offset:2px; }
        .origin-swatch { display:inline-block; width:16px; height:16px; border:1px solid #94a3b8; border-radius:3px; }
        .origin-count { color:#475569; }
        .origin-status { flex-basis:100%; margin:0; color:#334155; }
        .origin-none { flex-basis:100%; margin:0; color:#991b1b; font-weight:600; }
    </style>
    <fieldset class="origin-bar">
        <legend>{{ Lg::t('Mostrar propriedades de:') }}</legend>
        @foreach ($filter['origins'] as $o)
            <label>
                <input type="checkbox" value="{{ $o['key'] }}" x-model="selected" checked>
                <span class="origin-swatch" style="background: {{ $o['color'] }};" aria-hidden="true"></span>
                <span>{{ $o['label'] }}</span>
                <span class="origin-count">({{ $o['count'] }})</span>
            </label>
        @endforeach
        <button type="button" class="btn btn-secondary" style="padding:2px 10px;" @click="showAll()" :disabled="allSelected">
            {{ Lg::t('Mostrar todas') }}
        </button>
       
    </fieldset>
    {{ $slot }}
</div>
