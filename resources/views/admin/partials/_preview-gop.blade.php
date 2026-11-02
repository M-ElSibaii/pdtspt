{{-- One group of properties in the draft editor. Collapsed by default; each property is a
     compact row whose full edit forms are rendered only when opened (<template> + JS), so a
     large template stays fast and scannable. Expects $gop, $context, $dictRows, the field
     descriptors ($gopFields, $ctxFields, $dictFields, $dictEnums), $marker
     ('added'|'modified'|'unchanged'|null) and $ctxMarkers ([contextId => 'added'|'changed']). --}}
@php
    $count = $context->count();
@endphp
<details class="pe-gop js-gop-card" id="gop-{{ $gop->Id }}" data-gop-id="{{ $gop->Id }}">
    <summary class="pe-gop-sum">
        <span class="pe-chev" aria-hidden="true"></span>
        <span class="pe-gop-title">
            <strong>{{ $gop->gopNameEn ?: '—' }}</strong>
            <span class="adm-muted">/ {{ $gop->gopNamePt ?: '—' }}</span>
        </span>
        @if ($marker === 'added' || $marker === 'modified')
            <span class="adm-pill is-{{ $marker }}">{{ $marker === 'added' ? 'new group' : 'changed' }}</span>
        @endif
        <span class="adm-pill is-neutral"><span><span class="js-gop-count">{{ $count }}</span> propert<span class="js-gop-plural">{{ $count === 1 ? 'y' : 'ies' }}</span></span></span>
        <span class="adm-ver adm-small adm-muted" title="Group version">v{{ $gop->versionNumber }}.{{ $gop->revisionNumber }}</span>
    </summary>

    <div class="pe-gop-body">
        <div class="pe-toolbar">
            <button type="button" class="adm-btn is-sm js-toggle-picker">+ Add properties</button>
            <button type="button" class="adm-btn is-sm is-ghost js-toggle-gop-details">Group details</button>
            <button type="button" class="adm-btn is-sm is-danger js-remove-gop" style="margin-left:auto;">Remove group</button>
        </div>

        {{-- Group attributes + relations (rendered on first open) --}}
        <div class="pe-panel js-gop-details" hidden>
            <template class="js-lazy">
                <div class="js-gop-attrs">
                    @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => (array) $gop, 'prefix' => 'gop' . $gop->Id, 'idAttr' => 'gop' . $gop->Id])
                </div>
                <div class="mt-2 flex items-center gap-3">
                    <button type="button" class="adm-btn is-sm js-save-gop">Save group details</button>
                    <span class="js-gop-status adm-small"></span>
                </div>
                <x-relation-editor entity-type="gop" :guid="$gop->GUID" title="Group relationships" />
            </template>
        </div>

        {{-- Property picker (rendered on first open) --}}
        <div class="pe-panel js-picker-wrap" hidden>
            <template class="js-lazy">
                @include('admin.partials._property-picker', ['gopId' => $gop->Id, 'gopNameEn' => $gop->gopNameEn, 'gopNamePt' => $gop->gopNamePt, 'dictFields' => $dictFields, 'dictEnums' => $dictEnums])
            </template>
        </div>

        {{-- Properties --}}
        <div class="pe-props js-props">
            <div class="pe-prop-head">
                <span>Property</span><span>Data type</span><span>Unit</span><span>Definition</span><span></span>
            </div>
            <div class="pe-empty js-props-empty" @if ($count) hidden @endif>No properties yet — use <strong>+ Add properties</strong>.</div>
            @foreach ($context as $c)
                @php
                    $dictVals = isset($dictRows[$c->propertyId]) ? (array) $dictRows[$c->propertyId] : [];
                    $name = $c->dictNameEnSc ?: $c->dictNameEn;
                    $cm = $ctxMarkers[$c->Id] ?? null;
                @endphp
                <details class="pe-prop js-context" data-context-id="{{ $c->Id }}"
                         data-search="{{ mb_strtolower($name . ' ' . $c->dictNameEn . ' ' . ($c->dictNamePtSc ?: $c->dictNamePt) . ' ' . $c->dictNamePt) }}">
                    <summary class="pe-prop-row">
                        <span class="pe-prop-name">
                            <span class="pe-chev" aria-hidden="true"></span>
                            <span>
                                <strong>{{ $name ?: '?' }}</strong>
                                <span class="adm-small adm-muted pe-code">{{ $c->dictNameEn }}</span>
                                @if ($cm)<span class="adm-pill is-{{ $cm }}">{{ $cm === 'added' ? 'new here' : 'changed' }}</span>@endif
                                @if ($c->dictStatus === 'Preview')
                                    <span class="adm-pill is-draft js-shared-tag" title="This definition only exists in drafts until you publish">draft definition</span>
                                @elseif ($c->shared)
                                    <span class="adm-pill is-neutral js-shared-tag" title="Also used by other templates — editing the definition makes a draft copy for this template only">shared</span>
                                @endif
                            </span>
                        </span>
                        <span class="adm-small">{{ $c->dictDataType ?: '—' }}</span>
                        <span class="adm-small">{{ $c->dictUnits ?: '—' }}</span>
                        <span class="adm-small adm-ver">v{{ $c->dictVersion }}.{{ $c->dictRevision }}</span>
                        <span style="text-align:right;"><button type="button" class="adm-btn is-sm is-danger js-remove-context" title="Remove from this group">Remove</button></span>
                    </summary>
                    <div class="pe-prop-body js-lazy-host">
                        <template class="js-lazy">
                            <div class="pe-prop-panes">
                                <div>
                                    <h3>How this template uses it</h3>
                                    <div class="adm-small adm-muted" style="margin-bottom:6px;">Description, reference document and visual for this template only.</div>
                                    <div class="js-ctx-attrs">
                                        @include('admin.partials._attr-fields', ['fields' => $ctxFields, 'values' => (array) $c, 'prefix' => 'ctx' . $c->Id, 'idAttr' => 'ctx' . $c->Id])
                                    </div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <button type="button" class="adm-btn is-sm js-save-context">Save usage</button>
                                        <span class="js-context-status adm-small"></span>
                                    </div>
                                </div>
                                <div>
                                    <h3>Dictionary definition</h3>
                                    <div class="adm-small adm-muted" style="margin-bottom:6px;">
                                        @if ($c->shared && $c->dictStatus !== 'Preview')
                                            Shared with other templates: saving makes a draft copy for this template; the live definition is not changed.
                                        @else
                                            Saved into this draft.
                                        @endif
                                    </div>
                                    <div class="js-def-attrs">
                                        @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => $dictVals, 'prefix' => 'def' . $c->Id, 'idAttr' => 'def' . $c->Id, 'enums' => $dictEnums])
                                    </div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <button type="button" class="adm-btn is-sm js-save-def">Save definition</button>
                                        <span class="js-def-status adm-small"></span>
                                    </div>
                                    @if (!empty($dictVals['GUID']))
                                        <x-relation-editor entity-type="property" :guid="$dictVals['GUID']" title="Property relationships" />
                                        <x-property-dependency-editor :guid="$dictVals['GUID']" />
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</details>
