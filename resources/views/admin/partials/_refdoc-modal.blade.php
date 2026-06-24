{{-- Shared "add new reference document" modal + wiring. Include ONCE per editor page.
     Any referenceDocumentGUID <select class="js-refdoc"> with a sibling ".js-refdoc-new"
     button opens this; on create, the new option is appended to every ref-doc select and
     selected in the one that triggered it. --}}
<div id="refdoc-modal" class="rd-modal" style="display:none;">
    <div class="rd-modal-box">
        <h3 class="font-semibold text-lg mb-2">New reference document</h3>
        <div class="grid grid-cols-1 gap-3">
            <div><label class="block text-xs font-semibold mb-1">Title *</label><input type="text" id="rd-title" class="w-full border rounded p-2 text-sm"></div>
            <div><label class="block text-xs font-semibold mb-1">Name *</label><input type="text" id="rd-name" class="w-full border rounded p-2 text-sm"></div>
            <div><label class="block text-xs font-semibold mb-1">Description *</label><textarea id="rd-desc" rows="2" class="w-full border rounded p-2 text-sm"></textarea></div>
            <div><label class="block text-xs font-semibold mb-1">Status</label><input type="text" id="rd-status" value="Current" class="w-full border rounded p-2 text-sm"></div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button type="button" class="btn btn-secondary" id="rd-create">Create &amp; select</button>
            <button type="button" class="btn btn-secondary" id="rd-cancel">Cancel</button>
            <span id="rd-status-msg" class="text-sm"></span>
        </div>
    </div>
</div>
<style>
    .rd-modal { position: fixed; inset: 0; background: rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; z-index:60; }
    .rd-modal-box { background:#fff; border-radius:8px; padding:20px; width:min(560px,92vw); box-shadow:0 10px 40px rgba(0,0,0,.25); }
</style>
<script>
    (function () {
        const modal = document.getElementById('refdoc-modal');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const createUrl = "{{ route('admin.refdoc.createAjax') }}";

        // --- Searchable lookup fields (referenceDocumentGUID + constructionObjectGUID):
        // map the chosen datalist label back to its GUID into the hidden .js-attr. ---
        function syncLookup(input) {
            const hidden = document.getElementById(input.dataset.for);
            if (!hidden) return;
            const dl = document.getElementById(input.getAttribute('list'));
            const v = input.value.trim();
            if (v === '') { hidden.value = (hidden.dataset.field === 'referenceDocumentGUID') ? 'n/a' : ''; return; }
            const opt = dl ? [...dl.options].find(o => o.value === v) : null;
            if (opt) hidden.value = opt.dataset.guid;
            // if no exact match, leave the previous GUID (user is mid-type); blur handles reset
        }
        document.addEventListener('input', function (e) {
            if (e.target.classList && e.target.classList.contains('js-lookup-search')) syncLookup(e.target);
        });
        document.addEventListener('change', function (e) {
            if (e.target.classList && e.target.classList.contains('js-lookup-search')) syncLookup(e.target);
        });

        // --- Inline "new reference document" ---
        let activeHidden = null, activeSearch = null;
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-refdoc-new');
            if (!btn) return;
            const wrap = btn.closest('div');
            activeHidden = wrap ? wrap.querySelector('input[data-field="referenceDocumentGUID"]') : null;
            activeSearch = wrap ? wrap.querySelector('.js-lookup-search') : null;
            document.getElementById('rd-status-msg').textContent = '';
            modal.style.display = 'flex';
        });
        document.getElementById('rd-cancel').addEventListener('click', () => modal.style.display = 'none');

        document.getElementById('rd-create').addEventListener('click', function () {
            const msg = document.getElementById('rd-status-msg');
            const payload = {
                title: document.getElementById('rd-title').value.trim(),
                name: document.getElementById('rd-name').value.trim(),
                description: document.getElementById('rd-desc').value.trim(),
                status: document.getElementById('rd-status').value.trim() || 'Current',
            };
            if (!payload.title || !payload.name || !payload.description) {
                msg.textContent = 'Title, name and description are required.'; msg.className = 'text-sm text-red-700'; return;
            }
            msg.textContent = 'Creating…'; msg.className = 'text-sm';
            fetch(createUrl, { method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload) })
                .then(r => r.json()).then(body => {
                    if (!body.ok) { msg.textContent = '✗ ' + (body.error || 'Failed'); msg.className = 'text-sm text-red-700'; return; }
                    const label = payload.name + ': ' + body.label; // "rdName: Title"
                    // Append to every ref-doc datalist so all fields can pick it.
                    document.querySelectorAll('input[data-field="referenceDocumentGUID"]').forEach(h => {
                        const dl = document.getElementById('dl_' + h.id);
                        if (dl) { const o = document.createElement('option'); o.value = label; o.dataset.guid = body.guid; dl.appendChild(o); }
                    });
                    if (activeHidden) activeHidden.value = body.guid;
                    if (activeSearch) activeSearch.value = label;
                    modal.style.display = 'none';
                    ['rd-title', 'rd-name', 'rd-desc'].forEach(id => document.getElementById(id).value = '');
                })
                .catch(err => { msg.textContent = '✗ ' + err; msg.className = 'text-sm text-red-700'; });
        });
    })();
</script>
