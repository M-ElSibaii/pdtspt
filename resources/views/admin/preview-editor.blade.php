<x-app-layout>
    @include('admin.partials._admin-styles')
    @php
        $isVersion = $diff['isVersionDraft'];
        $changedGops = collect($diff['gops'])->where('state', '!=', 'unchanged');
        $totalProps = $contextByGop->flatten(1)->count();
    @endphp
    <style>
        .pe-bar { position:sticky; top:0; z-index:30; background:rgba(255,255,255,.97); backdrop-filter:blur(4px); border-bottom:1px solid var(--line); }
        .pe-bar-in { max-width:1320px; margin:0 auto; padding:10px 20px; display:flex; flex-wrap:wrap; gap:8px 16px; align-items:center; }
        .pe-bar h1 { font-size:19px; }
        .pe-layout { display:grid; grid-template-columns:270px minmax(0, 1fr); gap:20px; align-items:start; }
        .pe-side { position:sticky; top:76px; max-height:calc(100vh - 96px); overflow:auto; }
        .pe-nav a { display:flex; align-items:center; gap:6px; padding:6px 10px; border-radius:6px; color:var(--ink); text-decoration:none; font-size:13px; }
        .pe-nav a:hover { background:#f1f5f9; }
        .pe-nav a .n { margin-left:auto; color:var(--muted); font-variant-numeric:tabular-nums; }
        .pe-dot { width:7px; height:7px; border-radius:50%; flex:none; background:transparent; }
        .pe-dot.is-added { background:var(--ok); } .pe-dot.is-modified { background:var(--info); }
        .pe-gop { background:#fff; border:1px solid var(--line); border-radius:10px; margin-bottom:10px; scroll-margin-top:84px; }
        #add-gop { scroll-margin-top:84px; }
        .pe-gop[open] { box-shadow:0 2px 10px rgba(15,23,42,.06); }
        .pe-gop-sum { list-style:none; display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:12px 14px; cursor:pointer; }
        .pe-gop-sum::-webkit-details-marker, .pe-prop-row::-webkit-details-marker { display:none; }
        .pe-gop-title { flex:1 1 260px; min-width:0; }
        .pe-chev { width:0; height:0; border-left:5px solid #64748b; border-top:4px solid transparent; border-bottom:4px solid transparent; transition:transform .12s; flex:none; display:inline-block; }
        details[open] > summary .pe-chev, details[open] > summary > .pe-prop-name > .pe-chev { transform:rotate(90deg); }
        .pe-gop-body { border-top:1px solid var(--line); padding:12px 14px 14px; }
        .pe-toolbar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
        .pe-panel { border:1px solid var(--line); background:var(--soft); border-radius:8px; padding:12px; margin-bottom:12px; }
        .pe-props { border:1px solid var(--line); border-radius:8px; overflow:hidden; }
        .pe-prop-head, .pe-prop-row { display:grid; grid-template-columns:minmax(0, 1fr) 110px 110px 70px 86px; gap:10px; align-items:center; padding:8px 12px; }
        .pe-prop-head { background:var(--soft); font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:600; }
        .pe-prop { border-top:1px solid #f1f5f9; }
        .pe-prop-row { list-style:none; cursor:pointer; }
        .pe-prop-row:hover { background:#fafcff; }
        .pe-prop[open] > .pe-prop-row { background:#f8fafc; }
        .pe-prop-name { display:flex; gap:8px; align-items:baseline; min-width:0; }
        .pe-prop-name > span:last-child { min-width:0; overflow-wrap:anywhere; }
        .pe-code { margin-left:4px; }
        .pe-prop-body { padding:12px 14px 16px; background:#fcfdfe; border-top:1px dashed var(--line); }
        .pe-prop-panes { display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:20px; }
        .pe-prop-panes h3 { margin-bottom:2px; }
        .pe-empty { padding:14px; color:var(--muted); text-align:center; }
        .pe-hit-none { display:none !important; }
        .pe-modal { position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:60; padding:16px; }
        .pe-modal-box { background:#fff; border-radius:10px; padding:20px; width:min(640px, 100%); max-height:88vh; overflow:auto; box-shadow:0 20px 50px rgba(0,0,0,.25); }
        @media (max-width: 960px) {
            .pe-layout { grid-template-columns:1fr; }
            .pe-side { position:static; max-height:none; }
            .pe-prop-head { display:none; }
            .pe-prop-row { grid-template-columns:1fr auto; }
            .pe-prop-row > span:nth-child(2), .pe-prop-row > span:nth-child(3), .pe-prop-row > span:nth-child(4) { display:none; }
        }
    </style>

    <div class="adm" style="background:#f8fafc; min-height:100vh;" id="preview-editor" data-pdt-id="{{ $pdt->Id }}">

        {{-- Sticky action bar --}}
        <div class="pe-bar">
            <div class="pe-bar-in">
                <div style="flex:1 1 360px; min-width:0;">
                    <h1>{{ $pdt->pdtNameEn }} <span class="adm-muted" style="font-weight:400;">/ {{ $pdt->pdtNamePt }}</span></h1>
                    <div class="adm-small adm-muted" style="margin-top:2px;">
                        @if ($isVersion)
                            <span class="adm-pill is-draft">New version draft</span>
                            based on live <span class="adm-ver">v{{ $base->versionNumber }}.{{ $base->revisionNumber }}</span>
                        @else
                            <span class="adm-pill is-draft">New template draft</span> not published yet
                        @endif
                        · offline · saved as you go · last edited {{ $pdt->updated_at }}
                    </div>
                </div>
                <div class="adm-actions">
                    <a href="{{ route('admin.previews.review', ['pdt' => $pdt->Id]) }}" class="adm-btn is-go">Review &amp; publish →</a>
                    @if ($isVersion)
                        <a href="{{ route('pdtsdownload', ['pdtID' => $base->Id]) }}" class="adm-btn is-ghost" target="_blank">View live</a>
                    @endif
                    <button type="button" class="adm-btn is-danger" id="btn-delete">Discard draft…</button>
                    <a href="{{ route('admin') }}" class="adm-btn is-ghost">Admin</a>
                </div>
            </div>
        </div>

        <div class="adm-page" style="padding-top:18px;">
            @foreach (['success' => 'is-ok', 'error' => 'is-bad'] as $key => $cls)
                @if (session($key))
                    <div class="adm-banner {{ $cls }}">{{ session($key) }}</div>
                @endif
            @endforeach

            @if ($isVersion)
                <div class="adm-banner {{ $diff['hasChanges'] ? 'is-info' : 'is-warn' }}">
                    <div style="flex:1;">
                        @if ($diff['hasChanges'])
                            <b>Changes so far:</b>
                            {{ collect([
                                $diff['pdtChanges'] ? 'template details' : null,
                                ($n = $changedGops->where('state', 'added')->count()) ? $n . ' group(s) added' : null,
                                ($n = $changedGops->where('state', 'removed')->count()) ? $n . ' group(s) removed' : null,
                                ($n = $changedGops->where('state', 'modified')->count()) ? $n . ' group(s) changed' : null,
                            ])->filter()->implode(' · ') }}
                            — publishes as a <b>{{ $diff['bump'] }}</b>
                            (<span class="adm-ver">v{{ implode('.', $diff['next'][$diff['bump']]) }}</span>).
                        @else
                            <b>No changes yet.</b> This draft is still identical to live v{{ $base->versionNumber }}.{{ $base->revisionNumber }}.
                        @endif
                        <div class="adm-small" style="margin-top:4px;">The public site keeps showing the live version until you publish.
                            Relationships and property dependencies are shared by all versions, so editing them takes effect immediately.</div>
                    </div>
                </div>
            @endif

            <div class="pe-layout">
                {{-- Sidebar: search + group navigation --}}
                <aside class="pe-side adm-card" style="margin:0;">
                    <div class="adm-card-b" style="padding:12px;">
                        <input type="search" id="pe-search" class="adm-input" placeholder="Find a property…" autocomplete="off">
                        <div id="pe-search-info" class="adm-small adm-muted" style="margin-top:6px;" hidden></div>
                        <div class="adm-small adm-muted" style="margin:10px 2px 6px; display:flex; gap:10px; align-items:center;">
                            <span>{{ $gops->count() }} groups · <span id="pe-total">{{ $totalProps }}</span> properties</span>
                        </div>
                        <div style="display:flex; gap:6px; margin-bottom:8px;">
                            <button type="button" class="adm-btn is-sm is-ghost" id="pe-expand">Expand all</button>
                            <button type="button" class="adm-btn is-sm is-ghost" id="pe-collapse">Collapse all</button>
                        </div>
                        <nav class="pe-nav">
                            <a href="#tpl-details" data-target="tpl-details"><span class="pe-dot {{ $diff['pdtChanges'] ? 'is-modified' : '' }}"></span>Template details</a>
                            <div style="border-top:1px solid var(--line); margin:6px 0;"></div>
                            @foreach ($gops as $gop)
                                @php $m = $gopMarkers[$gop->Id] ?? null; @endphp
                                <a href="#gop-{{ $gop->Id }}" data-target="gop-{{ $gop->Id }}" class="js-nav-gop" data-gop-id="{{ $gop->Id }}">
                                    <span class="pe-dot {{ $isVersion && in_array($m, ['added', 'modified'], true) ? 'is-' . $m : '' }}"></span>
                                    <span style="min-width:0; overflow-wrap:anywhere;">{{ $gop->gopNameEn ?: $gop->gopNamePt }}</span>
                                    <span class="n js-nav-count">{{ $contextByGop->get($gop->Id, collect())->count() }}</span>
                                </a>
                            @endforeach
                            <div style="border-top:1px solid var(--line); margin:6px 0;"></div>
                            <a href="#add-gop" data-target="add-gop" style="color:var(--info);">+ Add a group</a>
                        </nav>
                    </div>
                </aside>

                <main style="min-width:0;">
                    {{-- Template details --}}
                    <details class="pe-gop" id="tpl-details" {{ $gops->isEmpty() && !$isVersion ? 'open' : '' }}>
                        <summary class="pe-gop-sum">
                            <span class="pe-chev" aria-hidden="true"></span>
                            <span class="pe-gop-title"><strong>Template details</strong>
                                <span class="adm-muted adm-small">name, description, category, construction object, reference document, relationships</span></span>
                            @if ($isVersion && $diff['pdtChanges'])<span class="adm-pill is-modified">changed</span>@endif
                        </summary>
                        <div class="pe-gop-body js-pdt-attrs">
                            @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => (array) $pdt, 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                            <div class="mt-3 flex items-center gap-3">
                                <button type="button" class="adm-btn is-sm js-save-pdt">Save template details</button>
                                <span class="js-pdt-status adm-small"></span>
                            </div>
                            <x-relation-editor entity-type="pdt" :guid="$pdt->GUID" title="Template relationships" />
                        </div>
                    </details>

                    <div style="display:flex; align-items:baseline; gap:10px; margin:18px 2px 10px;">
                        <h2>Groups of properties</h2>
                        <span class="adm-muted adm-small">Click a group to open it, then a property to edit it.</span>
                    </div>

                    {{-- Shared lookup lists, printed here — outside the forms rendered on demand
                         from <template> — so every form references one live copy. --}}
                    {!! \App\Support\AttrDatalists::lookup(true) !!}
                    {!! \App\Support\AttrDatalists::lookup(false) !!}
                    @foreach ($dictEnums as $field => $values)
                        @if (count($values) > 20){!! \App\Support\AttrDatalists::enum($field, $values) !!}@endif
                    @endforeach

                    <div id="gop-list">
                        @forelse ($gops as $gop)
                            @include('admin.partials._preview-gop', [
                                'gop' => $gop, 'context' => $contextByGop->get($gop->Id, collect()),
                                'dictRows' => $dictRows, 'gopFields' => $gopFields, 'ctxFields' => $ctxFields,
                                'dictFields' => $dictFields, 'dictEnums' => $dictEnums,
                                'marker' => $isVersion ? ($gopMarkers[$gop->Id] ?? null) : null, 'ctxMarkers' => $ctxMarkers,
                            ])
                        @empty
                            <div class="adm-card adm-empty" style="margin:0 0 10px;">No groups yet — add the first one below.</div>
                        @endforelse
                    </div>

                    {{-- Add a group --}}
                    <div class="adm-card" id="add-gop" style="margin-top:16px;">
                        <div class="adm-card-h">
                            <h2>Add a group of properties</h2>
                            <span class="adm-sub">A new, empty group. It opens straight away so you can add its properties.</span>
                        </div>
                        <div class="adm-card-b">
                            <div class="flex flex-wrap items-center gap-2" style="margin-bottom:10px;">
                                <label class="adm-small" style="font-weight:600;" for="gop-name-pick">Start from an existing group name:</label>
                                <select id="gop-name-pick" class="adm-input" style="max-width:340px;"><option value="">— choose (optional) —</option></select>
                                <span class="adm-small adm-muted">fills in name + definition; you can still edit them</span>
                            </div>
                            <div class="js-addgop-attrs">
                                @include('admin.partials._attr-fields', ['fields' => $gopFields, 'values' => [], 'prefix' => 'addgop', 'idAttr' => 'addgop'])
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <button type="button" class="adm-btn" id="btn-add-gop">Create group</button>
                                <span id="add-gop-status" class="adm-small"></span>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    {{-- Discard confirm --}}
    <div id="delete-modal" class="pe-modal adm" style="display:none;">
        <div class="pe-modal-box">
            <h2 style="color:var(--bad); margin-bottom:6px;">Discard this draft?</h2>
            <p class="adm-small adm-muted" style="margin:0 0 10px;">
                @if ($isVersion)
                    The draft and everything edited in it are deleted. The live version v{{ $base->versionNumber }}.{{ $base->revisionNumber }} is not affected.
                @else
                    The draft template and the properties created only for it are deleted permanently.
                @endif
            </p>
            <div id="delete-summary" class="adm-mono" style="white-space:pre-wrap; background:var(--soft); border:1px solid var(--line); border-radius:8px; padding:10px; max-height:260px; overflow:auto;">Loading…</div>
            <div class="adm-actions" style="margin-top:14px;">
                <button type="button" class="adm-btn is-danger" id="btn-delete-confirm" disabled>Discard permanently</button>
                <button type="button" class="adm-btn is-ghost" id="btn-delete-cancel">Cancel</button>
                <span id="delete-status" class="adm-small"></span>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('preview-editor');
            const pdtId = root.dataset.pdtId;
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
                afterDelete: "{{ route('admin') }}",
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
                el.style.color = msg ? (good ? 'var(--ok)' : 'var(--bad)') : '';
            }
            function collectAttrs(scope) {
                const out = {};
                scope.querySelectorAll('.js-attr').forEach(el => { if (!el.disabled) out[el.dataset.field] = el.value; });
                return out;
            }
            function missingMandatory(scope) {
                const miss = [];
                scope.querySelectorAll('.js-mandatory').forEach(el => { if (!el.disabled && (el.value || '').trim() === '') miss.push(el.dataset.field); });
                return miss;
            }
            function openExpandersWithMandatory(scope) {
                scope.querySelectorAll('details').forEach(d => { if (d.querySelector('.js-mandatory')) d.open = true; });
            }
            function setField(scope, field, value) {
                const el = scope.querySelector('.js-attr[data-field="' + field + '"]');
                if (el) el.value = value == null ? '' : value;
            }
            // Reload the page and land on a group (optionally with its picker open).
            function reloadTo(hash) {
                rememberOpen();
                if (location.hash === '#' + hash) location.reload();
                else { location.hash = hash; location.reload(); }
            }

            // ---------- lazy rendering ----------
            // Heavy forms live in <template class="js-lazy"> until first needed.
            function materialize(host, wire) {
                const tpl = host.querySelector(':scope > template.js-lazy');
                if (!tpl) return;
                host.appendChild(tpl.content.cloneNode(true));
                tpl.remove();
                // Scripts cloned from a template do not run on their own.
                host.querySelectorAll('script').forEach(old => {
                    const s = document.createElement('script');
                    s.textContent = old.textContent;
                    old.replaceWith(s);
                });
                window.pdtsWireRelationEditors && window.pdtsWireRelationEditors();
                window.pdtsWirePropertyDependencyEditors && window.pdtsWirePropertyDependencyEditors();
                host.dispatchEvent(new MouseEvent('click', { bubbles: true })); // lets the units helper decorate new fields
                if (wire) wire(host);
            }

            // ---------- remember which groups are open ----------
            const storeKey = 'pe-open-' + pdtId;
            function rememberOpen() {
                try { sessionStorage.setItem(storeKey, JSON.stringify([...root.querySelectorAll('.js-gop-card[open]')].map(d => d.id))); } catch (e) {}
            }
            function restoreOpen() {
                try { (JSON.parse(sessionStorage.getItem(storeKey) || '[]') || []).forEach(id => { const d = document.getElementById(id); if (d) d.open = true; }); } catch (e) {}
            }

            // ---------- template details ----------
            const pdtScope = root.querySelector('.js-pdt-attrs');
            pdtScope.querySelector('.js-save-pdt').addEventListener('click', function () {
                const st = pdtScope.querySelector('.js-pdt-status');
                const miss = missingMandatory(pdtScope);
                if (miss.length) { openExpandersWithMandatory(pdtScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                setStatus(st, 'Saving…', true);
                post(u.editPdt, { attrs: collectAttrs(pdtScope) }).then(({ ok, body }) =>
                    setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
            });

            // ---------- groups ----------
            root.querySelectorAll('.js-gop-card').forEach(wireGop);

            function updateCounts(card, delta) {
                const c = card.querySelector('.js-gop-count');
                const n = Math.max(0, parseInt(c.textContent, 10) + delta);
                c.textContent = n;
                card.querySelector('.js-gop-plural').textContent = n === 1 ? 'y' : 'ies';
                card.querySelector('.js-props-empty').hidden = n > 0;
                const nav = root.querySelector('.js-nav-gop[data-gop-id="' + card.dataset.gopId + '"] .js-nav-count');
                if (nav) nav.textContent = n;
                const total = document.getElementById('pe-total');
                total.textContent = Math.max(0, parseInt(total.textContent, 10) + delta);
            }

            function wireGop(card) {
                const gopId = parseInt(card.dataset.gopId, 10);
                card.addEventListener('toggle', rememberOpen);

                const details = card.querySelector('.js-gop-details');
                card.querySelector('.js-toggle-gop-details').addEventListener('click', function () {
                    materialize(details, wireGopDetails);
                    details.hidden = !details.hidden;
                    this.classList.toggle('is-ghost', details.hidden);
                });
                function wireGopDetails(scope) {
                    const gopScope = scope.querySelector('.js-gop-attrs');
                    scope.querySelector('.js-save-gop').addEventListener('click', function () {
                        const st = scope.querySelector('.js-gop-status');
                        const miss = missingMandatory(gopScope);
                        if (miss.length) { openExpandersWithMandatory(gopScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                        setStatus(st, 'Saving…', true);
                        post(u.editGop, { gopId, attrs: collectAttrs(gopScope) }).then(({ ok, body }) =>
                            setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                    });
                }

                const pickerWrap = card.querySelector('.js-picker-wrap');
                const pickerBtn = card.querySelector('.js-toggle-picker');
                card.openPicker = function () {
                    materialize(pickerWrap, w => wirePicker(w.querySelector('.js-picker'), card));
                    pickerWrap.hidden = false;
                    pickerBtn.textContent = '− Close add properties';
                    const q = pickerWrap.querySelector('.js-pick-q');
                    if (q) q.focus();
                };
                pickerBtn.addEventListener('click', function () {
                    if (pickerWrap.hidden) card.openPicker();
                    else { pickerWrap.hidden = true; pickerBtn.textContent = '+ Add properties'; }
                });

                card.querySelector('.js-remove-gop').addEventListener('click', function () {
                    const n = parseInt(card.querySelector('.js-gop-count').textContent, 10);
                    if (!confirm('Remove this group' + (n ? ' and its ' + n + ' propert' + (n === 1 ? 'y' : 'ies') : '') + ' from the draft?')) return;
                    post(u.removeGop, { gopId }).then(({ ok, body }) => {
                        if (!(ok && body.ok)) { alert(body.error || 'Failed'); return; }
                        const total = document.getElementById('pe-total');
                        total.textContent = Math.max(0, parseInt(total.textContent, 10) - n);
                        const nav = root.querySelector('.js-nav-gop[data-gop-id="' + gopId + '"]');
                        if (nav) nav.remove();
                        card.remove();
                    });
                });

                card.querySelectorAll('.js-context').forEach(block => wireContextShell(block, card));
            }

            // The always-present part of a property row; the editing forms wire on first open.
            function wireContextShell(block, card) {
                const contextId = parseInt(block.dataset.contextId, 10);
                block.addEventListener('toggle', () => { if (block.open) materialize(block.querySelector('.js-lazy-host'), host => wireContextForms(host, block, contextId)); });
                block.querySelector('.js-remove-context').addEventListener('click', function (e) {
                    e.preventDefault(); e.stopPropagation(); // do not toggle the row
                    if (!confirm('Remove this property from the group?')) return;
                    post(u.removeContext, { contextId }).then(({ ok, body }) => {
                        if (!(ok && body.ok)) { alert(body.error || 'Failed'); return; }
                        block.remove();
                        updateCounts(card, -1);
                    });
                });
            }

            function wireContextForms(host, block, contextId) {
                const ctxScope = host.querySelector('.js-ctx-attrs');
                const defScope = host.querySelector('.js-def-attrs');
                host.querySelector('.js-save-context').addEventListener('click', function () {
                    const st = host.querySelector('.js-context-status');
                    const miss = missingMandatory(ctxScope);
                    if (miss.length) { setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Saving…', true);
                    post(u.editContext, { contextId, attrs: collectAttrs(ctxScope) }).then(({ ok, body }) =>
                        setStatus(st, (ok && body.ok) ? '✓ Saved' : '✗ ' + (body.error || 'Failed'), ok && body.ok));
                });
                host.querySelector('.js-save-def').addEventListener('click', function () {
                    const st = host.querySelector('.js-def-status');
                    const miss = missingMandatory(defScope);
                    if (miss.length) { openExpandersWithMandatory(defScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Saving…', true);
                    post(u.editProperty, { contextId, values: collectAttrs(defScope) }).then(({ ok, body }) => {
                        if (!(ok && body.ok)) { setStatus(st, '✗ ' + (body.error || 'Failed'), false); return; }
                        setStatus(st, body.forked ? '✓ Saved as a draft copy — the live definition is unchanged' : '✓ Saved', true);
                        const tag = block.querySelector('.js-shared-tag');
                        if (body.forked && tag) { tag.textContent = 'draft definition'; tag.className = 'adm-pill is-draft js-shared-tag'; }
                    });
                });
            }

            // ---------- property picker ----------
            function wirePicker(p, card) {
                if (!p) return;
                const gopId = parseInt(p.dataset.gopId, 10);
                const results = p.querySelector('.js-pick-results');
                const addBtn = p.querySelector('.js-pick-add');
                const counts = p.querySelector('.js-pick-counts');
                const gapBtn = p.querySelector('.js-pick-gap');
                const qInput = p.querySelector('.js-pick-q');
                const selected = new Map(); // dictId -> name
                let lastGap = [];

                function refreshAddBtn() { addBtn.disabled = selected.size === 0; addBtn.textContent = 'Add selected (' + selected.size + ')'; }
                function renderRow(r, checked) {
                    return '<label class="flex items-start gap-2 py-1 border-b">'
                        + '<input type="checkbox" class="js-pick-cb mt-1" data-id="' + r.Id + '" data-name="' + esc(r.nameEn) + '" ' + (checked ? 'checked' : '') + '>'
                        + '<span><span class="font-semibold">' + esc(r.nameEn) + '</span>' + (r.namePt ? ' / ' + esc(r.namePt) : '')
                        + (r.versionNumber !== '' ? ' <span class="text-gray-500 text-xs">(v' + r.versionNumber + '.' + r.revisionNumber + ')</span>' : '')
                        + '<br><span class="text-gray-600 text-xs">' + esc((r.definitionEn || '').slice(0, 160)) + '</span></span></label>';
                }
                function bindRows() {
                    results.querySelectorAll('.js-pick-cb').forEach(cb => cb.addEventListener('change', function () {
                        const id = parseInt(cb.dataset.id, 10);
                        if (cb.checked) selected.set(id, cb.dataset.name); else selected.delete(id);
                        refreshAddBtn();
                    }));
                }
                function search() {
                    const q = qInput.value.trim();
                    if (q === '') { results.innerHTML = ''; return; }
                    results.innerHTML = 'Searching…';
                    get(u.pickProps + '?q=' + encodeURIComponent(q)).then(({ ok, body }) => {
                        if (!ok) { results.innerHTML = 'Search failed.'; return; }
                        const rows = body.results || [];
                        results.innerHTML = rows.length ? rows.map(r => renderRow(r, selected.has(r.Id))).join('')
                            : '<span class="text-gray-600">No matches — create it below with “Create a NEW one”.</span>';
                        bindRows();
                    });
                }
                qInput.addEventListener('input', function () { if (this.value.trim() === '') results.innerHTML = ''; });
                qInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); search(); } });
                p.querySelector('.js-pick-search').addEventListener('click', search);

                p.querySelector('.js-pick-upload').addEventListener('click', function () {
                    const file = p.querySelector('.js-pick-file').files[0];
                    if (!file) { setStatus(counts, 'Choose a file first.', false); return; }
                    const fd = new FormData();
                    fd.append('excelFile', file);
                    fd.append('groupName', p.dataset.gopName || '');
                    fd.append('groupNamePt', p.dataset.gopNamePt || '');
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
                            setStatus(counts, 'Matched/selected ' + body.matchedCount + ' · not found ' + body.unmatchedCount, true);
                            gapBtn.style.display = lastGap.length ? '' : 'none';
                            const nf = p.querySelector('.js-pick-notfound');
                            p.querySelector('.js-pick-notfound-list').innerHTML = lastGap.map(n => '<li>' + esc(n) + '</li>').join('');
                            nf.style.display = lastGap.length ? '' : 'none';
                            results.innerHTML = (body.matchedNames || []).map((n, i) =>
                                renderRow({ Id: body.matchedIds[i], nameEn: n, namePt: '', versionNumber: '', revisionNumber: '', definitionEn: '' }, true)).join('')
                                || '<span class="text-gray-600">No existing matches — use the gap list to create them.</span>';
                            bindRows();
                            refreshAddBtn();
                        }).catch(e => setStatus(counts, 'Error: ' + e, false));
                });

                gapBtn.addEventListener('click', function () {
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
                    addBtn.disabled = true;
                    (async () => {
                        for (let i = 0; i < ids.length; i++) {
                            setStatus(st, 'Adding ' + (i + 1) + ' of ' + ids.length + '…', true);
                            const { ok, body } = await post(u.addExisting, { gopId, dictId: ids[i] });
                            if (!ok || !body.ok) { setStatus(st, '✗ ' + (body.error || 'Failed'), false); addBtn.disabled = false; return; }
                        }
                        reloadTo('gop-' + gopId + '-add');
                    })();
                });

                const np = p.querySelector('.js-newprop');
                p.querySelector('.js-newprop-add').addEventListener('click', function () {
                    const st = p.querySelector('.js-newprop-status');
                    const miss = missingMandatory(np);
                    if (miss.length) { openExpandersWithMandatory(np); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                    setStatus(st, 'Creating…', true);
                    post(u.addNew, { gopId, values: collectAttrs(np) }).then(({ ok, body }) =>
                        (ok && body.ok) ? reloadTo('gop-' + gopId + '-add') : setStatus(st, '✗ ' + (body.error || 'Failed'), false));
                });
            }

            // ---------- add a group ----------
            const addgopScope = root.querySelector('.js-addgop-attrs');
            let suggestions = [];
            get(u.gopSuggest).then(({ ok, body }) => {
                if (!ok || !body.ok) return;
                suggestions = body.results || [];
                const sel = document.getElementById('gop-name-pick');
                suggestions.forEach((s, i) => {
                    const o = document.createElement('option');
                    o.value = String(i);
                    o.textContent = (s.gopNameEn || s.gopNamePt || '(unnamed)') + (s.gopNamePt && s.gopNameEn ? ' / ' + s.gopNamePt : '');
                    sel.appendChild(o);
                });
            });
            document.getElementById('gop-name-pick').addEventListener('change', function () {
                const s = suggestions[parseInt(this.value, 10)];
                if (!s) return;
                ['gopNameEn', 'gopNamePt', 'definitionEn', 'definitionPt'].forEach(f => setField(addgopScope, f, s[f]));
            });
            document.getElementById('btn-add-gop').addEventListener('click', function () {
                const st = document.getElementById('add-gop-status');
                const miss = missingMandatory(addgopScope);
                if (miss.length) { openExpandersWithMandatory(addgopScope); setStatus(st, 'Missing: ' + miss.join(', '), false); return; }
                setStatus(st, 'Creating…', true);
                post(u.addGop, { attrs: collectAttrs(addgopScope) }).then(({ ok, body }) =>
                    (ok && body.ok) ? reloadTo('gop-' + body.gopId + '-add') : setStatus(st, '✗ ' + (body.error || 'Failed'), false));
            });

            // ---------- navigation, expand/collapse, search ----------
            function reveal(id, openPicker) {
                const el = document.getElementById(id);
                if (!el) return;
                if (el.tagName === 'DETAILS') el.open = true;
                if (openPicker && el.openPicker) el.openPicker();
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            root.querySelectorAll('.pe-nav a').forEach(a => a.addEventListener('click', function (e) {
                e.preventDefault();
                history.replaceState(null, '', '#' + this.dataset.target);
                reveal(this.dataset.target, false);
            }));
            document.getElementById('pe-expand').addEventListener('click', () => { root.querySelectorAll('.js-gop-card').forEach(d => d.open = true); rememberOpen(); });
            document.getElementById('pe-collapse').addEventListener('click', () => { root.querySelectorAll('.js-gop-card').forEach(d => d.open = false); rememberOpen(); });

            const searchBox = document.getElementById('pe-search');
            const searchInfo = document.getElementById('pe-search-info');
            let openBeforeSearch = null;
            searchBox.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                const cards = [...root.querySelectorAll('.js-gop-card')];
                if (!q) {
                    cards.forEach(c => { c.classList.remove('pe-hit-none'); c.querySelectorAll('.js-context').forEach(p => p.classList.remove('pe-hit-none')); });
                    if (openBeforeSearch) cards.forEach(c => c.open = openBeforeSearch.has(c.id));
                    openBeforeSearch = null;
                    searchInfo.hidden = true;
                    return;
                }
                if (!openBeforeSearch) openBeforeSearch = new Set(cards.filter(c => c.open).map(c => c.id));
                let hits = 0;
                cards.forEach(c => {
                    let n = 0;
                    c.querySelectorAll('.js-context').forEach(p => { const hit = p.dataset.search.includes(q); p.classList.toggle('pe-hit-none', !hit); n += hit; });
                    c.classList.toggle('pe-hit-none', n === 0);
                    c.open = n > 0;
                    hits += n;
                });
                searchInfo.hidden = false;
                searchInfo.textContent = hits ? hits + ' matching propert' + (hits === 1 ? 'y' : 'ies') : 'No property matches.';
            });

            // ---------- discard ----------
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
                setStatus(st, 'Discarding…', true); this.disabled = true;
                post(u.deleteApply, {}).then(({ ok, body }) => {
                    if (ok && body.ok) { try { sessionStorage.removeItem(storeKey); } catch (e) {} window.location = u.afterDelete; }
                    else { setStatus(st, '✗ ' + (body.error || 'Failed'), false); this.disabled = false; }
                });
            });

            // ---------- initial state ----------
            restoreOpen();
            const m = location.hash.match(/^#(gop-\d+)(-add)?$/);
            if (m) reveal(m[1], !!m[2]);
            else if (location.hash === '#tpl-details' || location.hash === '#add-gop') reveal(location.hash.slice(1), false);
        })();
    </script>

    @include('components.partials._relation-editor-script')
    @include('components.partials._property-dependency-editor-script')
    @include('admin.partials._refdoc-modal')
    @include('admin.partials._units-autofill')
</x-app-layout>
