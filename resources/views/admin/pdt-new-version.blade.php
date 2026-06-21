<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" id="ver-editor"
             data-pdt-id="{{ $pdt->Id }}">

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
                changes, preview the exact set of rows that will be created/deprecated, then commit
                once to create a single new version. A name change or adding/removing a GOP bumps the
                <strong>version</strong>; smaller edits bump the <strong>revision</strong>. The current
                version is kept as a read-only InActive snapshot.
            </p>

            {{-- PDT attributes --}}
            <div class="mt-6 border rounded shadow-sm">
                <div class="px-4 py-2 border-b bg-slate-50 font-semibold">Template attributes</div>
                <div class="p-4 js-pdt-attrs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @php
                            $pf = ['pdtNameEn' => 'Name (EN)', 'pdtNamePt' => 'Name (PT)', 'category' => 'Category',
                                   'referenceDocumentGUID' => 'Reference document GUID', 'constructionObjectGUID' => 'Construction object GUID'];
                        @endphp
                        @foreach ($pf as $f => $label)
                            <div>
                                <label class="block text-xs font-semibold mb-1">{{ $label }}</label>
                                <input type="text" class="js-vf w-full border rounded p-2 text-sm" data-field="{{ $f }}"
                                       value="{{ $pdt->$f }}" data-original="{{ $pdt->$f }}">
                            </div>
                        @endforeach
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Description (EN)</label>
                            <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="descriptionEn" data-original="{{ $pdt->descriptionEn }}" rows="2">{{ $pdt->descriptionEn }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Description (PT)</label>
                            <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="descriptionPt" data-original="{{ $pdt->descriptionPt }}" rows="2">{{ $pdt->descriptionPt }}</textarea>
                        </div>
                    </div>
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
                        <span class="font-semibold">{{ $gop->gopNamePt ?: $gop->gopNameEn }}</span>
                        <span class="text-xs text-gray-500">(Id={{ $gop->Id }} · <x-version-badge :version="$gop->versionNumber" :revision="$gop->revisionNumber" />)</span>
                        <label class="ml-auto flex items-center gap-1 text-sm" style="color:#7f1d1d;">
                            <input type="checkbox" class="js-remove-gop"> Drop from new version
                        </label>
                    </div>
                    <div class="p-4">
                        <div class="js-gop-attrs grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1">Group name (EN)</label>
                                <input type="text" class="js-vf w-full border rounded p-2 text-sm" data-field="gopNameEn" value="{{ $gop->gopNameEn }}" data-original="{{ $gop->gopNameEn }}">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Group name (PT)</label>
                                <input type="text" class="js-vf w-full border rounded p-2 text-sm" data-field="gopNamePt" value="{{ $gop->gopNamePt }}" data-original="{{ $gop->gopNamePt }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1">Definition (EN)</label>
                                <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="definitionEn" data-original="{{ $gop->definitionEn }}" rows="2">{{ $gop->definitionEn }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1">Definition (PT)</label>
                                <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="definitionPt" data-original="{{ $gop->definitionPt }}" rows="2">{{ $gop->definitionPt }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 font-semibold text-sm">Properties ({{ $ctx->count() }})</div>
                        @foreach ($ctx as $c)
                            <div class="js-prop border rounded p-3 mt-2 bg-gray-50" data-dict-id="{{ $c->propertyId }}" data-context-id="{{ $c->Id }}">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="font-semibold">{{ $c->dictNamePt ?: $c->dictNameEn }}</span>
                                    <span class="text-xs text-gray-500">dict Id={{ $c->propertyId }} · v{{ $c->dictVersion }}.{{ $c->dictRevision }}</span>
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" class="js-correction"> correction (revision, not version)</label>
                                    <label class="ml-auto flex items-center gap-1 text-sm" style="color:#7f1d1d;">
                                        <input type="checkbox" class="js-remove-ctx" data-context-id="{{ $c->Id }}"> Remove
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs mb-1">nameEn</label>
                                        <input type="text" class="js-vf w-full border rounded p-2 text-sm" data-field="nameEn" value="{{ $c->dictNameEn }}" data-original="{{ $c->dictNameEn }}">
                                    </div>
                                    <div>
                                        <label class="block text-xs mb-1">namePt</label>
                                        <input type="text" class="js-vf w-full border rounded p-2 text-sm" data-field="namePt" value="{{ $c->dictNamePt }}" data-original="{{ $c->dictNamePt }}">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs mb-1">definitionEn</label>
                                        <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="definitionEn" data-original="{{ $c->dictDefEn }}" rows="2">{{ $c->dictDefEn }}</textarea>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs mb-1">definitionPt</label>
                                        <textarea class="js-vf w-full border rounded p-2 text-sm" data-field="definitionPt" data-original="{{ $c->dictDefPt }}" rows="2">{{ $c->dictDefPt }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Add existing property to this GOP (staged) --}}
                        <div class="mt-3 border rounded p-3 bg-white">
                            <div class="text-xs font-semibold mb-1">Add an existing dictionary property (staged)</div>
                            <div class="flex items-center gap-2">
                                <input type="text" class="js-existing-q border rounded p-2 text-sm flex-1" placeholder="Search by name…">
                                <button type="button" class="btn btn-secondary js-existing-search">Search</button>
                            </div>
                            <div class="js-existing-results text-sm mt-2"></div>
                            <div class="js-queued-adds text-sm mt-2 flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add a GOP (staged) — from scratch or cloned from an existing group --}}
            <div class="mt-4 border rounded p-4 bg-slate-50">
                <div class="font-semibold mb-2">Add a group of properties (staged)</div>

                <div class="mb-3">
                    <div class="text-xs font-semibold mb-1">From scratch</div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs mb-1">Name (EN)</label>
                            <input type="text" id="addgop-en" class="border rounded p-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Name (PT)</label>
                            <input type="text" id="addgop-pt" class="border rounded p-2 text-sm">
                        </div>
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

            const addPropQueue = {};  // gopId -> [{id,name}]
            const addGopQueue = [];   // [{gopNameEn,gopNamePt}] or [{fromGopId,name}]

            function postJSON(u, payload) {
                return fetch(u, { method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload || {}) }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            }
            function getJSON(u) { return fetch(u, { headers: { 'Accept': 'application/json' } }).then(r => r.json().then(j => ({ ok: r.ok, body: j }))); }
            function escapeHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

            // Collect only fields whose value differs from data-original, within a scope.
            function collectChanged(scope) {
                const out = {};
                if (!scope) return out;
                scope.querySelectorAll('.js-vf').forEach(el => {
                    if (el.value !== (el.dataset.original ?? '')) out[el.dataset.field] = el.value;
                });
                return out;
            }

            function buildStaged() {
                const staged = { pdt: { attributes: {} }, removeGopIds: [], addGops: [], gopEdits: [], propertyEdits: [] };
                staged.pdt.attributes = collectChanged(root.querySelector('.js-pdt-attrs'));

                root.querySelectorAll('.js-gop').forEach(card => {
                    const gopId = parseInt(card.dataset.gopId, 10);
                    if (card.querySelector('.js-remove-gop').checked) { staged.removeGopIds.push(gopId); return; }

                    const attrs = collectChanged(card.querySelector('.js-gop-attrs'));
                    const removeContextIds = [...card.querySelectorAll('.js-remove-ctx:checked')].map(c => parseInt(c.dataset.contextId, 10));
                    const adds = (addPropQueue[gopId] || []).map(a => a.id);
                    if (Object.keys(attrs).length || removeContextIds.length || adds.length) {
                        staged.gopEdits.push({ gopId, attributes: attrs, addProperties: adds, removeContextIds });
                    }

                    card.querySelectorAll('.js-prop').forEach(p => {
                        if (p.querySelector('.js-remove-ctx').checked) return; // being removed
                        const values = collectChanged(p);
                        if (Object.keys(values).length) {
                            staged.propertyEdits.push({
                                dictId: parseInt(p.dataset.dictId, 10), gopId,
                                values, correction: p.querySelector('.js-correction').checked,
                            });
                        }
                    });
                });

                staged.addGops = addGopQueue.map(g => g.fromGopId
                    ? { fromGopId: g.fromGopId, attributes: {} }
                    : { attributes: { gopNameEn: g.gopNameEn, gopNamePt: g.gopNamePt } });
                return staged;
            }

            // ---- per-GOP add-existing search + queue ----
            root.querySelectorAll('.js-gop').forEach(card => {
                const gopId = parseInt(card.dataset.gopId, 10);
                const results = card.querySelector('.js-existing-results');
                const queued = card.querySelector('.js-queued-adds');

                function renderQueued() {
                    const list = addPropQueue[gopId] || [];
                    queued.innerHTML = list.map((a, i) =>
                        '<span class="ver-chip">' + escapeHtml(a.name) + ' <button type="button" data-i="' + i + '">×</button></span>'
                    ).join('');
                    queued.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
                        addPropQueue[gopId].splice(parseInt(b.dataset.i, 10), 1); renderQueued();
                    }));
                }

                card.querySelector('.js-existing-search').addEventListener('click', function () {
                    const q = card.querySelector('.js-existing-q').value.trim();
                    results.innerHTML = 'Searching…';
                    getJSON(url.lookupProps + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                        if (!ok) { results.innerHTML = 'Search failed.'; return; }
                        const rows = body.results || [];
                        if (!rows.length) { results.innerHTML = '<span class="text-gray-600">No matches.</span>'; return; }
                        results.innerHTML = rows.map(r =>
                            '<div class="flex items-center gap-2 py-1 border-b"><span class="flex-1">'
                            + escapeHtml(r.nameEn) + ' / ' + escapeHtml(r.namePt) + ' <span class="text-gray-500 text-xs">(Id=' + r.Id + ')</span></span>'
                            + '<button type="button" class="btn btn-secondary js-queue" data-id="' + r.Id + '" data-name="' + escapeHtml(r.nameEn) + '">Queue</button></div>'
                        ).join('');
                        results.querySelectorAll('.js-queue').forEach(b => b.addEventListener('click', () => {
                            addPropQueue[gopId] = addPropQueue[gopId] || [];
                            if (!addPropQueue[gopId].some(a => a.id === parseInt(b.dataset.id, 10))) {
                                addPropQueue[gopId].push({ id: parseInt(b.dataset.id, 10), name: b.dataset.name });
                                renderQueued();
                            }
                        }));
                    });
                });
            });

            // ---- queue new GOP ----
            const queuedGops = document.getElementById('queued-gops');
            function renderQueuedGops() {
                queuedGops.innerHTML = addGopQueue.map((g, i) =>
                    '<span class="ver-chip">' + escapeHtml(g.fromGopId ? ('↳ ' + g.name) : (g.gopNamePt || g.gopNameEn)) + ' <button type="button" data-i="' + i + '">×</button></span>'
                ).join('');
                queuedGops.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
                    addGopQueue.splice(parseInt(b.dataset.i, 10), 1); renderQueuedGops();
                }));
            }
            document.getElementById('btn-queue-gop').addEventListener('click', function () {
                const en = document.getElementById('addgop-en').value.trim();
                const pt = document.getElementById('addgop-pt').value.trim();
                if (!en && !pt) return;
                addGopQueue.push({ gopNameEn: en, gopNamePt: pt });
                document.getElementById('addgop-en').value = '';
                document.getElementById('addgop-pt').value = '';
                renderQueuedGops();
            });

            // queue GOP from an existing group
            const addgopResults = document.getElementById('addgop-results');
            document.getElementById('btn-search-gop').addEventListener('click', function () {
                const q = document.getElementById('addgop-q').value.trim();
                addgopResults.innerHTML = 'Searching…';
                getJSON(url.lookupGops + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                    if (!ok) { addgopResults.innerHTML = 'Search failed.'; return; }
                    const rows = body.results || [];
                    if (!rows.length) { addgopResults.innerHTML = '<span class="text-gray-600">No matches.</span>'; return; }
                    addgopResults.innerHTML = rows.map(r =>
                        '<div class="flex items-center gap-2 py-1 border-b"><span class="flex-1">'
                        + escapeHtml(r.gopNameEn) + ' / ' + escapeHtml(r.gopNamePt) + ' <span class="text-gray-500 text-xs">(Id=' + r.Id + ')</span></span>'
                        + '<button type="button" class="btn btn-secondary js-queue-gop" data-id="' + r.Id + '" data-name="' + escapeHtml(r.gopNameEn) + '">Queue</button></div>'
                    ).join('');
                    addgopResults.querySelectorAll('.js-queue-gop').forEach(b => b.addEventListener('click', () => {
                        addGopQueue.push({ fromGopId: parseInt(b.dataset.id, 10), name: b.dataset.name });
                        renderQueuedGops();
                    }));
                });
            });

            // ---- preview + commit ----
            const modal = document.getElementById('ver-modal');
            const commitBtn = document.getElementById('btn-commit');
            let lastStaged = null;

            document.getElementById('btn-preview').addEventListener('click', function () {
                lastStaged = buildStaged();
                modal.style.display = 'flex';
                const sum = document.getElementById('ver-summary');
                commitBtn.disabled = true;
                document.getElementById('ver-status').textContent = '';
                sum.textContent = 'Planning…';
                postJSON(url.preview, { staged: lastStaged }).then(({ ok, body }) => {
                    if (!ok || !body.ok) { sum.textContent = body.error || 'Planning failed.'; return; }
                    sum.textContent = (body.summary || []).join('\n');
                    commitBtn.disabled = (body.opCount || 0) === 0;
                });
            });
            document.getElementById('btn-cancel').addEventListener('click', () => modal.style.display = 'none');

            commitBtn.addEventListener('click', function () {
                const st = document.getElementById('ver-status');
                st.textContent = 'Committing…'; st.className = 'text-sm text-green-700';
                commitBtn.disabled = true;
                postJSON(url.commit, { staged: lastStaged }).then(({ ok, body }) => {
                    if (ok && body.ok) {
                        window.location = "{{ url('pdtsdownload') }}/" + (body.newPdtId || "{{ $pdt->Id }}");
                    } else {
                        st.textContent = '✗ ' + (body.error || 'Commit failed.'); st.className = 'text-sm text-red-700';
                        commitBtn.disabled = false;
                    }
                });
            });
        })();
    </script>
</x-app-layout>
