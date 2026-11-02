<x-app-layout>
    @include('admin.partials._admin-styles')
    @php
        $isVersion = $diff['isVersionDraft'];
        $base = $diff['base'];
        $gops = collect($diff['gops']);
        $changedGops = $gops->where('state', '!=', 'unchanged');
        $unchangedGops = $gops->where('state', 'unchanged');
        $v = fn($pair) => $pair ? 'v' . $pair[0] . '.' . $pair[1] : '—';
    @endphp
    <div class="adm" style="background:#f8fafc;">
        <div class="adm-page" style="max-width:1100px;" id="review" data-pdt-id="{{ $pdt->Id }}">

            <div class="adm-head">
                <div>
                    <h1>Review &amp; publish</h1>
                    <div class="adm-sub"><strong>{{ $pdt->pdtNameEn }}</strong> / {{ $pdt->pdtNamePt }}
                        · <span class="adm-pill is-draft">{{ $isVersion ? 'New version draft' : 'New template draft' }}</span></div>
                </div>
                <div class="adm-actions">
                    <a href="{{ route('admin.previews.editor', ['pdt' => $pdt->Id]) }}" class="adm-btn is-ghost">← Back to editing</a>
                </div>
            </div>

            {{-- 1. What it will be published as --}}
            <div class="adm-card">
                <div class="adm-card-h"><h2>1 · Version</h2></div>
                <div class="adm-card-b">
                    @if ($isVersion && !$diff['hasChanges'])
                        <div class="adm-banner is-info" style="margin:0;">
                            <div><b>No changes yet.</b> This draft is identical to the live
                                <span class="adm-ver">v{{ $base->versionNumber }}.{{ $base->revisionNumber }}</span>, so there is nothing to publish.
                                Go back to editing, or discard the draft from the editor.</div>
                        </div>
                    @elseif ($isVersion)
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:16px; margin-bottom:14px;">
                            <span class="adm-muted">Live</span> <span class="adm-ver">v{{ $base->versionNumber }}.{{ $base->revisionNumber }}</span>
                            <span class="adm-muted">→ will publish as</span>
                            <span class="adm-ver" id="publish-as" style="font-size:20px; color:var(--ok);">{{ $v($diff['next'][$diff['bump']]) }}</span>
                        </div>
                        <div style="display:grid; gap:8px;">
                            @foreach (['version' => 'New version', 'revision' => 'Revision'] as $kind => $label)
                                @php $allowed = in_array($kind, $diff['allowedBumps'], true); @endphp
                                <label class="adm-card" style="margin:0; padding:10px 12px; display:flex; gap:10px; align-items:flex-start; cursor:{{ $allowed ? 'pointer' : 'not-allowed' }}; opacity:{{ $allowed ? 1 : .55 }};">
                                    <input type="radio" name="bump" value="{{ $kind }}" data-label="{{ $v($diff['next'][$kind]) }}"
                                           {{ $diff['bump'] === $kind ? 'checked' : '' }} {{ $allowed ? '' : 'disabled' }} style="margin-top:3px;">
                                    <span>
                                        <strong>{{ $label }} → {{ $v($diff['next'][$kind]) }}</strong>
                                        @if ($diff['bump'] === $kind)<span class="adm-pill is-neutral" style="margin-left:6px;">required by your changes</span>@endif
                                        <div class="adm-small adm-muted">
                                            @if ($kind === 'version')
                                                For changes of meaning or structure: a renamed template, or groups added/removed.
                                            @elseif ($allowed)
                                                For corrections and refinements that keep the template's meaning and structure.
                                            @else
                                                Not possible: your changes alter the template's name or its set of groups, which requires a new version.
                                            @endif
                                        </div>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($diff['reasons'])
                            <div class="adm-small" style="margin-top:12px;"><strong>Why:</strong>
                                <ul style="margin:4px 0 0 18px; list-style:disc;">
                                    @foreach ($diff['reasons'] as $r)<li>{{ $r }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <div style="font-size:16px;">First publication as <span class="adm-ver" style="color:var(--ok); font-size:20px;">{{ $v($diff['publishAs']) }}</span></div>
                        <div class="adm-muted" style="margin-top:4px;">{{ $diff['counts']['gops'] }} groups · {{ $diff['counts']['properties'] }} properties</div>
                    @endif

                    @if (!empty($diff['counts']['emptyGops']))
                        <div class="adm-banner is-warn" style="margin:14px 0 0;">
                            <div><b>{{ count($diff['counts']['emptyGops']) }} group(s) have no properties:</b> {{ implode(', ', $diff['counts']['emptyGops']) }}.
                                They will be published empty — add properties or remove them first if that is not intended.</div>
                        </div>
                    @endif
                    @if ($diff['counts']['gops'] === 0)
                        <div class="adm-banner is-warn" style="margin:14px 0 0;"><div><b>This template has no groups of properties.</b></div></div>
                    @endif
                </div>
            </div>

            {{-- 2. Changes --}}
            @if ($isVersion && $diff['hasChanges'])
                <div class="adm-card">
                    <div class="adm-card-h">
                        <h2>2 · Changes compared to the live version</h2>
                        <span class="adm-sub">{{ $changedGops->count() }} group(s) changed · {{ $unchangedGops->count() }} unchanged</span>
                    </div>
                    <div class="adm-card-b" style="display:grid; gap:12px;">
                        @if ($diff['pdtChanges'])
                            <div>
                                <h3>Template attributes</h3>
                                @include('admin.partials._diff-fields', ['changes' => $diff['pdtChanges'], 'labels' => $labels['productdatatemplates']])
                            </div>
                        @endif

                        @foreach ($changedGops as $g)
                            <details class="adm-card" style="margin:0;" {{ $changedGops->count() <= 4 ? 'open' : '' }}>
                                <summary style="padding:10px 14px; cursor:pointer; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                                    <span class="adm-pill is-{{ $g['state'] }}">{{ ucfirst($g['state']) }}</span>
                                    <strong>{{ $g['nameEn'] }}</strong> <span class="adm-muted">/ {{ $g['namePt'] }}</span>
                                    <span class="adm-small adm-muted" style="margin-left:auto;">
                                        @if ($g['state'] === 'modified') {{ $v($g['from']) }} → <span class="adm-ver">{{ $v($g['to']) }}</span> ({{ $g['bump'] }})
                                        @elseif ($g['state'] === 'added') new group
                                        @else dropped from the new version @endif
                                    </span>
                                </summary>
                                <div style="padding:0 14px 12px;">
                                    @if ($g['reasons'])<div class="adm-small adm-muted">{{ ucfirst(implode(', ', $g['reasons'])) }}</div>@endif
                                    @include('admin.partials._diff-fields', ['changes' => $g['changes'], 'labels' => $labels['groupofproperties']])

                                    @if ($g['propsAdded'])
                                        <div style="margin-top:8px;"><span class="adm-pill is-added">+ {{ count($g['propsAdded']) }} added</span>
                                            <span class="adm-small">{{ collect($g['propsAdded'])->pluck('nameEn')->implode(', ') }}</span></div>
                                    @endif
                                    @if ($g['propsRemoved'])
                                        <div style="margin-top:8px;"><span class="adm-pill is-removed">− {{ count($g['propsRemoved']) }} removed</span>
                                            <span class="adm-small">{{ collect($g['propsRemoved'])->pluck('nameEn')->implode(', ') }}</span></div>
                                    @endif
                                    @foreach ($g['propsChanged'] as $p)
                                        <div style="margin-top:10px; border-left:3px solid #bfdbfe; padding-left:10px;">
                                            <span class="adm-pill is-changed">changed</span> <strong>{{ $p['nameEn'] }}</strong>
                                            @if ($p['versionSwitch'])
                                                <span class="adm-small adm-muted">· definition v{{ $p['versionSwitch']['from'] }} →
                                                    {{ $p['versionSwitch']['draftStatus'] === 'Preview' ? 'edited in this draft' : 'v' . $p['versionSwitch']['to'] }}</span>
                                            @endif
                                            @if ($p['usage'])
                                                <div class="adm-small adm-muted" style="margin-top:4px;">How this template uses it:</div>
                                                @include('admin.partials._diff-fields', ['changes' => $p['usage'], 'labels' => $labels['properties']])
                                            @endif
                                            @if ($p['definition'])
                                                <div class="adm-small adm-muted" style="margin-top:4px;">Dictionary definition:</div>
                                                @include('admin.partials._diff-fields', ['changes' => $p['definition'], 'labels' => $labels['propertiesdatadictionaries']])
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach

                        @if ($unchangedGops->isNotEmpty())
                            <div class="adm-small adm-muted">Unchanged (carried forward as they are): {{ $unchangedGops->pluck('nameEn')->implode(', ') }}</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- 3. Edited shared definitions --}}
            @if ($plan['divergences'])
                <div class="adm-card">
                    <div class="adm-card-h">
                        <h2>{{ $isVersion ? '3' : '2' }} · Edited dictionary definitions</h2>
                        <span class="adm-sub">You edited definitions that are also live in the dictionary. Decide for each one.</span>
                    </div>
                    <div class="adm-card-b" style="display:grid; gap:10px;">
                        @foreach ($plan['divergences'] as $d)
                            <div class="adm-card js-divergence" style="margin:0; padding:12px;" data-context-id="{{ $d['contextId'] }}">
                                <strong>{{ $d['nameEn'] }}</strong>
                                <div class="adm-small" style="margin:6px 0;">
                                    <div><span class="adm-muted">Live:</span> <span class="adm-diff-old">{{ \Illuminate\Support\Str::limit($d['old']['definitionEn'] ?? '', 300) ?: '(empty)' }}</span></div>
                                    <div style="margin-top:3px;"><span class="adm-muted">Yours:</span> <span class="adm-diff-new">{{ \Illuminate\Support\Str::limit($d['new']['definitionEn'] ?? '', 300) ?: '(empty)' }}</span></div>
                                </div>
                                <label style="display:block;"><input type="radio" name="dv_{{ $d['contextId'] }}" value="version" checked> Publish my edit as a new version of this property</label>
                                <label style="display:block;"><input type="radio" name="dv_{{ $d['contextId'] }}" value="keep"> Discard my edit — keep the live definition</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- What happens + publish --}}
            @if ($plan['canPublish'])
                <div class="adm-card">
                    <div class="adm-card-h"><h2>On publish</h2></div>
                    <div class="adm-card-b">
                        <ul style="margin:0 0 0 18px; list-style:disc; line-height:1.7;">
                            @if ($isVersion)
                                <li>This draft becomes the live version (<span id="publish-as-2" class="adm-ver">{{ $v($diff['next'][$diff['bump']]) }}</span>) on the public site, in exports and in the API.</li>
                                <li>The current <span class="adm-ver">v{{ $base->versionNumber }}.{{ $base->revisionNumber }}</span> becomes <span class="adm-pill is-inactive">InActive</span> and stays available as read-only history.</li>
                                @if ($changedGops->where('state', 'modified')->isNotEmpty())
                                    <li>Changed groups get their next version/revision; unchanged groups keep theirs.</li>
                                @endif
                            @else
                                <li>The template goes live on the public site, in exports and in the API.</li>
                            @endif
                            <li>Statuses are updated automatically: properties used by the new version become <span class="adm-pill is-active">Active</span>,
                                properties no Active template uses any more become <span class="adm-pill is-inactive">InActive</span>,
                                and a new construction object is published with the template.</li>
                            <li>A backup of the rows involved is written to <span class="adm-mono">storage/app</span> first.</li>
                        </ul>
                    </div>
                    <div class="adm-card-b" style="border-top:1px solid var(--line); display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                        <button type="button" class="adm-btn is-go" id="btn-publish">Publish {{ $isVersion ? 'as ' : '' }}<span id="publish-as-3">{{ $isVersion ? $v($diff['next'][$diff['bump']]) : '' }}</span></button>
                        <a href="{{ route('admin.previews.editor', ['pdt' => $pdt->Id]) }}" class="adm-btn is-ghost">Keep editing</a>
                        <span id="publish-status" class="adm-small"></span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('btn-publish');
            if (!btn) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const status = document.getElementById('publish-status');
            const labels = ['publish-as', 'publish-as-2', 'publish-as-3'].map(id => document.getElementById(id)).filter(Boolean);

            document.querySelectorAll('input[name="bump"]').forEach(r => r.addEventListener('change', () => {
                labels.forEach(el => el.textContent = r.dataset.label);
            }));

            btn.addEventListener('click', function () {
                const bump = document.querySelector('input[name="bump"]:checked');
                const label = bump ? bump.dataset.label : '';
                if (!confirm('Publish this draft' + (label ? ' as ' + label : '') + '? It goes live immediately.')) return;

                const decisions = {};
                document.querySelectorAll('.js-divergence').forEach(d => {
                    const sel = d.querySelector('input[type=radio]:checked');
                    decisions[d.dataset.contextId] = sel ? sel.value : 'keep';
                });

                btn.disabled = true;
                status.textContent = 'Publishing…';
                status.style.color = '';
                fetch("{{ route('admin.previews.publishApply', ['pdt' => $pdt->Id]) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ decisions, bump: bump ? bump.value : null }),
                }).then(r => r.json().then(j => ({ ok: r.ok, body: j }))).then(({ ok, body }) => {
                    if (ok && body.ok) {
                        status.textContent = '✓ Published' + (body.version ? ' as v' + body.version.join('.') : '') + '. Opening it…';
                        status.style.color = 'var(--ok)';
                        window.location = body.redirect;
                    } else {
                        status.textContent = '✗ ' + (body.error || 'Publishing failed — nothing was changed.');
                        status.style.color = 'var(--bad)';
                        btn.disabled = false;
                    }
                }).catch(e => { status.textContent = '✗ ' + e; status.style.color = 'var(--bad)'; btn.disabled = false; });
            });
        })();
    </script>
</x-app-layout>
