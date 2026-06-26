<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" id="active-edit" data-pdt-id="{{ $pdt->Id }}">

            <div class="flex flex-wrap items-center gap-2 border-b pb-3">
                <h1 class="flex-none inline">{{ $pdt->pdtNamePt }}</h1>
                <span> - <x-version-badge :version="$pdt->versionNumber" :revision="$pdt->revisionNumber" /></span>
                <x-status-badge :status="$pdt->status" />
                <span class="ml-auto flex gap-2">
                    <a href="{{ route('admin.pdt.newVersion', ['pdt' => $pdt->Id]) }}" class="btn btn-secondary">Create new version</a>
                    <a href="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}" class="btn btn-secondary">Back</a>
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                <strong>Limited edit of an Active template.</strong> Only the sanctioned non-version
                attributes are editable here (in place, no new version): a property's
                <em>description, visual representation, reference document</em>, and a dictionary
                property's <em>mapping</em>. Everything else is read-only. For structural or
                name/definition changes, use <a href="{{ route('admin.pdt.newVersion', ['pdt' => $pdt->Id]) }}" class="text-blue-700 underline">Create new version</a>.
            </p>

            {{-- PDT attributes (all read-only) --}}
            <div class="mt-6 border rounded shadow-sm">
                <div class="px-4 py-2 border-b bg-slate-50 font-semibold">Template attributes (read-only)</div>
                <div class="p-4">
                    @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => (array) $pdt, 'prefix' => 'pdt', 'idAttr' => 'pdt', 'editableOverride' => []])
                </div>
            </div>

            {{-- Relationships ARE editable on an Active PDT: GUID-lineage, so they apply
                 across versions and need no new draft/version. --}}
            <x-relation-editor entity-type="pdt" :guid="$pdt->GUID" title="Template relationships (editable on Active)" />

            @foreach ($gops as $gop)
                <div class="mt-4 border rounded shadow-sm">
                    <div class="px-4 py-2 border-b bg-slate-50 flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $gop->gopNameEn }} <span class="text-gray-400">/</span> {{ $gop->gopNamePt }}</span>
                        <span class="text-xs text-gray-500">(Id={{ $gop->Id }} · <x-version-badge :version="$gop->versionNumber" :revision="$gop->revisionNumber" />) — read-only</span>
                    </div>
                    <div class="p-4">
                        <details>
                            <summary class="cursor-pointer text-xs font-semibold text-gray-700">Group attributes (read-only)</summary>
                            <div class="mt-2">
                                @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => (array) $gop, 'prefix' => 'gop' . $gop->Id, 'idAttr' => 'gop' . $gop->Id, 'editableOverride' => []])
                            </div>
                        </details>

                        <x-relation-editor entity-type="gop" :guid="$gop->GUID" title="Group relationships (editable on Active)" />

                        <div class="mt-3 font-semibold text-sm">Properties</div>
                        @foreach ($contextByGop->get($gop->Id, collect()) as $c)
                            @php $dictVals = isset($dictRows[$c->propertyId]) ? (array) $dictRows[$c->propertyId] : []; @endphp
                            <div class="js-context border rounded p-3 mt-2 bg-gray-50" data-context-id="{{ $c->Id }}">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="font-semibold">{{ $c->dictNameEn }} <span class="text-gray-400">/</span> {{ $c->dictNamePt }}</span>
                                    <span class="text-xs text-gray-500">dict Id={{ $c->propertyId }} · v{{ $c->dictVersion }}.{{ $c->dictRevision }}</span>
                                </div>

                                {{-- Editable context fields --}}
                                <div class="js-ctx-attrs">
                                    @include('admin.partials._attr-fields', ['fields' => $ctxFields, 'values' => (array) $c, 'prefix' => 'ctx' . $c->Id, 'idAttr' => 'ctx' . $c->Id, 'editableOverride' => $ctxEditable])
                                </div>
                                <div class="mt-1 flex items-center gap-3">
                                    <button type="button" class="btn btn-secondary js-save-ctx">Save description / reference</button>
                                    <span class="js-ctx-status text-sm"></span>
                                </div>

                                {{-- Dictionary: only the mapping attribute editable --}}
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">Dictionary attributes (read-only except mapping)</summary>
                                    <div class="js-def-attrs mt-2">
                                        @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => $dictVals, 'prefix' => 'def' . $c->Id, 'idAttr' => 'def' . $c->Id, 'editableOverride' => $dictEditable])
                                    </div>
                                    <div class="mt-1 flex items-center gap-3">
                                        <button type="button" class="btn btn-secondary js-save-def">Save mapping</button>
                                        <span class="js-def-status text-sm"></span>
                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('active-edit');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const u = {
                ctx: "{{ route('admin.pdt.active.context', ['pdt' => $pdt->Id]) }}",
                def: "{{ route('admin.pdt.active.mapping', ['pdt' => $pdt->Id]) }}",
            };
            const post = (url, payload) => fetch(url, { method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload || {}) }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            function setStatus(el, msg, good) {
                el.textContent = msg;
                el.className = el.className.replace(/\btext-(red|green)-700\b/g, '').trim() + (msg ? (good ? ' text-green-700' : ' text-red-700') : '');
            }
            function collect(scope) {
                const out = {};
                scope.querySelectorAll('.js-attr').forEach(el => { if (!el.disabled) out[el.dataset.field] = el.value; });
                return out;
            }
            // Confirm-summary before mutating live Active data.
            function confirmSave(label, attrs) {
                const lines = Object.entries(attrs).map(([k, v]) => '  • ' + k + ' = ' + (v === '' ? '(blank)' : v));
                return confirm('Save these changes to this Active ' + label + '?\n\n' + lines.join('\n'));
            }

            root.querySelectorAll('.js-context').forEach(block => {
                const contextId = parseInt(block.dataset.contextId, 10);

                block.querySelector('.js-save-ctx').addEventListener('click', function () {
                    const st = block.querySelector('.js-ctx-status');
                    const attrs = collect(block.querySelector('.js-ctx-attrs'));
                    if (!Object.keys(attrs).length) { setStatus(st, 'Nothing editable.', false); return; }
                    if (!confirmSave('property (context)', attrs)) return;
                    setStatus(st, 'Saving…', true);
                    post(u.ctx, { contextId, attrs }).then(({ ok, body }) =>
                        setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                });

                const defBtn = block.querySelector('.js-save-def');
                if (defBtn) defBtn.addEventListener('click', function () {
                    const st = block.querySelector('.js-def-status');
                    const attrs = collect(block.querySelector('.js-def-attrs'));
                    if (!Object.keys(attrs).length) { setStatus(st, 'Nothing editable.', false); return; }
                    if (!confirmSave('dictionary mapping', attrs)) return;
                    setStatus(st, 'Saving…', true);
                    post(u.def, { contextId, attrs }).then(({ ok, body }) =>
                        setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                });
            });
        })();
    </script>

    @include('admin.partials._refdoc-modal')
</x-app-layout>
