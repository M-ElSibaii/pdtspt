<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" id="ver-editor" data-pdt-id="{{ $pdt->Id }}">

            {{-- Header --}}
            <div class="flex flex-wrap items-center gap-2 border-b pb-3">
                <h1 class="flex-none inline">{{ $pdt->pdtNamePt }}</h1>
                <span> - <x-version-badge :version="$pdt->versionNumber" :revision="$pdt->revisionNumber" /></span>
                <x-status-badge :status="$pdt->status" />
                <span class="ml-auto flex gap-2">
                    <button type="button" class="btn btn-secondary" id="btn-preview">Preview new version…</button>
                    <a href="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}" class="btn btn-secondary">Cancel</a>
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                Staged editor — <strong>nothing is written</strong> until you commit. Make all your
                changes, preview the exact rows to be created/deprecated, then commit once. Every level
                shows its full attribute set (rest behind “All attributes”). A name change or adding/
                removing a GOP bumps the <strong>version</strong>; smaller edits bump the
                <strong>revision</strong>. The current version is kept as a read-only InActive snapshot.
            </p>

            {{-- PDT attributes (full) --}}
            <div class="mt-6 border rounded shadow-sm">
                <div class="px-4 py-2 border-b bg-slate-50 font-semibold">Template attributes</div>
                <div class="p-4 js-pdt-attrs">
                    @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => (array) $pdt, 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                </div>
            </div>

            {{-- GOPs --}}
            <div class="mt-6 flex items-center gap-3">
                <h2 class="text-lg font-semibold">Groups of properties</h2>
                <span class="text-sm text-gray-600">({{ $gops->count() }})</span>
            </div>

            @foreach ($gops as $gop)
                @php $ctx = $contextByGop->get($gop->Id, collect()); @endphp
                <div class="js-gop mt-4 border rounded shadow-sm" data-gop-id="{{ $gop->Id }}">
                    <div class="px-4 py-2 border-b bg-slate-50 flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $gop->gopNameEn }} <span class="text-gray-400">/</span> {{ $gop->gopNamePt }}</span>
                        <span class="text-xs text-gray-500">(Id={{ $gop->Id }} · <x-version-badge :version="$gop->versionNumber" :revision="$gop->revisionNumber" />)</span>
                        <label class="ml-auto flex items-center gap-1 text-sm" style="color:#7f1d1d;">
                            <input type="checkbox" class="js-remove-gop"> Drop from new version
                        </label>
                    </div>
                    <div class="p-4">
                        {{-- Full GOP attributes (item 4) --}}
                        <div class="js-gop-attrs">
                            @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => (array) $gop, 'prefix' => 'gop' . $gop->Id, 'idAttr' => 'gop' . $gop->Id])
                        </div>

                        <div class="mt-4 font-semibold text-sm">Properties ({{ $ctx->count() }})</div>
                        @foreach ($ctx as $c)
                            @php $dictVals = isset($dictRows[$c->propertyId]) ? (array) $dictRows[$c->propertyId] : []; @endphp
                            <div class="js-prop border rounded p-3 mt-2 bg-gray-50" data-dict-id="{{ $c->propertyId }}" data-context-id="{{ $c->Id }}">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="font-semibold">{{ $c->dictNameEnSc ?: $c->dictNameEn }} <span class="text-gray-400">/</span> {{ $c->dictNamePtSc ?: $c->dictNamePt }}</span>
                                    <span class="text-xs text-gray-500">code: {{ $c->dictNameEn }} · dict Id={{ $c->propertyId }} · v{{ $c->dictVersion }}.{{ $c->dictRevision }}</span>
                                    <label class="ml-auto flex items-center gap-1 text-sm" style="color:#7f1d1d;">
                                        <input type="checkbox" class="js-remove-ctx" data-context-id="{{ $c->Id }}"> Remove
                                    </label>
                                </div>

                                {{-- CONTEXT: this template's usage (properties table) — can differ from the dictionary --}}
                                <details class="border rounded bg-white" open>
                                    <summary class="cursor-pointer text-xs font-semibold p-2">This template's usage <span class="text-gray-400">(context · properties table — description / visual representation / reference document, may differ from the dictionary)</span></summary>
                                    <div class="js-ctx-attrs p-2">
                                        @include('admin.partials._attr-fields', ['fields' => $ctxFields, 'values' => (array) $c, 'prefix' => 'ctx' . $c->Id, 'idAttr' => 'ctx' . $c->Id])
                                    </div>
                                </details>

                                {{-- DICTIONARY: the shared definition (propertiesdatadictionaries) --}}
                                <label class="flex items-start gap-2 text-xs mt-2 mb-1 bg-white border rounded p-2">
                                    <input type="checkbox" class="js-correction mt-0.5" checked>
                                    <span><strong>Wording correction</strong> (revision) — fixing wording/attributes; the
                                    <em>name/code stays locked</em>. <strong>Uncheck</strong> to change the name or meaning
                                    (a real rename/redefinition → new <em>version</em>).</span>
                                </label>
                                <details class="border rounded bg-white">
                                    <summary class="cursor-pointer text-xs font-semibold p-2">Dictionary definition <span class="text-gray-400">(propertiesdatadictionaries — shared; Code = PascalCase no accents, Name = sentence case)</span></summary>
                                    <div class="js-def-attrs p-2">
                                        @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => $dictVals, 'prefix' => 'def' . $c->Id, 'idAttr' => 'def' . $c->Id, 'enums' => $dictEnums])
                                    </div>
                                </details>
                            </div>
                        @endforeach

                        {{-- Add EXISTING property (staged) --}}
                        <div class="mt-3 border rounded p-3 bg-white">
                            <div class="text-xs font-semibold mb-1">Add an existing dictionary property (staged)</div>
                            <div class="flex items-center gap-2">
                                <input type="text" class="js-existing-q border rounded p-2 text-sm flex-1" placeholder="Search by name…">
                                <button type="button" class="btn btn-secondary js-existing-search">Search</button>
                            </div>
                            <div class="js-existing-results text-sm mt-2"></div>
                            <div class="js-queued-adds text-sm mt-2 flex flex-wrap gap-2"></div>
                        </div>

                        {{-- Create a NEW property from scratch (staged) — item 1 --}}
                        <details class="mt-2 border rounded p-3 bg-white">
                            <summary class="cursor-pointer text-xs font-semibold">Create a NEW dictionary property (full attributes, fresh GUID)</summary>
                            <div class="js-newprop mt-2">
                                @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => [], 'prefix' => 'newp' . $gop->Id, 'idAttr' => 'newp' . $gop->Id, 'enums' => $dictEnums])
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <button type="button" class="btn btn-secondary js-queue-newprop">Queue new property</button>
                                <span class="js-newprop-status text-sm"></span>
                            </div>
                            <div class="js-queued-newprops text-sm mt-2 flex flex-wrap gap-2"></div>
                        </details>
                    </div>
                </div>
            @endforeach

            {{-- Add a GOP (staged): from scratch or cloned from existing --}}
            <div class="mt-4 border rounded p-4 bg-slate-50">
                <div class="font-semibold mb-2">Add a group of properties (staged)</div>
                <div class="mb-3">
                    <div class="text-xs font-semibold mb-1">From scratch</div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div><label class="block text-xs mb-1">Name (EN)</label><input type="text" id="addgop-en" class="border rounded p-2 text-sm"></div>
                        <div><label class="block text-xs mb-1">Name (PT)</label><input type="text" id="addgop-pt" class="border rounded p-2 text-sm"></div>
                        <button type="button" class="btn btn-secondary" id="btn-queue-gop">Queue group</button>
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold mb-1">From an existing group (clones its attributes, fresh GUID)</div>
                    <div class="flex items-center gap-2">
                        <input type="text" id="addgop-q" class="border rounded p-2 text-sm flex-1" placeholder="Search existing groups by name…">
                        <button type="button" class="btn btn-secondary" id="btn-search-gop">Search</button>
                    </div>
                    <div id="addgop-results" class="text-sm mt-2"></div>
                </div>
                <div id="queued-gops" class="text-sm mt-3 flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    {{-- Preview / commit modal --}}
    <div id="ver-modal" class="ver-modal" style="display:none;">
        <div class="ver-modal-box">
            <h3 class="font-semibold text-lg mb-2">Preview new version</h3>
            <div id="ver-summary" class="text-sm whitespace-pre-wrap font-mono bg-gray-50 border rounded p-3 max-h-96 overflow-auto">Loading…</div>
            <div class="mt-4 flex items-center gap-3">
                <button type="button" class="btn btn-secondary" id="btn-commit" disabled>Commit new version</button>
                <button type="button" class="btn btn-secondary" id="btn-cancel">Cancel</button>
                <span id="ver-status" class="text-sm"></span>
            </div>
        </div>
    </div>

    <style>
        .ver-modal { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 50; }
        .ver-modal-box { background: #fff; border-radius: 8px; padding: 20px; width: min(760px, 92vw); max-height: 88vh; overflow:auto; box-shadow: 0 10px 40px rgba(0,0,0,.25); }
        .ver-chip { background:#e0e7ff; color:#3730a3; border-radius:6px; padding:2px 8px; font-size:12px; display:inline-flex; align-items:center; gap:6px; }
        .ver-chip button { font-weight:bold; }
    </style>

    <script>
        (function () {
            const root = document.getElementById('ver-editor');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = {
                preview: "{{ route('admin.pdt.newVersion.preview', ['pdt' => $pdt->Id]) }}",
                commit: "{{ route('admin.pdt.newVersion.commit', ['pdt' => $pdt->Id]) }}",
                lookupProps: "{{ route('admin.lookup.properties') }}",
                lookupGops: "{{ route('admin.lookup.gops') }}",
            };

            const addPropQueue = {};   // gopId -> [{id,name}]
            const newPropQueue = {};   // gopId -> [{values}]
            const addGopQueue = [];

            const postJSON = (u, p) => fetch(u, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(p || {}) }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            const getJSON = (u) => fetch(u, { headers: { 'Accept': 'application/json' } }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

            // changed (vs data-original), skipping disabled (e.g. name locked in correction mode)
            function collectChanged(scope) {
                const out = {};
                if (!scope) return out;
                scope.querySelectorAll('.js-attr').forEach(el => {
                    if (!el.disabled && el.value !== (el.dataset.original ?? '')) out[el.dataset.field] = el.value;
                });
                return out;
            }
            // all non-empty values (for a brand-new property)
            function collectAll(scope) {
                const out = {};
                scope.querySelectorAll('.js-attr').forEach(el => { if (!el.disabled && (el.value || '').trim() !== '') out[el.dataset.field] = el.value; });
                return out;
            }
            function missingMandatory(scope) {
                const miss = [];
                scope.querySelectorAll('.js-mandatory').forEach(el => { if (!el.disabled && (el.value || '').trim() === '') miss.push(el.dataset.field); });
                return miss;
            }
            function setMsg(el, m, good) { if (el) { el.textContent = m; el.className = (el.className.replace(/\btext-(red|green)-700\b/g, '').trim()) + (m ? (good ? ' text-green-700' : ' text-red-700') : ''); } }

            function buildStaged() {
                const staged = { pdt: { attributes: {} }, removeGopIds: [], addGops: [], gopEdits: [], propertyEdits: [], contextEdits: [] };
                staged.pdt.attributes = collectChanged(root.querySelector('.js-pdt-attrs'));

                root.querySelectorAll('.js-gop').forEach(card => {
                    const gopId = parseInt(card.dataset.gopId, 10);
                    if (card.querySelector('.js-remove-gop').checked) { staged.removeGopIds.push(gopId); return; }

                    const attrs = collectChanged(card.querySelector('.js-gop-attrs'));
                    const removeContextIds = [...card.querySelectorAll('.js-remove-ctx:checked')].map(c => parseInt(c.dataset.contextId, 10));
                    const existingAdds = (addPropQueue[gopId] || []).map(a => a.id);
                    const newAdds = (newPropQueue[gopId] || []).map(v => ({ newProperty: v }));
                    const adds = existingAdds.concat(newAdds);
                    if (Object.keys(attrs).length || removeContextIds.length || adds.length) {
                        staged.gopEdits.push({ gopId, attributes: attrs, addProperties: adds, removeContextIds });
                    }

                    card.querySelectorAll('.js-prop').forEach(p => {
                        if (p.querySelector('.js-remove-ctx').checked) return;
                        // Dictionary definition edits (shared) -> revision/version per correction.
                        const values = collectChanged(p.querySelector('.js-def-attrs'));
                        if (Object.keys(values).length) {
                            staged.propertyEdits.push({
                                dictId: parseInt(p.dataset.dictId, 10), gopId,
                                values, correction: p.querySelector('.js-correction').checked,
                            });
                        }
                        // Context (this template's usage) edits — distinct from the dictionary.
                        const ctxVals = collectChanged(p.querySelector('.js-ctx-attrs'));
                        if (Object.keys(ctxVals).length) {
                            staged.contextEdits.push({ contextId: parseInt(p.dataset.contextId, 10), attributes: ctxVals });
                        }
                    });
                });

                staged.addGops = addGopQueue.map(g => g.fromGopId ? { fromGopId: g.fromGopId, attributes: {} } : { attributes: { gopNameEn: g.gopNameEn, gopNamePt: g.gopNamePt } });
                return staged;
            }

            // ---- per-GOP wiring ----
            root.querySelectorAll('.js-gop').forEach(card => {
                const gopId = parseInt(card.dataset.gopId, 10);

                // correction toggle locks/unlocks the property name fields (item 5)
                card.querySelectorAll('.js-prop').forEach(p => {
                    const cb = p.querySelector('.js-correction');
                    const defScope = p.querySelector('.js-def-attrs');
                    const nameEls = [...defScope.querySelectorAll('.js-attr')].filter(el => el.dataset.field === 'nameEn' || el.dataset.field === 'namePt');
                    const applyLock = () => nameEls.forEach(el => { el.disabled = cb.checked; el.classList.toggle('bg-gray-100', cb.checked); });
                    cb.addEventListener('change', applyLock);
                    applyLock(); // default checked => name locked
                });

                // add-existing search + queue (+ clear on empty — item 6)
                const results = card.querySelector('.js-existing-results');
                const queued = card.querySelector('.js-queued-adds');
                const qInput = card.querySelector('.js-existing-q');
                const renderQueued = () => {
                    queued.innerHTML = (addPropQueue[gopId] || []).map((a, i) => '<span class="ver-chip">' + esc(a.name) + ' <button type="button" data-i="' + i + '">×</button></span>').join('');
                    queued.querySelectorAll('button').forEach(b => b.addEventListener('click', () => { addPropQueue[gopId].splice(+b.dataset.i, 1); renderQueued(); }));
                };
                qInput.addEventListener('input', () => { if (qInput.value.trim() === '') results.innerHTML = ''; });
                card.querySelector('.js-existing-search').addEventListener('click', () => {
                    const q = qInput.value.trim();
                    if (q === '') { results.innerHTML = ''; return; }
                    results.innerHTML = 'Searching…';
                    getJSON(url.lookupProps + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                        if (!ok) { results.innerHTML = 'Search failed.'; return; }
                        const rows = body.results || [];
                        results.innerHTML = rows.length ? rows.map(r =>
                            '<div class="flex items-start gap-2 py-1 border-b"><span class="flex-1">' + esc(r.nameEn) + ' / ' + esc(r.namePt)
                            + ' <span class="text-gray-500 text-xs">(Id=' + r.Id + ')</span><br><span class="text-gray-600 text-xs">' + esc((r.definitionEn || '').slice(0, 140)) + '</span></span>'
                            + '<button type="button" class="btn btn-secondary js-queue" data-id="' + r.Id + '" data-name="' + esc(r.nameEn) + '">Queue</button></div>').join('')
                            : '<span class="text-gray-600">No matches — use “Create a NEW dictionary property”.</span>';
                        results.querySelectorAll('.js-queue').forEach(b => b.addEventListener('click', () => {
                            addPropQueue[gopId] = addPropQueue[gopId] || [];
                            if (!addPropQueue[gopId].some(a => a.id === +b.dataset.id)) { addPropQueue[gopId].push({ id: +b.dataset.id, name: b.dataset.name }); renderQueued(); }
                        }));
                    });
                });

                // create-new property queue (item 1)
                const np = card.querySelector('.js-newprop');
                const npChips = card.querySelector('.js-queued-newprops');
                const renderNew = () => {
                    npChips.innerHTML = (newPropQueue[gopId] || []).map((v, i) => '<span class="ver-chip">＋' + esc(v.nameEn || v.namePt || 'new') + ' <button type="button" data-i="' + i + '">×</button></span>').join('');
                    npChips.querySelectorAll('button').forEach(b => b.addEventListener('click', () => { newPropQueue[gopId].splice(+b.dataset.i, 1); renderNew(); }));
                };
                card.querySelector('.js-queue-newprop').addEventListener('click', () => {
                    const st = card.querySelector('.js-newprop-status');
                    const miss = missingMandatory(np);
                    if (miss.length) { np.querySelectorAll('details').forEach(d => { if (d.querySelector('.js-mandatory')) d.open = true; }); setMsg(st, 'Missing: ' + miss.join(', '), false); return; }
                    newPropQueue[gopId] = newPropQueue[gopId] || [];
                    newPropQueue[gopId].push(collectAll(np));
                    np.querySelectorAll('.js-attr').forEach(el => { if (!el.disabled) el.value = ''; });
                    setMsg(st, '✓ queued', true); renderNew();
                });
            });

            // ---- add GOP ----
            const queuedGops = document.getElementById('queued-gops');
            const renderQueuedGops = () => {
                queuedGops.innerHTML = addGopQueue.map((g, i) => '<span class="ver-chip">' + esc(g.fromGopId ? ('↳ ' + g.name) : (g.gopNamePt || g.gopNameEn)) + ' <button type="button" data-i="' + i + '">×</button></span>').join('');
                queuedGops.querySelectorAll('button').forEach(b => b.addEventListener('click', () => { addGopQueue.splice(+b.dataset.i, 1); renderQueuedGops(); }));
            };
            document.getElementById('btn-queue-gop').addEventListener('click', () => {
                const en = document.getElementById('addgop-en').value.trim(), pt = document.getElementById('addgop-pt').value.trim();
                if (!en && !pt) return;
                addGopQueue.push({ gopNameEn: en, gopNamePt: pt });
                document.getElementById('addgop-en').value = ''; document.getElementById('addgop-pt').value = '';
                renderQueuedGops();
            });
            const addgopResults = document.getElementById('addgop-results');
            const addgopQ = document.getElementById('addgop-q');
            addgopQ.addEventListener('input', () => { if (addgopQ.value.trim() === '') addgopResults.innerHTML = ''; });
            document.getElementById('btn-search-gop').addEventListener('click', () => {
                const q = addgopQ.value.trim();
                if (q === '') { addgopResults.innerHTML = ''; return; }
                addgopResults.innerHTML = 'Searching…';
                getJSON(url.lookupGops + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                    if (!ok) { addgopResults.innerHTML = 'Search failed.'; return; }
                    const rows = body.results || [];
                    addgopResults.innerHTML = rows.length ? rows.map(r =>
                        '<div class="flex items-center gap-2 py-1 border-b"><span class="flex-1">' + esc(r.gopNameEn) + ' / ' + esc(r.gopNamePt) + ' <span class="text-gray-500 text-xs">(Id=' + r.Id + ')</span></span>'
                        + '<button type="button" class="btn btn-secondary js-queue-gop" data-id="' + r.Id + '" data-name="' + esc(r.gopNameEn) + '">Queue</button></div>').join('') : '<span class="text-gray-600">No matches.</span>';
                    addgopResults.querySelectorAll('.js-queue-gop').forEach(b => b.addEventListener('click', () => { addGopQueue.push({ fromGopId: +b.dataset.id, name: b.dataset.name }); renderQueuedGops(); }));
                });
            });

            // ---- preview + commit ----
            const modal = document.getElementById('ver-modal');
            const commitBtn = document.getElementById('btn-commit');
            let lastStaged = null;
            document.getElementById('btn-preview').addEventListener('click', () => {
                lastStaged = buildStaged();
                modal.style.display = 'flex';
                const sum = document.getElementById('ver-summary');
                commitBtn.disabled = true; document.getElementById('ver-status').textContent = ''; sum.textContent = 'Planning…';
                postJSON(url.preview, { staged: lastStaged }).then(({ ok, body }) => {
                    if (!ok || !body.ok) { sum.textContent = body.error || 'Planning failed.'; return; }
                    sum.textContent = (body.summary || []).join('\n');
                    commitBtn.disabled = (body.opCount || 0) === 0;
                });
            });
            document.getElementById('btn-cancel').addEventListener('click', () => modal.style.display = 'none');
            commitBtn.addEventListener('click', function () {
                const st = document.getElementById('ver-status');
                setMsg(st, 'Committing…', true); commitBtn.disabled = true;
                postJSON(url.commit, { staged: lastStaged }).then(({ ok, body }) => {
                    if (ok && body.ok) window.location = "{{ url('pdtsdownload') }}/" + (body.newPdtId || "{{ $pdt->Id }}");
                    else { setMsg(st, '✗ ' + (body.error || 'Commit failed.'), false); commitBtn.disabled = false; }
                });
            });
        })();
    </script>

    @include('admin.partials._refdoc-modal')
</x-app-layout>
