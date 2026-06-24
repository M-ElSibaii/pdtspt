<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" id="preview-editor" data-pdt-id="{{ $pdt->Id }}">

            {{-- Header --}}
            <div class="flex flex-wrap items-center gap-2 border-b pb-3">
                <h1 class="flex-none inline">{{ $pdt->pdtNamePt }}</h1>
                <span> - <x-version-badge :version="$pdt->versionNumber" :revision="$pdt->revisionNumber" /></span>
                <x-status-badge :status="$pdt->status" />
                <span class="ml-auto flex gap-2">
                    <button type="button" class="btn btn-secondary" id="btn-publish">Publish draft…</button>
                    <button type="button" class="btn btn-secondary" id="btn-delete" style="color:#7f1d1d;">Delete draft…</button>
                    <a href="{{ route('admin.previews') }}" class="btn btn-secondary">All drafts</a>
                </span>
            </div>
            @if (session('success'))
                <div class="mt-3 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
            @endif
            <p class="text-sm text-gray-600 mt-2">
                Free-editing draft — changes save in place. Every level shows its full attribute set
                (name/definition visible, the rest behind “All attributes”); <strong>mandatory</strong>
                fields are marked <span class="text-red-600">*</span> and block saving until filled.
                Lineage/system fields are read-only (auto-managed). Editing a property
                <strong>shared</strong> with a live PDT forks a Preview copy automatically.
            </p>

            {{-- PDT attributes --}}
            <div class="mt-6 border rounded shadow-sm">
                <div class="px-4 py-2 border-b bg-slate-50 font-semibold">Template attributes</div>
                <div class="p-4 js-pdt-attrs">
                    @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => (array) $pdt, 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                    <div class="mt-3 flex items-center gap-3">
                        <button type="button" class="btn btn-secondary js-save-pdt">Save template attributes</button>
                        <span class="js-pdt-status text-sm"></span>
                    </div>
                </div>
            </div>

            {{-- GOP cards --}}
            <div class="mt-6 flex items-center gap-3">
                <h2 class="text-lg font-semibold">Groups of properties</h2>
                <span class="text-sm text-gray-600">({{ $gops->count() }})</span>
            </div>
            <div id="gop-list">
                @foreach ($gops as $gop)
                    @include('admin.partials._preview-gop', [
                        'gop' => $gop, 'context' => $contextByGop->get($gop->Id, collect()),
                        'dictRows' => $dictRows, 'gopFields' => $gopFields, 'ctxFields' => $ctxFields,
                        'dictFields' => $dictFields, 'dictEnums' => $dictEnums,
                    ])
                @endforeach
            </div>

            {{-- Add GOP: always a fresh empty group; optional name-dropdown prefill --}}
            <div class="mt-4 border rounded p-4 bg-slate-50" id="add-gop">
                <div class="font-semibold mb-2">Add a group of properties (new &amp; empty)</div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <label class="text-xs font-semibold">Quick-start from an existing name:</label>
                    <select id="gop-name-pick" class="border rounded p-2 text-sm"><option value="">— choose —</option></select>
                    <span class="text-xs text-gray-500">pre-fills name + definition (editable; still a fresh, independent GOP — new GUID, no properties)</span>
                </div>
                <div class="js-addgop-attrs">
                    @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => [], 'prefix' => 'addgop', 'idAttr' => 'addgop'])
                </div>
                <div class="mt-2 flex items-center gap-3">
                    <button type="button" class="btn btn-secondary" id="btn-add-gop">Create group</button>
                    <span id="add-gop-status" class="text-sm"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <div id="delete-modal" class="pe-modal" style="display:none;">
        <div class="pe-modal-box">
            <h3 class="font-semibold text-lg mb-2" style="color:#7f1d1d;">Delete this draft?</h3>
            <div id="delete-summary" class="text-sm whitespace-pre-wrap font-mono bg-gray-50 border rounded p-3 max-h-80 overflow-auto">Loading…</div>
            <div class="mt-4 flex items-center gap-3">
                <button type="button" class="btn btn-secondary" id="btn-delete-confirm" style="color:#7f1d1d;" disabled>Permanently delete</button>
                <button type="button" class="btn btn-secondary" id="btn-delete-cancel">Cancel</button>
                <span id="delete-status" class="text-sm"></span>
            </div>
        </div>
    </div>

    {{-- Publish modal --}}
    <div id="publish-modal" class="pe-modal" style="display:none;">
        <div class="pe-modal-box">
            <h3 class="font-semibold text-lg mb-2">Publish draft → Active</h3>
            <div id="publish-summary" class="text-sm mb-3"></div>
            <div id="publish-divergences"></div>
            <div class="mt-4 flex items-center gap-3">
                <button type="button" class="btn btn-secondary" id="btn-publish-confirm" disabled>Publish</button>
                <button type="button" class="btn btn-secondary" id="btn-publish-cancel">Cancel</button>
                <span id="publish-status" class="text-sm"></span>
            </div>
        </div>
    </div>

    <style>
        .pe-modal { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 50; }
        .pe-modal-box { background: #fff; border-radius: 8px; padding: 20px; width: min(720px, 92vw); max-height: 88vh; overflow:auto; box-shadow: 0 10px 40px rgba(0,0,0,.25); }
        .pe-shared-tag { font-size: 11px; padding: 1px 6px; border-radius: 6px; background:#fdf2e9; color:#c05621; }
    </style>

    <script>
        (function () {
            const root = document.getElementById('preview-editor');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const u = {
                editPdt: "{{ route('admin.previews.editPdt', ['pdt' => $pdt->Id]) }}",
                editGop: "{{ route('admin.previews.editGop', ['pdt' => $pdt->Id]) }}",
                addGop: "{{ route('admin.previews.addGop', ['pdt' => $pdt->Id]) }}",
                gopSuggest: "{{ route('admin.previews.gopSuggestions', ['pdt' => $pdt->Id]) }}",
                removeGop: "{{ route('admin.previews.removeGop', ['pdt' => $pdt->Id]) }}",
                editContext: "{{ route('admin.previews.editContext', ['pdt' => $pdt->Id]) }}",
                removeContext: "{{ route('admin.previews.removeContext', ['pdt' => $pdt->Id]) }}",
                editProperty: "{{ route('admin.previews.editProperty', ['pdt' => $pdt->Id]) }}",
                addExisting: "{{ route('admin.previews.addExisting', ['pdt' => $pdt->Id]) }}",
                addNew: "{{ route('admin.previews.addNew', ['pdt' => $pdt->Id]) }}",
                deletePlan: "{{ route('admin.previews.deletePlan', ['pdt' => $pdt->Id]) }}",
                deleteApply: "{{ route('admin.previews.deleteApply', ['pdt' => $pdt->Id]) }}",
                publishPlan: "{{ route('admin.previews.publishPlan', ['pdt' => $pdt->Id]) }}",
                publishApply: "{{ route('admin.previews.publishApply', ['pdt' => $pdt->Id]) }}",
                pickProps: "{{ route('admin.picker.properties') }}",
                pickMatch: "{{ route('admin.picker.match') }}",
                pickGap: "{{ route('admin.picker.gap') }}",
            };

            const post = (url, payload) => fetch(url, { method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload || {}) }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            const get = (url) => fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
            const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
            function setStatus(el, msg, good) {
                if (!el) return;
                el.textContent = msg;
                el.className = el.className.replace(/\btext-(red|green)-700\b/g, '').trim() + (msg ? (good ? ' text-green-700' : ' text-red-700') : '');
            }
            // Collect editable values (skip disabled/system) within a scope, by data-field.
            function collectAttrs(scope) {
                const out = {};
                scope.querySelectorAll('.js-attr').forEach(el => { if (!el.disabled) out[el.dataset.field] = el.value; });
                return out;
            }
            // Names of empty mandatory fields within a scope (for inline blocking).
            function missingMandatory(scope) {
                const miss = [];
                scope.querySelectorAll('.js-mandatory').forEach(el => {
                    if (!el.disabled && (el.value || '').trim() === '') miss.push(el.dataset.field);
                });
                return miss;
            }
            function openExpandersWithMandatory(scope) {
                scope.querySelectorAll('details').forEach(d => { if (d.querySelector('.js-mandatory')) d.open = true; });
            }
            function setField(scope, field, value) {
                const el = scope.querySelector('.js-attr[data-field="' + field + '"]');
                if (el) el.value = value == null ? '' : value;
            }

            // ---------- PDT attributes ----------
            const pdtScope = root.querySelector('.js-pdt-attrs');
            pdtScope.querySelector('.js-save-pdt').addEventListener('click', function () {
                const st = pdtScope.querySelector('.js-pdt-status');
                const miss = missingMandatory(pdtScope);
                if (miss.length) { openExpandersWithMandatory(pdtScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                setStatus(st, 'Saving…', true);
                post(u.editPdt, { attrs: collectAttrs(pdtScope) }).then(({ ok, body }) =>
                    setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
            });

            // ---------- per-GOP wiring ----------
            root.querySelectorAll('.js-gop-card').forEach(wireGop);
            function wireGop(card) {
                const gopId = parseInt(card.dataset.gopId, 10);

                const gopScope = card.querySelector('.js-gop-attrs');
                card.querySelector('.js-save-gop').addEventListener('click', function () {
                    const st = card.querySelector('.js-gop-status');
                    const miss = missingMandatory(gopScope);
                    if (miss.length) { openExpandersWithMandatory(gopScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Saving…', true);
                    post(u.editGop, { gopId, attrs: collectAttrs(gopScope) }).then(({ ok, body }) =>
                        setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                });

                card.querySelector('.js-remove-gop').addEventListener('click', function () {
                    if (!confirm('Remove this group and its property rows from the draft?')) return;
                    post(u.removeGop, { gopId }).then(({ ok, body }) => ok && body.ok ? card.remove() : alert(body.error || 'Failed'));
                });

                card.querySelectorAll('.js-context').forEach(wireContext);
                wirePicker(card.querySelector('.js-picker'));
            }

            function wireContext(block) {
                const contextId = parseInt(block.dataset.contextId, 10);
                const ctxScope = block.querySelector('.js-ctx-attrs');
                const defScope = block.querySelector('.js-def-attrs');

                block.querySelector('.js-save-context').addEventListener('click', function () {
                    const st = block.querySelector('.js-context-status');
                    const miss = missingMandatory(ctxScope);
                    if (miss.length) { setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Saving…', true);
                    post(u.editContext, { contextId, attrs: collectAttrs(ctxScope) }).then(({ ok, body }) =>
                        setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                });

                block.querySelector('.js-save-def').addEventListener('click', function () {
                    const st = block.querySelector('.js-def-status');
                    const miss = missingMandatory(defScope);
                    if (miss.length) { setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Saving…', true);
                    post(u.editProperty, { contextId, values: collectAttrs(defScope) }).then(({ ok, body }) => {
                        if (ok && body.ok) {
                            setStatus(st, body.forked ? '✓ Saved — forked (shared definition untouched)' : '✓ Saved', true);
                            const tag = block.querySelector('.js-shared-tag');
                            if (body.forked && tag) tag.textContent = 'forked for this draft';
                        } else setStatus(st, '✗ ' + (body.error || 'Failed'), false);
                    });
                });

                block.querySelector('.js-remove-context').addEventListener('click', function () {
                    if (!confirm('Remove this property from the group?')) return;
                    post(u.removeContext, { contextId }).then(({ ok, body }) => ok && body.ok ? block.remove() : alert(body.error || 'Failed'));
                });
            }

            // ---------- property picker ----------
            function wirePicker(p) {
                if (!p) return;
                const gopId = parseInt(p.dataset.gopId, 10);
                const results = p.querySelector('.js-pick-results');
                const addBtn = p.querySelector('.js-pick-add');
                const counts = p.querySelector('.js-pick-counts');
                const gapBtn = p.querySelector('.js-pick-gap');
                const selected = new Map(); // dictId -> name
                let lastGap = [];

                function refreshAddBtn() { addBtn.disabled = selected.size === 0; addBtn.textContent = 'Add selected (' + selected.size + ')'; }
                function renderRow(r, checked) {
                    return '<label class="flex items-start gap-2 py-1 border-b">'
                        + '<input type="checkbox" class="js-pick-cb mt-1" data-id="' + r.Id + '" data-name="' + esc(r.nameEn) + '" ' + (checked ? 'checked' : '') + '>'
                        + '<span><span class="font-semibold">' + esc(r.nameEn) + '</span> / ' + esc(r.namePt)
                        + ' <span class="text-gray-500 text-xs">(v' + r.versionNumber + '.' + r.revisionNumber + ')</span>'
                        + '<br><span class="text-gray-600 text-xs">' + esc((r.definitionEn || '').slice(0, 160)) + '</span></span></label>';
                }
                function bindRows() {
                    results.querySelectorAll('.js-pick-cb').forEach(cb => cb.addEventListener('change', function () {
                        const id = parseInt(cb.dataset.id, 10);
                        if (cb.checked) selected.set(id, cb.dataset.name); else selected.delete(id);
                        refreshAddBtn();
                    }));
                }

                // Clear stale results when the search box is emptied (item 6).
                p.querySelector('.js-pick-q').addEventListener('input', function () {
                    if (this.value.trim() === '') results.innerHTML = '';
                });

                p.querySelector('.js-pick-search').addEventListener('click', function () {
                    const q = p.querySelector('.js-pick-q').value.trim();
                    if (q === '') { results.innerHTML = ''; return; }
                    results.innerHTML = 'Searching…';
                    get(u.pickProps + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                        if (!ok) { results.innerHTML = 'Search failed.'; return; }
                        const rows = body.results || [];
                        results.innerHTML = rows.length ? rows.map(r => renderRow(r, selected.has(r.Id))).join('') : '<span class="text-gray-600">No matches.</span>';
                        bindRows();
                    });
                });

                p.querySelector('.js-pick-upload').addEventListener('click', function () {
                    const file = p.querySelector('.js-pick-file').files[0];
                    if (!file) { setStatus(counts, 'Choose a file first.', false); return; }
                    const fd = new FormData();
                    fd.append('excelFile', file);
                    fd.append('groupName', p.dataset.gopName || ''); // match only this group's sheet
                    counts.textContent = 'Matching…';
                    fetch(u.pickMatch, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd })
                        .then(r => r.json()).then(body => {
                            if (!body.ok) { setStatus(counts, body.error || 'Match failed.', false); return; }
                            if (!body.sheetMatched) {
                                setStatus(counts, 'No sheet named “' + (p.dataset.gopName || '') + '” in the file. Sheets: ' + (body.sheetNames || []).join(', '), false);
                                return;
                            }
                            (body.matchedIds || []).forEach((id, i) => selected.set(id, (body.matchedNames || [])[i] || ('#' + id)));
                            lastGap = body.unmatched || [];
                            counts.textContent = 'Matched/selected ' + body.matchedCount + ' · not found ' + body.unmatchedCount;
                            counts.className = 'js-pick-counts text-gray-700';
                            gapBtn.style.display = lastGap.length ? '' : 'none';
                            // On-screen "not found" list (item 2), alongside the downloadable gap file.
                            const nf = p.querySelector('.js-pick-notfound');
                            const nfList = p.querySelector('.js-pick-notfound-list');
                            if (lastGap.length) {
                                nfList.innerHTML = lastGap.map(n => '<li>' + escapeHtml(n) + '</li>').join('');
                                nf.style.display = '';
                            } else {
                                nf.style.display = 'none';
                            }
                            // Show the matched as a selected list so the admin sees what got picked.
                            results.innerHTML = (body.matchedNames || []).map((n, i) =>
                                renderRow({ Id: body.matchedIds[i], nameEn: n, namePt: '', versionNumber: '', revisionNumber: '', definitionEn: '' }, true)).join('')
                                || '<span class="text-gray-600">No existing matches — use the gap list to create them.</span>';
                            bindRows();
                            refreshAddBtn();
                        }).catch(e => setStatus(counts, 'Error: ' + e, false));
                });

                gapBtn.addEventListener('click', function () {
                    // POST the gap names to download the .xlsx (hidden form submit carries CSRF).
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = u.pickGap; f.style.display = 'none';
                    const t = document.createElement('input'); t.name = '_token'; t.value = csrf; f.appendChild(t);
                    lastGap.forEach(n => { const i = document.createElement('input'); i.name = 'names[]'; i.value = n; f.appendChild(i); });
                    document.body.appendChild(f); f.submit(); f.remove();
                });

                addBtn.addEventListener('click', function () {
                    const st = p.querySelector('.js-pick-status');
                    const ids = [...selected.keys()];
                    if (!ids.length) return;
                    setStatus(st, 'Adding ' + ids.length + '…', true);
                    addBtn.disabled = true;
                    // Add sequentially to keep ordering and surface the first error.
                    (async () => {
                        for (const id of ids) {
                            const { ok, body } = await post(u.addExisting, { gopId, dictId: id });
                            if (!ok || !body.ok) { setStatus(st, '✗ ' + (body.error || 'Failed'), false); addBtn.disabled = false; return; }
                        }
                        location.reload();
                    })();
                });

                // create-new (full attrs)
                const np = p.querySelector('.js-newprop');
                p.querySelector('.js-newprop-add').addEventListener('click', function () {
                    const st = p.querySelector('.js-newprop-status');
                    const miss = missingMandatory(np);
                    if (miss.length) { openExpandersWithMandatory(np); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Creating…', true);
                    post(u.addNew, { gopId, values: collectAttrs(np) }).then(({ ok, body }) =>
                        (ok && body.ok) ? location.reload() : setStatus(st, '✗ ' + (body.error || 'Failed'), false));
                });
            }

            // ---------- add GOP (two paths) ----------
            const addgopScope = root.querySelector('.js-addgop-attrs');
            let suggestions = [];
            get(u.gopSuggest).then(({ ok, body }) => {
                if (!ok || !body.ok) return;
                suggestions = body.results || [];
                const sel = document.getElementById('gop-name-pick');
                suggestions.forEach((s, i) => {
                    const o = document.createElement('option');
                    o.value = String(i);
                    o.textContent = (s.gopNameEn || s.gopNamePt || '(unnamed)');
                    sel.appendChild(o);
                });
            });
            document.getElementById('gop-name-pick').addEventListener('change', function () {
                if (this.value === '') return;
                const s = suggestions[parseInt(this.value, 10)];
                if (!s) return;
                setField(addgopScope, 'gopNameEn', s.gopNameEn);
                setField(addgopScope, 'gopNamePt', s.gopNamePt);
                setField(addgopScope, 'definitionEn', s.definitionEn);
                setField(addgopScope, 'definitionPt', s.definitionPt);
            });
            document.getElementById('btn-add-gop').addEventListener('click', function () {
                const st = document.getElementById('add-gop-status');
                const miss = missingMandatory(addgopScope);
                if (miss.length) { openExpandersWithMandatory(addgopScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                setStatus(st, 'Creating…', true);
                post(u.addGop, { attrs: collectAttrs(addgopScope) }).then(({ ok, body }) =>
                    (ok && body.ok) ? location.reload() : setStatus(st, '✗ ' + (body.error || 'Failed'), false));
            });

            // ---------- delete ----------
            const delModal = document.getElementById('delete-modal');
            document.getElementById('btn-delete').addEventListener('click', function () {
                delModal.style.display = 'flex';
                const sum = document.getElementById('delete-summary');
                const cb = document.getElementById('btn-delete-confirm');
                cb.disabled = true; sum.textContent = 'Loading…';
                get(u.deletePlan).then(({ ok, body }) => {
                    if (!ok || !body.ok) { sum.textContent = body.error || 'Failed.'; return; }
                    sum.textContent = (body.summary || []).join('\n'); cb.disabled = false;
                });
            });
            document.getElementById('btn-delete-cancel').addEventListener('click', () => delModal.style.display = 'none');
            document.getElementById('btn-delete-confirm').addEventListener('click', function () {
                const st = document.getElementById('delete-status');
                setStatus(st, 'Deleting…', true); this.disabled = true;
                post(u.deleteApply, {}).then(({ ok, body }) => ok && body.ok
                    ? window.location = "{{ route('admin.previews') }}"
                    : (setStatus(st, '✗ ' + (body.error || 'Failed'), false), this.disabled = false));
            });

            // ---------- publish ----------
            const pubModal = document.getElementById('publish-modal');
            document.getElementById('btn-publish').addEventListener('click', function () {
                pubModal.style.display = 'flex';
                const sum = document.getElementById('publish-summary');
                const divs = document.getElementById('publish-divergences');
                const cb = document.getElementById('btn-publish-confirm');
                cb.disabled = true; sum.textContent = 'Loading…'; divs.innerHTML = '';
                get(u.publishPlan).then(({ ok, body }) => {
                    if (!ok || !body.ok) { sum.textContent = body.error || 'Failed.'; return; }
                    sum.innerHTML = '<div class="whitespace-pre-wrap font-mono bg-gray-50 border rounded p-3">' + esc((body.summary || []).join('\n')) + '</div>';
                    const ds = body.divergences || [];
                    divs.innerHTML = ds.length
                        ? '<p class="text-sm font-semibold mt-3 mb-1">Decide per diverged property:</p>' + ds.map(d =>
                            '<div class="border rounded p-3 mb-2 text-sm" data-context-id="' + d.contextId + '">'
                            + '<div class="font-semibold">' + esc(d.nameEn) + '</div>'
                            + '<div class="text-gray-600">old: ' + esc(d.old.definitionEn) + '</div>'
                            + '<div class="text-gray-600">new: ' + esc(d.new.definitionEn) + '</div>'
                            + '<label class="flex items-center gap-1 mt-1"><input type="radio" name="pub_' + d.contextId + '" value="version" checked> New version (deprecate old)</label>'
                            + '<label class="flex items-center gap-1"><input type="radio" name="pub_' + d.contextId + '" value="keep"> Keep existing (discard my edit)</label></div>').join('')
                        : '<p class="text-sm text-green-700 mt-2">No divergences — straight publish.</p>';
                    cb.disabled = false;
                });
            });
            document.getElementById('btn-publish-cancel').addEventListener('click', () => pubModal.style.display = 'none');
            document.getElementById('btn-publish-confirm').addEventListener('click', function () {
                const st = document.getElementById('publish-status');
                const decisions = {};
                document.querySelectorAll('#publish-divergences [data-context-id]').forEach(b => {
                    const cid = b.dataset.contextId;
                    const sel = b.querySelector('input[name="pub_' + cid + '"]:checked');
                    decisions[cid] = sel ? sel.value : 'keep';
                });
                setStatus(st, 'Publishing…', true); this.disabled = true;
                post(u.publishApply, { decisions }).then(({ ok, body }) => ok && body.ok
                    ? window.location = "{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}"
                    : (setStatus(st, '✗ ' + (body.error || 'Failed'), false), this.disabled = false));
            });
        })();
    </script>

    @include('admin.partials._refdoc-modal')
    @include('admin.partials._units-autofill')
</x-app-layout>
