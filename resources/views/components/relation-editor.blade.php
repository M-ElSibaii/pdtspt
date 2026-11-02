@props([
    'entityType',   // 'pdt' | 'gop' | 'property' | 'objecttype'
    'guid',         // source lineage GUID
    'title' => 'Relationships',
])

@php
    // Property uses IsSpecializationOf (0..1) and has no HasPart in this phase.
    $isProperty   = $entityType === 'property';
    $subtypeRel   = $isProperty ? 'IsSpecializationOf' : 'IsSubtypeOf';
    $subtypeLabel = $isProperty ? 'Specialization of' : 'Subtype of';
    $hasPart      = $isProperty ? '0' : '1';
    // Default target kind for the picker = same entity kind (self-referential).
    $targetType   = $entityType;
@endphp

<div class="js-relation-editor mt-4 border rounded shadow-sm"
     data-entity-type="{{ $entityType }}"
     data-guid="{{ $guid }}"
     data-subtype-rel="{{ $subtypeRel }}"
     data-subtype-label="{{ $subtypeLabel }}"
     data-haspart="{{ $hasPart }}"
     data-target-type="{{ $targetType }}">
    <div class="px-4 py-2 border-b bg-slate-50 font-semibold">{{ $title }}</div>
    <div class="p-4 text-sm">
        {{-- Subtype / specialization (0..1) --}}
        <div class="mb-4">
            <div class="font-semibold mb-1">{{ $subtypeLabel }} <span class="text-gray-500 text-xs">(0..1)</span></div>
            <div class="js-rel-parent text-gray-600">…</div>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <input type="text" class="js-rel-parent-q border rounded p-1 text-sm" placeholder="search to set/replace…">
                <button type="button" class="btn btn-secondary js-rel-parent-search">Search</button>
                <span class="js-rel-parent-status text-xs"></span>
            </div>
            <div class="js-rel-parent-results mt-1"></div>
        </div>

        {{-- HasPart (0..*) ordered --}}
        @if ($hasPart === '1')
        <div>
            <div class="font-semibold mb-1">Has part <span class="text-gray-500 text-xs">(0..*, ordered)</span></div>
            <ol class="js-rel-parts list-decimal ml-5"></ol>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <input type="text" class="js-rel-part-q border rounded p-1 text-sm" placeholder="search to add a part…">
                <button type="button" class="btn btn-secondary js-rel-part-search">Search</button>
                <span class="js-rel-part-status text-xs"></span>
            </div>
            <div class="js-rel-part-results mt-1"></div>
        </div>
        @endif
    </div>
</div>

@once
    @include('components.partials._relation-editor-script')
@endonce
