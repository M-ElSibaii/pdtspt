@props([
    'entityType',   // 'pdt' | 'gop' | 'property' | 'objecttype'
    'guid',         // source lineage GUID
    'title' => 'Relationships (EN ISO 23387)',
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
<script>
(function () {
    const REL = {
        searchBase:  "{{ url('admin/relations/search') }}",      // + /{type}?q=
        indexBase:   "{{ url('admin/relations') }}",             // + /{type}/{guid}
        store:       "{{ route('admin.relations.store') }}",
        reorder:     "{{ route('admin.relations.reorder') }}",
        destroyBase: "{{ url('admin/relations') }}",             // + /{id}
        csrf:        document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const jget = (url) => fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    const jsend = (url, method, payload) => fetch(url, {
        method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': REL.csrf },
        body: payload ? JSON.stringify(payload) : null
    }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));

    function setStatus(el, msg, good) {
        if (!el) return;
        el.textContent = msg || '';
        el.style.color = msg ? (good ? '#15803d' : '#b91c1c') : '';
    }

    function wire(block) {
        const entityType = block.dataset.entityType;
        const guid       = block.dataset.guid;
        const subtypeRel = block.dataset.subtypeRel;
        const targetType = block.dataset.targetType;
        const hasPart    = block.dataset.haspart === '1';

        const parentBox   = block.querySelector('.js-rel-parent');
        const parentRes   = block.querySelector('.js-rel-parent-results');
        const parentStat  = block.querySelector('.js-rel-parent-status');
        const partsList   = block.querySelector('.js-rel-parts');
        const partRes     = block.querySelector('.js-rel-part-results');
        const partStat    = block.querySelector('.js-rel-part-status');

        let state = { parent: null, parts: [] }; // parent = relation obj or null

        function render() {
            // Parent
            if (state.parent) {
                parentBox.innerHTML = '<span class="font-semibold">' + esc(state.parent.targetName) + '</span> '
                    + '<button type="button" class="btn btn-secondary js-rel-del" data-id="' + state.parent.id + '" style="color:#7f1d1d;">remove</button>';
            } else {
                parentBox.innerHTML = '<span class="text-gray-500">none</span>';
            }
            // Parts
            if (hasPart) {
                partsList.innerHTML = state.parts.map((p, i) =>
                    '<li class="py-1" data-id="' + p.id + '">' + esc(p.targetName)
                    + ' <button type="button" class="btn btn-secondary js-part-up" data-i="' + i + '">↑</button>'
                    + ' <button type="button" class="btn btn-secondary js-part-down" data-i="' + i + '">↓</button>'
                    + ' <button type="button" class="btn btn-secondary js-rel-del" data-id="' + p.id + '" style="color:#7f1d1d;">remove</button>'
                    + '</li>').join('') || '<li class="text-gray-500 list-none">none</li>';
            }
            bindDeletes();
            bindReorder();
        }

        function load() {
            jget(REL.indexBase + '/' + entityType + '/' + guid).then(j => {
                const rels = j.relations || [];
                state.parent = rels.find(r => r.relationType === subtypeRel) || null;
                state.parts  = rels.filter(r => r.relationType === 'HasPart')
                                   .sort((a, b) => (a.position ?? 1e9) - (b.position ?? 1e9));
                render();
            });
        }

        function results(container, rows, onPick) {
            container.innerHTML = rows.length
                ? rows.map((r, i) => '<div class="py-1 border-b"><a href="#" class="js-pick" data-i="' + i + '">' + esc(r.name) + '</a></div>').join('')
                : '<span class="text-gray-600">No matches.</span>';
            container.querySelectorAll('.js-pick').forEach(a => a.addEventListener('click', e => {
                e.preventDefault(); onPick(rows[parseInt(a.dataset.i, 10)]);
            }));
        }

        function searchInto(q, container, stat, onPick) {
            if (q.trim() === '') { container.innerHTML = ''; return; }
            container.innerHTML = 'Searching…';
            jget(REL.searchBase + '/' + targetType + '?q=' + encodeURIComponent(q)).then(j => {
                results(container, (j.results || []).filter(r => r.guid !== guid), onPick);
            });
        }

        function create(relationType, target, stat, onDone) {
            setStatus(stat, 'Saving…', true);
            jsend(REL.store, 'POST', {
                sourceEntityType: entityType, sourceGuid: guid, relationType,
                targetEntityType: targetType, targetGuid: target.guid,
                position: relationType === 'HasPart' ? state.parts.length : null,
            }).then(({ ok, body }) => {
                if (ok && body.ok) { setStatus(stat, '✓ added', true); onDone(body.relation); }
                else setStatus(stat, '✗ ' + (body.error || 'Failed'), false);
            });
        }

        function del(id, stat) {
            return jsend(REL.destroyBase + '/' + id, 'DELETE', null).then(({ ok, body }) => ok && body.ok);
        }

        function bindDeletes() {
            block.querySelectorAll('.js-rel-del').forEach(b => b.addEventListener('click', function () {
                del(parseInt(this.dataset.id, 10)).then(okk => { if (okk) load(); });
            }));
        }
        function bindReorder() {
            if (!hasPart) return;
            const persist = () => jsend(REL.reorder, 'POST', { ids: state.parts.map(p => p.id) });
            block.querySelectorAll('.js-part-up').forEach(b => b.addEventListener('click', function () {
                const i = parseInt(this.dataset.i, 10);
                if (i <= 0) return;
                [state.parts[i - 1], state.parts[i]] = [state.parts[i], state.parts[i - 1]];
                render(); persist();
            }));
            block.querySelectorAll('.js-part-down').forEach(b => b.addEventListener('click', function () {
                const i = parseInt(this.dataset.i, 10);
                if (i >= state.parts.length - 1) return;
                [state.parts[i + 1], state.parts[i]] = [state.parts[i], state.parts[i + 1]];
                render(); persist();
            }));
        }

        // Parent (subtype/specialization): set/replace — 0..1 so remove existing first.
        block.querySelector('.js-rel-parent-search').addEventListener('click', () =>
            searchInto(block.querySelector('.js-rel-parent-q').value, parentRes, parentStat, (t) => {
                const proceed = () => create(subtypeRel, t, parentStat, () => { parentRes.innerHTML = ''; load(); });
                if (state.parent) del(state.parent.id, parentStat).then(proceed); else proceed();
            }));

        // HasPart: add.
        if (hasPart) {
            block.querySelector('.js-rel-part-search').addEventListener('click', () =>
                searchInto(block.querySelector('.js-rel-part-q').value, partRes, partStat, (t) =>
                    create('HasPart', t, partStat, () => { partRes.innerHTML = ''; load(); })));
        }

        load();
    }

    function init() { document.querySelectorAll('.js-relation-editor').forEach(wire); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endonce
