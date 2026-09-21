<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="mb-0">Review &amp; deduplicate reference documents</h1>
                <a href="{{ route('admin.dedupe') }}" class="btn btn-secondary ml-auto">← Dictionary properties</a>
            </div>
            <p class="text-sm text-gray-600 mt-1">
                Two things are shown, and they are not the same thing:
            </p>
            <ul class="text-sm text-gray-600 mt-1 list-disc ml-6">
                <li><strong>Same name</strong> — a true duplicate. Two rows with one <code>rdName</code>
                    and different GUIDs claim the same identifier, and properties end up split
                    across both. These are safe to merge.</li>
                <li><strong>Same title only</strong> — shown for review, <em>not</em> a duplicate by
                    default. A standard's amendments and corrigenda keep the base standard's title
                    (<code>EN 474-1:2006+A6:2019</code> and <code>EN 474-1:2006+A4:2013/AC:2014</code>),
                    so merging these would destroy real information. Confirm before merging.</li>
            </ul>
            <p class="text-sm text-gray-600 mt-2">
                A merge repoints every citation — properties, groups, data templates and construction
                objects — onto the survivor and then deletes the other rows. A JSON backup is written
                to <code>storage/app</code> first, and the whole change runs in one transaction.
            </p>

            @if ($schemaError)
                <div class="mt-6 p-4 rounded bg-red-100 text-red-800">
                    <strong>Schema problem:</strong> {{ $schemaError }}
                </div>
            @elseif (empty($groups))
                <div class="mt-6 p-4 rounded bg-green-100 text-green-800">
                    No reference documents share a name or a title. Nothing to review.
                </div>
            @else
                @php
                    $nameGroups  = collect($groups)->where('kind', 'name');
                    $titleGroups = collect($groups)->where('kind', 'title');
                @endphp
                <p class="mt-4 mb-2 text-sm text-gray-700">
                    <strong>{{ $nameGroups->count() }}</strong> duplicated name(s);
                    <strong>{{ $titleGroups->count() }}</strong> group(s) sharing only a title.
                </p>

                @foreach ($groups as $group)
                    <div class="refdoc-card mt-6 border rounded shadow-sm"
                         data-key="{{ $group['key'] }}"
                         data-guids="{{ json_encode($group['guids']) }}"
                         data-needs-ack="{{ ($group['needsAcknowledgement'] || $group['namesDiffer']) ? '1' : '0' }}">

                        <div class="px-4 py-3 border-b bg-slate-50 flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-base">{{ $group['label'] }}</span>
                            @if ($group['kind'] === 'name')
                                <span class="status-tag status-tag-inactive">same name — duplicate</span>
                            @else
                                <span class="status-tag status-tag-preview">same title only — review</span>
                            @endif
                            <span class="text-sm text-gray-600">
                                {{ count($group['documents']) }} documents · {{ $group['affectedCount'] }} citation(s)
                            </span>
                        </div>

                        <div class="p-4">
                            <table class="w-full text-sm" style="border-collapse:collapse;">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="py-2" style="width:90px;">Survivor</th>
                                        <th class="py-2">Document</th>
                                        <th class="py-2" style="width:150px;">Citations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($group['documents'] as $doc)
                                    <tr class="border-b align-top">
                                        <td class="py-3">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="radio" name="survivor-{{ md5($group['key']) }}"
                                                       class="rd-survivor" value="{{ $doc['guid'] }}"
                                                       @if ($loop->first) checked @endif>
                                                <span class="text-xs text-gray-500">keep</span>
                                            </label>
                                        </td>
                                        <td class="py-3 rd-edit-row" data-guid="{{ $doc['guid'] }}">
                                            <div class="grid gap-2" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                                                <label class="block">
                                                    <span class="block text-xs font-semibold mb-1">rdName</span>
                                                    <input type="text" class="rd-field w-full border rounded px-2 py-1"
                                                           data-field="rdName" data-orig="{{ $doc['rdName'] }}"
                                                           value="{{ $doc['rdName'] }}">
                                                </label>
                                                <label class="block">
                                                    <span class="block text-xs font-semibold mb-1">title</span>
                                                    <input type="text" class="rd-field w-full border rounded px-2 py-1"
                                                           data-field="title" data-orig="{{ $doc['title'] }}"
                                                           value="{{ $doc['title'] }}">
                                                </label>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <code>{{ $doc['guid'] }}</code>
                                                @if ($doc['uri'])
                                                    · <a href="{{ url(parse_url($doc['uri'], PHP_URL_PATH)) }}" class="underline">{{ $doc['uri'] }}</a>
                                                @endif
                                                @if ($doc['status']) · {{ $doc['status'] }} @endif
                                            </div>
                                            @if ($doc['usedBy'])
                                                <details class="mt-2 text-xs text-gray-600">
                                                    <summary class="cursor-pointer">what cites it</summary>
                                                    <ul class="list-disc ml-5 mt-1">
                                                        @foreach ($doc['usedBy'] as $use)
                                                            <li>{{ $use['kind'] }}: {{ $use['label'] }}</li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="py-3 text-xs text-gray-700">
                                            <strong>{{ $doc['usage']['total'] }}</strong> total<br>
                                            {{ $doc['usage']['properties'] }} properties<br>
                                            {{ $doc['usage']['groupofproperties'] }} groups<br>
                                            {{ $doc['usage']['productdatatemplates'] }} templates<br>
                                            {{ $doc['usage']['constructionobjects'] }} objects
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            @if ($group['needsAcknowledgement'] || $group['namesDiffer'])
                                <label class="flex items-start gap-2 mt-4 text-sm bg-amber-50 border-l-4 border-amber-400 p-3">
                                    <input type="checkbox" class="rd-ack mt-1">
                                    <span>
                                        These documents do <strong>not</strong> share a name. I have checked that they
                                        are the same document and not different editions, amendments or corrigenda
                                        of one standard.
                                    </span>
                                </label>
                            @endif

                            <div class="flex flex-wrap items-center gap-4 mt-4">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="action-{{ md5($group['key']) }}" class="rd-action" value="merge" checked>
                                    Merge into the survivor
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="action-{{ md5($group['key']) }}" class="rd-action" value="keep_separate">
                                    Keep separate (save the edits above)
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="action-{{ md5($group['key']) }}" class="rd-action" value="skip">
                                    Skip
                                </label>

                                <button type="button" class="btn btn-secondary rd-apply ml-auto">Apply</button>
                                <span class="rd-status text-sm"></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        (function () {
            const applyUrl = @json(route('admin.dedupeRefDocs.apply'));
            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
                ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

            document.querySelectorAll('.refdoc-card').forEach(function (card) {
                const button = card.querySelector('.rd-apply');
                if (!button) return;

                button.addEventListener('click', function () {
                    const status = card.querySelector('.rd-status');
                    status.textContent = '';
                    status.className = 'rd-status text-sm';

                    const action = (card.querySelector('.rd-action:checked') || {}).value;
                    const decision = {
                        action: action,
                        key: card.dataset.key,
                        expectedGuids: JSON.parse(card.dataset.guids),
                    };

                    if (action === 'merge') {
                        decision.survivorGuid = (card.querySelector('.rd-survivor:checked') || {}).value;
                        const ack = card.querySelector('.rd-ack');
                        decision.acknowledgeDifferentNames = ack ? ack.checked : false;

                        if (card.dataset.needsAck === '1' && !decision.acknowledgeDifferentNames) {
                            status.textContent = 'Confirm these are the same document before merging.';
                            status.className = 'rd-status text-sm text-red-700';
                            return;
                        }
                    } else if (action === 'keep_separate') {
                        const edits = {};
                        card.querySelectorAll('.rd-edit-row').forEach(function (row) {
                            const fields = {};
                            row.querySelectorAll('.rd-field').forEach(function (input) {
                                // Only send what the reviewer actually changed.
                                if (input.value !== input.dataset.orig) fields[input.dataset.field] = input.value.trim();
                            });
                            if (Object.keys(fields).length) edits[row.dataset.guid] = fields;
                        });
                        decision.edits = edits;
                    }

                    button.disabled = true;
                    status.textContent = 'Applying…';

                    fetch(applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ decision: decision }),
                    })
                        .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
                        .then(function (res) {
                            button.disabled = false;
                            if (!res.ok || !res.body.ok) {
                                status.textContent = '✗ ' + (res.body.error || 'Apply failed.');
                                status.className = 'rd-status text-sm text-red-700';
                                return;
                            }
                            status.textContent = '✓ ' + res.body.result.message;
                            status.className = 'rd-status text-sm text-green-700';
                            if (!res.body.group) {
                                card.style.opacity = '0.55';
                                card.querySelectorAll('input, button').forEach(el => el.disabled = true);
                            }
                        })
                        .catch(function (err) {
                            button.disabled = false;
                            status.textContent = '✗ ' + err;
                            status.className = 'rd-status text-sm text-red-700';
                        });
                });
            });
        })();
    </script>
</x-app-layout>
