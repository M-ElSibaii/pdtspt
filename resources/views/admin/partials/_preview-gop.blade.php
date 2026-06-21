{{-- One GOP card in the Preview editor. Expects $gop, $context, $dictRows, and the
     field descriptors $gopFields, $ctxFields, $dictFields. --}}
<div class="js-gop-card mt-4 border rounded shadow-sm" data-gop-id="{{ $gop->Id }}">
    <div class="px-4 py-2 border-b bg-slate-50 flex flex-wrap items-center gap-2">
        <span class="font-semibold">{{ $gop->gopNamePt ?: $gop->gopNameEn ?: 'Group' }}</span>
        <span class="text-xs text-gray-500">(Id={{ $gop->Id }} · <x-version-badge :version="$gop->versionNumber" :revision="$gop->revisionNumber" />)</span>
        <button type="button" class="btn btn-secondary js-remove-gop ml-auto" style="color:#7f1d1d;">Remove group</button>
    </div>

    <div class="p-4">
        {{-- GOP full attributes --}}
        <div class="js-gop-attrs">
            @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => (array) $gop, 'prefix' => 'gop' . $gop->Id, 'idAttr' => 'gop' . $gop->Id])
        </div>
        <div class="mt-2 flex items-center gap-3">
            <button type="button" class="btn btn-secondary js-save-gop">Save group attributes</button>
            <span class="js-gop-status text-sm"></span>
        </div>

        {{-- Properties --}}
        <div class="mt-4 font-semibold text-sm">Properties ({{ $context->count() }})</div>
        @foreach ($context as $c)
            @php $dictVals = isset($dictRows[$c->propertyId]) ? (array) $dictRows[$c->propertyId] : []; @endphp
            <div class="js-context border rounded p-3 mt-2 bg-gray-50" data-context-id="{{ $c->Id }}">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="font-semibold">{{ $c->dictNamePt ?: $c->dictNameEn ?: '(unnamed)' }}</span>
                    <span class="text-xs text-gray-500">dict Id={{ $c->propertyId }} · v{{ $c->dictVersion }}.{{ $c->dictRevision }} · {{ $c->dictStatus }}</span>
                    @if ($c->shared)
                        <span class="pe-shared-tag js-shared-tag" title="Shared with another PDT — editing the definition forks a Preview copy">shared (edits fork)</span>
                    @endif
                    <button type="button" class="btn btn-secondary js-remove-context ml-auto" style="color:#7f1d1d;">Remove</button>
                </div>

                {{-- This template's description of the property (context row) --}}
                <details>
                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">This template's description of the property (context)</summary>
                    <div class="js-ctx-attrs mt-2">
                        @include('admin.partials._attr-fields', ['fields' => $ctxFields, 'values' => (array) $c, 'prefix' => 'ctx' . $c->Id, 'idAttr' => 'ctx' . $c->Id])
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <button type="button" class="btn btn-secondary js-save-context">Save description</button>
                        <span class="js-context-status text-sm"></span>
                    </div>
                </details>

                {{-- Shared dictionary definition (full attrs; forks on edit if shared) --}}
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">Dictionary definition (full attributes — editing forks if shared)</summary>
                    <div class="js-def-attrs mt-2">
                        @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => $dictVals, 'prefix' => 'def' . $c->Id, 'idAttr' => 'def' . $c->Id, 'enums' => $dictEnums])
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <button type="button" class="btn btn-secondary js-save-def">Save definition</button>
                        <span class="js-def-status text-sm"></span>
                    </div>
                </details>
            </div>
        @endforeach

        {{-- Add properties (reworked picker) --}}
        @include('admin.partials._property-picker', ['gopId' => $gop->Id, 'gopNameEn' => $gop->gopNameEn, 'dictFields' => $dictFields, 'dictEnums' => $dictEnums])
    </div>
</div>
