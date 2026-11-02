<x-app-layout>
    @include('admin.partials._admin-styles')
    <div class="adm" style="background:#f8fafc;">
        <div class="adm-page">

            <div class="adm-head">
                <div>
                    <h1>Status health check</h1>
                    <div class="adm-sub">
                        Compares every status with the rules: the newest published version of a template is
                        <span class="adm-pill is-active">Active</span>, older ones
                        <span class="adm-pill is-inactive">InActive</span>; groups follow their template; a property or
                        construction object is Active when an Active template uses it,
                        <span class="adm-pill is-preview">Preview</span> when only drafts do, otherwise InActive.
                        Nothing changes until you apply.
                    </div>
                </div>
                <div class="adm-actions">
                    <a href="{{ route('admin') }}" class="adm-btn is-ghost">← Admin</a>
                    <a href="{{ route('admin.statusCheck') }}" class="adm-btn is-ghost">Re-check</a>
                </div>
            </div>

            @foreach (['success' => 'is-ok', 'error' => 'is-bad'] as $key => $cls)
                @if (session($key))
                    <div class="adm-banner {{ $cls }}">{{ session($key) }}</div>
                @endif
            @endforeach

            @if ($report['total'] === 0 && !$report['issues'])
                <div class="adm-banner is-ok"><div><b>All statuses are consistent.</b> Nothing to fix.</div></div>
            @endif

            {{-- Status fixes --}}
            @if ($report['total'] > 0)
                <form method="POST" action="{{ route('admin.statusCheck.apply') }}" id="fix-form">
                    @csrf
                    <div class="adm-banner is-warn">
                        <div style="flex:1;">
                            <b>{{ $report['total'] }} status {{ $report['total'] === 1 ? 'fix' : 'fixes' }} proposed.</b>
                            Review them, untick any you disagree with, then apply. A JSON backup of the affected rows is written first.
                        </div>
                        <button type="submit" class="adm-btn" id="apply-btn"><span>Apply selected (<span id="sel-count">0</span>)</span></button>
                    </div>

                    @foreach ($report['byTable'] as $table => $rows)
                        <div class="adm-card">
                            <div class="adm-card-h">
                                <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer;">
                                    <input type="checkbox" class="js-all" data-table="{{ $table }}" checked>
                                    <h2>{{ $tables[$table] }}</h2>
                                </label>
                                <span class="adm-sub">{{ count($rows) }} change{{ count($rows) === 1 ? '' : 's' }}</span>
                                @php $byTo = collect($rows)->countBy('to'); @endphp
                                <span style="margin-left:auto;" class="adm-actions">
                                    @foreach ($byTo as $to => $n)
                                        <span class="adm-pill is-{{ strtolower($to) }}">→ {{ $to }}: {{ $n }}</span>
                                    @endforeach
                                </span>
                            </div>
                            <div class="adm-scroll" style="max-height:420px; overflow-y:auto;">
                                <table class="adm-table">
                                    <thead style="position:sticky; top:0; z-index:1;"><tr><th style="width:36px;"></th><th>Element</th><th>Change</th><th>Why</th></tr></thead>
                                    <tbody>
                                        @foreach ($rows as $c)
                                            <tr>
                                                <td><input type="checkbox" name="keys[]" value="{{ $c['key'] }}" class="js-fix" data-table="{{ $table }}" checked></td>
                                                <td>
                                                    <strong>{{ $c['name'] }}</strong>
                                                    @if ($c['version'])<span class="adm-ver adm-small"> {{ $c['version'] }}</span>@endif
                                                    <div class="adm-small adm-muted">
                                                        {{ $table === 'constructionobjects' ? 'GUID' : 'Id' }} {{ $c['id'] }}
                                                        @if ($c['pdtId']) · <a href="{{ route('pdtsdownload', ['pdtID' => $c['pdtId']]) }}" class="adm-link" target="_blank">open template</a>@endif
                                                    </div>
                                                </td>
                                                <td style="white-space:nowrap;">
                                                    <span class="adm-pill is-{{ strtolower($c['from'] ?? 'neutral') }}">{{ $c['from'] ?? 'none' }}</span>
                                                    →
                                                    <span class="adm-pill is-{{ strtolower($c['to']) }}">{{ $c['to'] }}</span>
                                                </td>
                                                <td class="adm-small">{{ $c['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </form>
            @endif

            {{-- Structural issues --}}
            @if ($report['issues'])
                <div class="adm-card" style="margin-top:20px;">
                    <div class="adm-card-h">
                        <h2>Needs a closer look</h2>
                        <span class="adm-sub">Problems a status change alone cannot fix.</span>
                    </div>
                    <div class="adm-card-b" style="display:grid; gap:12px;">
                        @foreach ($report['issues'] as $issue)
                            <div class="adm-banner {{ $issue['severity'] === 'error' ? 'is-bad' : 'is-warn' }}" style="margin:0; display:block;">
                                <div><b>{{ $issue['title'] }}</b></div>
                                <div class="adm-small" style="margin:4px 0 8px;">{{ $issue['detail'] }}</div>
                                <div class="adm-actions">
                                    @foreach ($issue['rows'] as $r)
                                        @if (!empty($r['pdtId']))
                                            <a class="adm-pill is-neutral" style="text-decoration:none;"
                                               href="{{ !empty($r['draft']) ? route('admin.previews.editor', ['pdt' => $r['pdtId']]) : route('pdtsdownload', ['pdtID' => $r['pdtId']]) }}">{{ $r['label'] }} ↗</a>
                                        @else
                                            <span class="adm-pill is-neutral">{{ $r['label'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('fix-form');
            if (!form) return;
            const count = document.getElementById('sel-count');
            const btn = document.getElementById('apply-btn');
            const fixes = [...form.querySelectorAll('.js-fix')];
            function refresh() {
                const n = fixes.filter(f => f.checked).length;
                count.textContent = n;
                btn.disabled = n === 0;
                form.querySelectorAll('.js-all').forEach(all => {
                    const group = fixes.filter(f => f.dataset.table === all.dataset.table);
                    const on = group.filter(f => f.checked).length;
                    all.checked = on === group.length;
                    all.indeterminate = on > 0 && on < group.length;
                });
            }
            form.querySelectorAll('.js-all').forEach(all => all.addEventListener('change', () => {
                fixes.filter(f => f.dataset.table === all.dataset.table).forEach(f => f.checked = all.checked);
                refresh();
            }));
            fixes.forEach(f => f.addEventListener('change', refresh));
            form.addEventListener('submit', e => {
                const n = fixes.filter(f => f.checked).length;
                if (!confirm('Apply ' + n + ' status change(s)? A backup is written first.')) e.preventDefault();
            });
            refresh();
        })();
    </script>
</x-app-layout>
