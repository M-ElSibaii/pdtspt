<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="mb-0">Deduped &amp; shared dictionary properties</h1>
                <a href="{{ route('admin.dedupe') }}" class="btn btn-secondary ml-auto">← Back to deduplication</a>
            </div>
            <p class="text-sm text-gray-600 mt-1">
                Every <code>propertiesdatadictionaries</code> entry used in <strong>two or more</strong> PDT/group
                contexts. Edit the general dictionary definition, and every in-context description, in place.
                Each save is independent.
            </p>

            @if ($schemaError)
                <div class="mt-6 p-4 rounded bg-red-100 text-red-800">
                    <strong>Schema problem:</strong> {{ $schemaError }}
                </div>
            @elseif (empty($rows))
                <div class="mt-6 p-4 rounded bg-green-100 text-green-800">
                    No dictionary property is used in more than one context.
                </div>
            @else
                @php
                    $attentionCount = collect($rows)->where('needsAttention', true)->count();
                @endphp

                {{-- Toolbar --}}
                <div class="mt-4 mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 sticky top-0 bg-white py-2 z-10 border-b">
                    <input type="text" id="dp-search"
                           class="border rounded p-2 text-sm w-72"
                           placeholder="Filter by name (nameEn / namePt)…">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" id="dp-attention-only">
                        Needs attention only
                        <span class="status-tag status-tag-inactive">{{ $attentionCount }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" id="dp-hide-preview">
                        Hide Preview
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" id="dp-hide-dealt">
                        Hide dealt-with
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" id="dp-hide-matching">
                        Hide instances matching dictionary
                    </label>
                    <span class="text-sm text-gray-600 ml-auto">
                        Showing <strong id="dp-visible">{{ count($rows) }}</strong> of {{ count($rows) }} shared propertie(s)
                    </span>
                </div>

                <div id="dp-list" class="space-y-6">
                    @foreach ($rows as $row)
                        @php $isDealt = !empty($dealtWith[$row['id']]); @endphp
                        <div class="dp-card border rounded shadow-sm{{ $isDealt ? ' dp-dealt' : '' }}"
                             data-name="{{ mb_strtolower(($row['nameEn'] ?? '') . ' ' . ($row['namePt'] ?? '')) }}"
                             data-attention="{{ $row['needsAttention'] ? '1' : '0' }}"
                             data-status="{{ mb_strtolower($row['status'] ?? '') }}"
                             data-dealt="{{ $isDealt ? '1' : '0' }}">

                            {{-- Header --}}
                            <div class="px-4 py-3 border-b bg-slate-50 flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-base">
                                    {{ $row['nameEn'] ?: '—' }}
                                    <span class="text-gray-400">/</span>
                                    {{ $row['namePt'] ?: '—' }}
                                </span>
                                @if ($row['needsAttention'])
                                    <span class="status-tag status-tag-inactive">needs attention</span>
                                @endif
                                <span class="status-tag status-tag-preview">{{ $row['refCount'] }} context(s)</span>
                                <label class="flex items-center gap-1 text-sm ml-auto cursor-pointer select-none">
                                    <input type="checkbox" class="dp-dealt-toggle" data-dict-id="{{ $row['id'] }}" {{ $isDealt ? 'checked' : '' }}>
                                    <span class="font-medium text-green-700">Dealt with</span>
                                </label>
                                <span class="text-xs text-gray-500 w-full text-right" style="word-break:break-all;">
                                    dict Id={{ $row['id'] }} · GUID={{ $row['guid'] }} · {{ $row['status'] }}
                                </span>
                            </div>

                            <div class="p-4">
                                {{-- Dictionary-level general definition --}}
                                <div class="dp-dict border rounded p-3 bg-blue-50" data-dict-id="{{ $row['id'] }}">
                                    <div class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">
                                        Dictionary definition (general — shared by all contexts)
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">definitionEn</label>
                                            <textarea class="dp-dict-en w-full border rounded p-2 text-sm" rows="3">{{ $row['definitionEn'] }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">definitionPt</label>
                                            <textarea class="dp-dict-pt w-full border rounded p-2 text-sm" rows="3">{{ $row['definitionPt'] }}</textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <button type="button" class="btn btn-secondary dp-dict-save">Save dictionary definition</button>
                                        <span class="dp-dict-status text-sm"></span>
                                    </div>
                                </div>

                                {{-- In-context instances --}}
                                <div class="mt-3 text-xs font-semibold text-gray-700 uppercase tracking-wide">
                                    In-context instances ({{ $row['refCount'] }})
                                </div>
                                <div class="mt-2 space-y-3">
                                    @foreach ($row['refs'] as $p)
                                        <div class="dp-prop border rounded p-3 bg-gray-50" data-property-id="{{ $p->Id }}">
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 items-baseline mb-2 text-sm">
                                                <span class="text-gray-700">
                                                    PDT: <strong>{{ $p->_pdtNameEn ?: ($p->_pdtNamePt ?: '—') }}</strong>
                                                    <span class="text-gray-400">/</span> {{ $p->_pdtNamePt ?: ($p->_pdtNameEn ?: '—') }}
                                                    (Id={{ $p->pdtID }}@if(isset($p->_pdtVersion)) · v{{ $p->_pdtVersion }}.{{ $p->_pdtRevision }}@endif)
                                                </span>
                                                <span class="text-gray-600">
                                                    Group: {{ $p->_gopNameEn ?: ($p->_gopNamePt ?: '—') }}
                                                    <span class="text-gray-400">/</span> {{ $p->_gopNamePt ?: ($p->_gopNameEn ?: '—') }}
                                                    (Id={{ $p->gopID }})
                                                </span>
                                                <span class="text-gray-400">properties.Id={{ $p->Id }}</span>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-semibold mb-1">descriptionEn (in context)</label>
                                                    <textarea class="dp-prop-en w-full border rounded p-2 text-sm" rows="3">{{ $p->descriptionEn }}</textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold mb-1">descriptionPt (in context)</label>
                                                    <textarea class="dp-prop-pt w-full border rounded p-2 text-sm" rows="3">{{ $p->descriptionPt }}</textarea>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex items-center gap-3">
                                                <button type="button" class="btn btn-secondary dp-prop-match"
                                                        title="Copy the dictionary definition above into these fields">↑ Match to dictionary</button>
                                                <button type="button" class="btn btn-secondary dp-prop-save">Save description</button>
                                                <span class="dp-prop-status text-sm"></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        .dp-card.dp-dealt { opacity: .55; }
        .dp-card.dp-dealt:hover { opacity: 1; }
    </style>

    <script>
        (function () {
            const dictUrl = "{{ route('admin.dedupe.dict') }}";
            const propUrl = "{{ route('admin.dedupe.property') }}";
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function post(url, payload, statusEl, btn) {
                statusEl.textContent = 'Saving…';
                statusEl.className = statusEl.className.replace(/\btext-(red|green)-700\b/g, '');
                btn.disabled = true;
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                })
                    .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
                    .then(({ ok, body }) => {
                        btn.disabled = false;
                        if (!ok || !body.ok) {
                            statusEl.textContent = '✗ ' + (body.error || 'Save failed.');
                            statusEl.className += ' text-red-700';
                            return;
                        }
                        statusEl.textContent = '✓ Saved';
                        statusEl.className += ' text-green-700';
                    })
                    .catch(err => {
                        btn.disabled = false;
                        statusEl.textContent = '✗ ' + err;
                        statusEl.className += ' text-red-700';
                    });
            }

            // Save dictionary definition
            document.querySelectorAll('.dp-dict-save').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const wrap = btn.closest('.dp-dict');
                    post(dictUrl, {
                        dictId: parseInt(wrap.dataset.dictId, 10),
                        definitionEn: wrap.querySelector('.dp-dict-en').value,
                        definitionPt: wrap.querySelector('.dp-dict-pt').value,
                    }, wrap.querySelector('.dp-dict-status'), btn);
                });
            });

            // Save in-context description
            document.querySelectorAll('.dp-prop-save').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const wrap = btn.closest('.dp-prop');
                    post(propUrl, {
                        propertyId: parseInt(wrap.dataset.propertyId, 10),
                        descriptionEn: wrap.querySelector('.dp-prop-en').value,
                        descriptionPt: wrap.querySelector('.dp-prop-pt').value,
                    }, wrap.querySelector('.dp-prop-status'), btn);
                });
            });

            // Match to dictionary: copy this card's dictionary definition into the
            // instance's description fields (no copy-paste). Fills only — the reviewer
            // still clicks Save.
            document.querySelectorAll('.dp-prop-match').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const card = btn.closest('.dp-card');
                    const wrap = btn.closest('.dp-prop');
                    wrap.querySelector('.dp-prop-en').value = card.querySelector('.dp-dict-en').value;
                    wrap.querySelector('.dp-prop-pt').value = card.querySelector('.dp-dict-pt').value;
                    const st = wrap.querySelector('.dp-prop-status');
                    st.textContent = '↑ Matched — click Save';
                    st.className = 'dp-prop-status text-sm text-blue-700';
                });
            });

            // Dealt-with toggle (persisted to a file, not the database).
            const reviewUrl = "{{ route('admin.dedupe.reviewState') }}";
            document.querySelectorAll('.dp-dealt-toggle').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    const card = cb.closest('.dp-card');
                    const dealt = cb.checked;
                    card.dataset.dealt = dealt ? '1' : '0';
                    card.classList.toggle('dp-dealt', dealt);
                    applyFilter();
                    fetch(reviewUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ dictId: parseInt(cb.dataset.dictId, 10), dealtWith: dealt }),
                    }).catch(function () { /* best-effort; UI already updated */ });
                });
            });

            // ---- Filters ----
            const search = document.getElementById('dp-search');
            const attentionOnly = document.getElementById('dp-attention-only');
            const hidePreview = document.getElementById('dp-hide-preview');
            const hideDealt = document.getElementById('dp-hide-dealt');
            const hideMatching = document.getElementById('dp-hide-matching');
            const visibleEl = document.getElementById('dp-visible');
            const cards = Array.from(document.querySelectorAll('.dp-card'));

            function applyFilter() {
                const q = (search.value || '').trim().toLowerCase();
                const attn = attentionOnly.checked;
                const hPrev = hidePreview.checked;
                const hDealt = hideDealt.checked;
                const hMatch = hideMatching.checked;
                let visible = 0;

                cards.forEach(function (card) {
                    let show = (q === '' || card.dataset.name.indexOf(q) !== -1)
                        && (!attn || card.dataset.attention === '1')
                        && (!hPrev || card.dataset.status !== 'preview')
                        && (!hDealt || card.dataset.dealt !== '1');

                    // Instance-level: optionally hide instances identical to the dictionary.
                    const props = card.querySelectorAll('.dp-prop');
                    if (show && hMatch) {
                        const dEn = card.querySelector('.dp-dict-en').value.trim();
                        const dPt = card.querySelector('.dp-dict-pt').value.trim();
                        let anyVisible = false;
                        props.forEach(function (p) {
                            const same = p.querySelector('.dp-prop-en').value.trim() === dEn
                                && p.querySelector('.dp-prop-pt').value.trim() === dPt;
                            p.style.display = same ? 'none' : '';
                            if (!same) anyVisible = true;
                        });
                        if (!anyVisible) show = false;   // whole card is fully matched
                    } else {
                        props.forEach(function (p) { p.style.display = ''; });
                    }

                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                if (visibleEl) visibleEl.textContent = visible;
            }

            [search, attentionOnly, hidePreview, hideDealt, hideMatching].forEach(function (el) {
                if (el) el.addEventListener(el.type === 'text' ? 'input' : 'change', applyFilter);
            });
        })();
    </script>
</x-app-layout>
