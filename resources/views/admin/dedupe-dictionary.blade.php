<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Review &amp; deduplicate dictionary properties</h1>
            <p class="text-sm text-gray-600 mt-1">
                Groups of <code>propertiesdatadictionaries</code> rows that share the exact same
                <strong>nameEn</strong>. Resolve one group at a time. Version variants (same GUID,
                different version/revision) are shown read-only and are never changed.
            </p>

            @if ($schemaError)
                <div class="mt-6 p-4 rounded bg-red-100 text-red-800">
                    <strong>Schema problem:</strong> {{ $schemaError }}
                </div>
            @elseif (empty($groups))
                <div class="mt-6 p-4 rounded bg-green-100 text-green-800">
                    No duplicate <code>nameEn</code> values found. Nothing to review.
                </div>
            @else
                <p class="mt-4 mb-2 text-sm text-gray-700">
                    <strong>{{ count($groups) }}</strong> duplicated name(s) found.
                </p>

                @foreach ($groups as $group)
                    @php
                        $actionable = array_merge([$group['survivor']], $group['duplicates']);
                        $hasMismatch = collect($actionable)->contains(fn($r) => !empty($r['mismatchProperties']));
                    @endphp

                    @php
                        $defsMap = collect($actionable)->mapWithKeys(fn($r) => [
                            $r['id'] => ['en' => $r['definitionEn'], 'pt' => $r['definitionPt']],
                        ]);
                    @endphp
                    <div class="dedupe-card mt-6 border rounded shadow-sm"
                         data-actionable-ids="{{ json_encode($group['actionableIds']) }}"
                         data-has-mismatch="{{ $hasMismatch ? '1' : '0' }}">

                        {{-- UTF-8 safe transport for JS (group name + per-survivor definitions) --}}
                        <input type="hidden" class="dd-name" value="{{ $group['name'] }}">
                        <script type="application/json" class="dd-defs">@json($defsMap)</script>

                        {{-- Header --}}
                        <div class="px-4 py-3 border-b bg-slate-50 flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-base">{{ $group['name'] }}</span>
                            @if (!$group['isActionable'])
                                <span class="status-tag status-tag-preview">only version variants — nothing to merge</span>
                            @endif
                            @if ($group['hasDescriptionConflict'])
                                <span class="status-tag status-tag-inactive">definitions differ</span>
                            @endif
                            @if ($hasMismatch)
                                <span class="status-tag status-tag-inactive">propertyId/GUID mismatch</span>
                            @endif
                            @if (!empty($group['versionVariants']))
                                <span class="status-tag status-tag-preview">{{ count($group['versionVariants']) }} version variant(s) kept</span>
                            @endif
                            <span class="ml-auto text-sm text-gray-600">
                                {{ $group['affectedCount'] }} properties row(s) reference the duplicate(s)
                            </span>
                        </div>

                        <div class="dedupe-body p-4">
                            {{-- Comparison table --}}
                            <table id="tblprop" class="text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left">Role</th>
                                        <th class="text-left">Id</th>
                                        <th class="text-left">GUID</th>
                                        <th class="text-left">Version</th>
                                        <th class="text-left">definitionEn</th>
                                        <th class="text-left">definitionPt</th>
                                        <th class="text-left"># refs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($actionable as $i => $row)
                                        <tr @if(!empty($row['mismatchProperties'])) class="highlight-duplicate" @endif>
                                            <td>{{ $i === 0 ? 'survivor (lowest Id)' : 'duplicate' }}</td>
                                            <td>{{ $row['id'] }}</td>
                                            <td style="word-break: break-all;">{{ $row['guid'] }}</td>
                                            <td>v{{ $row['versionNumber'] }}.{{ $row['revisionNumber'] }}</td>
                                            <td style="min-width:240px; max-width:420px; white-space:pre-wrap; word-break:break-word; text-align:left;">{{ $row['definitionEn'] }}</td>
                                            <td style="min-width:240px; max-width:420px; white-space:pre-wrap; word-break:break-word; text-align:left;">{{ $row['definitionPt'] }}</td>
                                            <td>
                                                {{ $row['referenceCount'] }}
                                                @if (!empty($row['mismatchProperties']))
                                                    <span class="text-red-700 font-semibold">
                                                        ({{ count($row['mismatchProperties']) }} mismatch)
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Version variants (read-only) --}}
                            @if (!empty($group['versionVariants']))
                                <div class="mt-3 p-3 rounded bg-gray-50 border text-sm">
                                    <strong>Version variants — left untouched (read-only):</strong>
                                    <ul class="list-disc ml-6 mt-1">
                                        @foreach ($group['versionVariants'] as $v)
                                            <li>Id={{ $v['id'] }} · GUID={{ $v['guid'] }} · v{{ $v['versionNumber'] }}.{{ $v['revisionNumber'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Mismatch detail --}}
                            @if ($hasMismatch)
                                <details class="mt-3 text-sm">
                                    <summary class="cursor-pointer text-red-700 font-semibold">
                                        ⚠ Some referencing properties disagree: their propertyId points to a
                                        dictionary row with a different GUID than the property's own GUID.
                                    </summary>
                                    <ul class="list-disc ml-6 mt-1">
                                        @foreach ($actionable as $row)
                                            @foreach ($row['mismatchProperties'] as $mp)
                                                <li>
                                                    properties.Id={{ $mp->Id }} (pdtID={{ $mp->pdtID }}, gopID={{ $mp->gopID }}):
                                                    GUID={{ $mp->GUID }}, propertyId={{ $mp->propertyId }} →
                                                    points at dict "{{ $mp->_dictNameByPropertyId ?? 'MISSING' }}"
                                                    (GUID={{ $mp->_dictGuidByPropertyId ?? 'none' }})
                                                </li>
                                            @endforeach
                                        @endforeach
                                    </ul>
                                </details>
                            @endif

                            {{-- Decision controls --}}
                            <div class="dedupe-controls mt-4 border-t pt-4">
                                @if ($group['isActionable'])
                                    <div class="flex flex-wrap gap-4 mb-3">
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="action_{{ $loop->index }}" class="dd-action" value="merge" checked> Merge
                                        </label>
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="action_{{ $loop->index }}" class="dd-action" value="keep_separate"> Keep separate
                                        </label>
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="action_{{ $loop->index }}" class="dd-action" value="skip"> Skip
                                        </label>
                                    </div>

                                    {{-- MERGE panel --}}
                                    <div class="dd-panel dd-panel-merge">
                                        <div class="mb-3">
                                            <div class="font-semibold text-sm mb-1">Survivor (kept row):</div>
                                            @foreach ($actionable as $i => $row)
                                                <label class="flex items-center gap-2 text-sm">
                                                    <input type="radio" class="dd-survivor" name="survivor_{{ $loop->parent->index }}"
                                                           value="{{ $row['id'] }}" {{ $i === 0 ? 'checked' : '' }}>
                                                    Id={{ $row['id'] }} (v{{ $row['versionNumber'] }}.{{ $row['revisionNumber'] }}, {{ $row['referenceCount'] }} refs)
                                                </label>
                                            @endforeach
                                        </div>

                                        <div class="mb-3">
                                            <div class="font-semibold text-sm mb-1">definitionEn on the survivor:</div>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" class="dd-defen-mode" name="defen_{{ $loop->index }}" value="survivor" checked>
                                                Keep the survivor's existing definitionEn
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" class="dd-defen-mode" name="defen_{{ $loop->index }}" value="custom">
                                                Write a custom definitionEn:
                                            </label>
                                            <textarea class="dd-defen-text w-full border rounded p-2 mt-1 text-sm" rows="2"
                                                      disabled placeholder="Custom general definition (English)">{{ $group['survivor']['definitionEn'] }}</textarea>
                                        </div>

                                        <div class="mb-3">
                                            <div class="font-semibold text-sm mb-1">definitionPt on the survivor:</div>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" class="dd-defpt-mode" name="defpt_{{ $loop->index }}" value="survivor" checked>
                                                Keep the survivor's existing definitionPt
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" class="dd-defpt-mode" name="defpt_{{ $loop->index }}" value="custom">
                                                Write a custom definitionPt:
                                            </label>
                                            <textarea class="dd-defpt-text w-full border rounded p-2 mt-1 text-sm" rows="2"
                                                      disabled placeholder="Custom general definition (Portuguese)">{{ $group['survivor']['definitionPt'] }}</textarea>
                                        </div>

                                        @if ($hasMismatch)
                                            <label class="flex items-start gap-2 text-sm text-red-700 mb-2">
                                                <input type="checkbox" class="dd-ack-mismatch mt-1">
                                                I understand some referencing properties have a propertyId/GUID disagreement
                                                and want to repoint them to the survivor anyway.
                                            </label>
                                        @endif

                                        <p class="text-sm text-gray-600">
                                            This will repoint <strong>{{ $group['affectedCount'] }}</strong> properties row(s)
                                            and delete <strong>{{ count($group['duplicates']) }}</strong> dictionary row(s).
                                        </p>
                                    </div>

                                    {{-- KEEP SEPARATE panel --}}
                                    <div class="dd-panel dd-panel-keep_separate" style="display:none;">
                                        <p class="text-sm text-gray-600 mb-2">
                                            These are genuinely different properties. Optionally rename one or more so they
                                            are no longer duplicates (new name must be unique). Leave blank to keep as-is.
                                        </p>
                                        @foreach ($actionable as $row)
                                            <div class="flex items-center gap-2 text-sm mb-1">
                                                <span class="w-40">Id={{ $row['id'] }} ({{ $row['nameEn'] }})</span>
                                                <input type="text" class="dd-rename border rounded p-1 flex-1"
                                                       data-id="{{ $row['id'] }}" placeholder="New nameEn (optional)">
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- SKIP panel --}}
                                    <div class="dd-panel dd-panel-skip" style="display:none;">
                                        <p class="text-sm text-gray-600">Leave this group for later. No changes will be made.</p>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3">
                                        <button type="button" class="btn btn-secondary dedupe-apply">Apply this group</button>
                                        <span class="dedupe-status text-sm"></span>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600">
                                        Nothing to do here — this name only has version variants, which are never merged.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        (function () {
            const applyUrl = "{{ route('admin.dedupe.apply') }}";
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            document.querySelectorAll('.dedupe-card').forEach(function (card) {
                // Toggle decision panels
                card.querySelectorAll('.dd-action').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        card.querySelectorAll('.dd-panel').forEach(p => p.style.display = 'none');
                        const panel = card.querySelector('.dd-panel-' + radio.value);
                        if (panel) panel.style.display = '';
                    });
                });

                // Enable/disable custom definition textareas
                function wireDefMode(modeClass, textClass) {
                    card.querySelectorAll('.' + modeClass).forEach(function (radio) {
                        radio.addEventListener('change', function () {
                            const ta = card.querySelector('.' + textClass);
                            if (ta) ta.disabled = (radio.value !== 'custom');
                        });
                    });
                }
                wireDefMode('dd-defen-mode', 'dd-defen-text');
                wireDefMode('dd-defpt-mode', 'dd-defpt-text');

                // When the chosen survivor changes, base the definition editors on THAT
                // survivor's current text — but only while still in "keep survivor" mode
                // (don't clobber a custom definition the admin has started typing).
                const defsEl = card.querySelector('.dd-defs');
                const defs = defsEl ? JSON.parse(defsEl.textContent || '{}') : {};
                card.querySelectorAll('.dd-survivor').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        const d = defs[radio.value];
                        if (!d) return;
                        const enTa = card.querySelector('.dd-defen-text');
                        const ptTa = card.querySelector('.dd-defpt-text');
                        if (enTa && enTa.disabled) enTa.value = d.en || '';
                        if (ptTa && ptTa.disabled) ptTa.value = d.pt || '';
                    });
                });

                const applyBtn = card.querySelector('.dedupe-apply');
                if (!applyBtn) return;

                applyBtn.addEventListener('click', function () {
                    const status = card.querySelector('.dedupe-status');
                    status.textContent = '';
                    status.className = 'dedupe-status text-sm';

                    const action = (card.querySelector('.dd-action:checked') || {}).value;
                    const name = card.querySelector('.dd-name').value;
                    const expectedActionableIds = JSON.parse(card.dataset.actionableIds);

                    const decision = { action, name, expectedActionableIds };

                    if (action === 'merge') {
                        decision.survivorId = parseInt((card.querySelector('.dd-survivor:checked') || {}).value, 10);

                        const defEnMode = (card.querySelector('.dd-defen-mode:checked') || {}).value || 'survivor';
                        const defPtMode = (card.querySelector('.dd-defpt-mode:checked') || {}).value || 'survivor';
                        decision.definitionEn = { mode: defEnMode, value: card.querySelector('.dd-defen-text').value };
                        decision.definitionPt = { mode: defPtMode, value: card.querySelector('.dd-defpt-text').value };

                        const ack = card.querySelector('.dd-ack-mismatch');
                        decision.acknowledgeMismatch = ack ? ack.checked : false;

                        if (card.dataset.hasMismatch === '1' && !decision.acknowledgeMismatch) {
                            status.textContent = 'Please acknowledge the propertyId/GUID mismatch before merging.';
                            status.className = 'dedupe-status text-sm text-red-700';
                            return;
                        }
                    } else if (action === 'keep_separate') {
                        const renames = {};
                        card.querySelectorAll('.dd-rename').forEach(function (input) {
                            if (input.value.trim() !== '') renames[input.dataset.id] = input.value.trim();
                        });
                        decision.renames = renames;
                    }

                    applyBtn.disabled = true;
                    status.textContent = 'Applying…';

                    fetch(applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ decision }),
                    })
                        .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
                        .then(({ ok, body }) => {
                            applyBtn.disabled = false;
                            if (!ok || !body.ok) {
                                status.textContent = '✗ ' + (body.error || 'Apply failed.');
                                status.className = 'dedupe-status text-sm text-red-700';
                                return;
                            }
                            resolveCard(card, body);
                        })
                        .catch(err => {
                            applyBtn.disabled = false;
                            status.textContent = '✗ ' + err;
                            status.className = 'dedupe-status text-sm text-red-700';
                        });
                });
            });

            function resolveCard(card, body) {
                const groupGone = !body.group || !body.group.isActionable;
                const result = body.result || {};
                const bodyEl = card.querySelector('.dedupe-body');
                if (groupGone) {
                    bodyEl.innerHTML =
                        '<div class="p-3 rounded bg-green-100 text-green-800 text-sm">'
                        + '✓ ' + escapeHtml(result.message || 'Resolved.')
                        + (result.backup ? '<br><span class="text-gray-600">Backup: ' + escapeHtml(result.backup) + '</span>' : '')
                        + '</div>';
                } else {
                    // Group still has duplicates (e.g. a partial rename). Keep it simple: show
                    // the outcome and ask for a reload to continue with the refreshed data.
                    const status = card.querySelector('.dedupe-status');
                    status.textContent = '✓ ' + (result.message || 'Done') + ' — reload the page to continue with this group.';
                    status.className = 'dedupe-status text-sm text-green-700';
                }
            }

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            }
        })();
    </script>
</x-app-layout>
