@props([
    'guid',  // source property lineage GUID
    'title' => 'Property dependencies',
])

<div class="js-propdep-editor mt-4 border rounded shadow-sm" data-guid="{{ $guid }}">
    <div class="px-4 py-2 border-b bg-slate-50 font-semibold">{{ $title }}</div>
    <div class="p-4 text-sm">
        <div class="js-propdep-list mb-3 text-gray-700">…</div>

        <div class="border-t pt-3">
            <div class="font-semibold mb-1">Add a dependency</div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <select class="js-propdep-kind border rounded p-1 text-sm">
                    <option value="additive_context">additive context parameters</option>
                    <option value="combinative_context">combinative context parameters</option>
                    <option value="reference_proxy">reference proxy property</option>
                    <option value="alternative_proxy">alternative proxy properties</option>
                    <option value="function">function dependency</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <input type="text" class="js-propdep-q border rounded p-1 text-sm" placeholder="search target property…">
                <button type="button" class="btn btn-secondary js-propdep-search">Search</button>
            </div>
            <div class="js-propdep-results mb-2"></div>
            <div class="font-semibold text-xs mt-2">Chosen targets (ordered):</div>
            <ol class="js-propdep-chosen list-decimal ml-5 mb-2"><li class="text-gray-500 list-none">none</li></ol>
            <div class="js-propdep-fn mb-2" style="display:none;">
                <label class="text-xs font-semibold">Function expression (stored, not executed):</label>
                <textarea class="js-propdep-expr w-full border rounded p-1 text-sm" rows="2" placeholder="e.g. GWP_total = GWP_fossil + GWP_biogenic"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-secondary js-propdep-add">Add dependency</button>
                <span class="js-propdep-status text-xs"></span>
            </div>
        </div>
    </div>
</div>

@once
<script>
(function () {
    const PD = {
        indexBase:  "{{ url('admin/property-dependencies') }}",   // + /{guid}
        store:      "{{ route('admin.propdeps.store') }}",
        destroyBase:"{{ url('admin/property-dependencies') }}",   // + /{id}
        searchBase: "{{ url('admin/relations/search/property') }}",
        csrf:       document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const jget = (u) => fetch(u, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    const jsend = (u, m, p) => fetch(u, { method: m, headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':PD.csrf }, body: p ? JSON.stringify(p) : null }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));

    function wire(block) {
        const guid = block.dataset.guid;
        const listEl = block.querySelector('.js-propdep-list');
        const kindEl = block.querySelector('.js-propdep-kind');
        const resEl = block.querySelector('.js-propdep-results');
        const chosenEl = block.querySelector('.js-propdep-chosen');
        const fnEl = block.querySelector('.js-propdep-fn');
        const exprEl = block.querySelector('.js-propdep-expr');
        const statusEl = block.querySelector('.js-propdep-status');
        let chosen = []; // {guid,name,isPreferred}

        const isAlt = () => kindEl.value === 'alternative_proxy';
        kindEl.addEventListener('change', () => { fnEl.style.display = kindEl.value === 'function' ? '' : 'none'; renderChosen(); });

        function load() {
            jget(PD.indexBase + '/' + guid).then(j => {
                const deps = j.dependencies || [];
                listEl.innerHTML = deps.length ? deps.map(d =>
                    '<div class="border rounded p-2 mb-1">'
                    + '<span class="font-semibold">' + esc(d.kind) + '</span>'
                    + (d.expression ? ' <span class="text-gray-600">[' + esc(d.expression) + ']</span>' : '')
                    + ' <button type="button" class="btn btn-secondary js-pd-del" data-id="' + d.id + '" style="color:#7f1d1d;">remove</button>'
                    + '<ul class="ml-4 list-disc">' + d.targets.map(t =>
                        '<li>' + esc(t.name) + (t.isPreferred ? ' <span class="text-green-700">(preferred)</span>' : '') + '</li>').join('') + '</ul>'
                    + '</div>').join('') : '<span class="text-gray-500">none</span>';
                block.querySelectorAll('.js-pd-del').forEach(b => b.addEventListener('click', function () {
                    jsend(PD.destroyBase + '/' + this.dataset.id, 'DELETE', null).then(({ok,body}) => { if (ok && body.ok) load(); });
                }));
            });
        }

        function renderChosen() {
            chosenEl.innerHTML = chosen.length ? chosen.map((c, i) =>
                '<li data-i="' + i + '">' + esc(c.name)
                + (isAlt() ? ' <label class="text-xs"><input type="checkbox" class="js-pref" data-i="' + i + '" ' + (c.isPreferred ? 'checked' : '') + '> preferred</label>' : '')
                + ' <button type="button" class="btn btn-secondary js-unchoose" data-i="' + i + '">remove</button></li>').join('')
                : '<li class="text-gray-500 list-none">none</li>';
            chosenEl.querySelectorAll('.js-unchoose').forEach(b => b.addEventListener('click', function () { chosen.splice(+this.dataset.i, 1); renderChosen(); }));
            chosenEl.querySelectorAll('.js-pref').forEach(cb => cb.addEventListener('change', function () {
                // single preferred among alternatives
                chosen.forEach((c, i) => c.isPreferred = (i === +this.dataset.i) ? this.checked : false);
                renderChosen();
            }));
        }

        block.querySelector('.js-propdep-search').addEventListener('click', () => {
            const q = block.querySelector('.js-propdep-q').value.trim();
            if (!q) { resEl.innerHTML = ''; return; }
            resEl.innerHTML = 'Searching…';
            jget(PD.searchBase + '?q=' + encodeURIComponent(q)).then(j => {
                const rows = (j.results || []).filter(r => r.guid !== guid);
                resEl.innerHTML = rows.length ? rows.map((r, i) => '<div class="py-1 border-b"><a href="#" class="js-pick" data-i="' + i + '">' + esc(r.name) + '</a></div>').join('') : '<span class="text-gray-600">No matches.</span>';
                resEl.querySelectorAll('.js-pick').forEach(a => a.addEventListener('click', e => {
                    e.preventDefault();
                    const r = rows[+a.dataset.i];
                    if (!chosen.some(c => c.guid === r.guid)) { chosen.push({ guid: r.guid, name: r.name, isPreferred: false }); renderChosen(); }
                }));
            });
        });

        block.querySelector('.js-propdep-add').addEventListener('click', () => {
            if (!chosen.length) { statusEl.textContent = 'Pick at least one target.'; statusEl.style.color = '#b91c1c'; return; }
            const payload = {
                sourcePropertyGuid: guid,
                dependencyKind: kindEl.value,
                expression: kindEl.value === 'function' ? exprEl.value : null,
                targets: chosen.map((c, i) => ({ guid: c.guid, isPreferred: !!c.isPreferred, position: i })),
            };
            statusEl.textContent = 'Saving…'; statusEl.style.color = '';
            jsend(PD.store, 'POST', payload).then(({ ok, body }) => {
                if (ok && body.ok) { chosen = []; exprEl.value = ''; renderChosen(); resEl.innerHTML = ''; statusEl.textContent = '✓ added'; statusEl.style.color = '#15803d'; load(); }
                else { statusEl.textContent = '✗ ' + (body.error || 'Failed'); statusEl.style.color = '#b91c1c'; }
            });
        });

        renderChosen();
        load();
    }

    function init() { document.querySelectorAll('.js-propdep-editor').forEach(wire); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endonce
