<x-app-layout>
    @include('admin.partials._admin-styles')
    <div class="adm" style="background:#f8fafc;">
        <div class="adm-page">

            <div class="adm-head">
                <div>
                    <h1>Admin</h1>
                    <div class="adm-sub">Templates, drafts, data health and users.</div>
                </div>
                <div class="adm-actions">
                    <a href="{{ route('admin.pdt.create') }}" class="adm-btn">+ Create a new template</a>
                </div>
            </div>

            @foreach (['success' => 'is-ok', 'error' => 'is-bad'] as $key => $cls)
                @if (session($key))
                    <div class="adm-banner {{ $cls }}">{{ session($key) }}</div>
                @endif
            @endforeach

            @php
                $published = $templates->where('status', 'Active')->count();
            @endphp
            <div class="adm-stats">
                <div class="adm-stat"><div class="n">{{ $published }}</div><div class="l">Published templates</div></div>
                <a href="#drafts" class="adm-stat {{ $drafts->isNotEmpty() ? 'is-warn' : '' }}"><div class="n">{{ $drafts->count() }}</div><div class="l">Drafts in progress</div></a>
                <a href="{{ route('admin.statusCheck') }}" class="adm-stat {{ $healthCount ? 'is-warn' : 'is-ok' }}">
                    <div class="n">{{ $healthCount ?: '✓' }}</div>
                    <div class="l">{{ $healthCount ? 'Status issues to review' : 'Statuses consistent' }}</div>
                </a>
                <a href="#users" class="adm-stat"><div class="n">{{ $users->total() }}</div><div class="l">Users</div></a>
            </div>

            {{-- Drafts in progress --}}
            <div class="adm-card" id="drafts">
                <div class="adm-card-h">
                    <h2>Drafts in progress</h2>
                    <span class="adm-sub">Offline until published. Nothing here is visible on the public site.</span>
                    <a href="{{ route('admin.previews') }}" class="adm-link" style="margin-left:auto;">All drafts →</a>
                </div>
                @if ($drafts->isEmpty())
                    <div class="adm-empty">No drafts. Start one with “New version” on a template below, or create a new template.</div>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead><tr><th>Template</th><th>Kind</th><th>Last edited</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($drafts as $d)
                                    <tr>
                                        <td><strong>{{ $d->pdtNameEn }}</strong> <span class="adm-muted">/ {{ $d->pdtNamePt }}</span></td>
                                        <td>
                                            @if ($d->base)
                                                <span class="adm-pill is-draft">New version</span>
                                                <span class="adm-small adm-muted">of live <span class="adm-ver">v{{ $d->base->versionNumber }}.{{ $d->base->revisionNumber }}</span></span>
                                            @else
                                                <span class="adm-pill is-draft">New template</span>
                                            @endif
                                        </td>
                                        <td class="adm-muted">{{ $d->updated_at }}</td>
                                        <td style="text-align:right;">
                                            <div class="adm-actions" style="justify-content:flex-end;">
                                                <a href="{{ route('admin.previews.editor', ['pdt' => $d->Id]) }}" class="adm-btn is-sm">Continue editing</a>
                                                <a href="{{ route('admin.previews.review', ['pdt' => $d->Id]) }}" class="adm-btn is-sm is-ghost">Review &amp; publish</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Templates --}}
            <div class="adm-card" id="templates">
                <div class="adm-card-h">
                    <h2>Templates</h2>
                    <span class="adm-sub">{{ $templates->count() }} total</span>
                    <input type="search" id="tpl-filter" class="adm-input" placeholder="Filter by name…" style="max-width:280px; margin-left:auto;">
                </div>
                <div class="adm-scroll" style="max-height:560px; overflow-y:auto;">
                    <table class="adm-table" id="tpl-table">
                        <thead style="position:sticky; top:0; z-index:1;"><tr><th>Name</th><th>Live version</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($templates as $t)
                                <tr data-name="{{ mb_strtolower($t->pdtNameEn . ' ' . $t->pdtNamePt) }}">
                                    <td>
                                        <strong>{{ $t->pdtNameEn }}</strong> <span class="adm-muted">/ {{ $t->pdtNamePt }}</span>
                                        @if ($t->draft)
                                            <span class="adm-pill is-draft" style="margin-left:6px;">draft in progress</span>
                                        @endif
                                    </td>
                                    <td><span class="adm-ver">v{{ $t->versionNumber }}.{{ $t->revisionNumber }}</span>
                                        @if ($t->versionCount > 1)<span class="adm-small adm-muted"> · {{ $t->versionCount }} versions</span>@endif
                                    </td>
                                    <td><span class="adm-pill is-{{ strtolower($t->status) }}">{{ $t->status === 'Preview' ? 'Draft (never published)' : $t->status }}</span></td>
                                    <td style="text-align:right;">
                                        <div class="adm-actions" style="justify-content:flex-end;">
                                            <a href="{{ route('pdtsdownload', ['pdtID' => $t->Id]) }}" class="adm-btn is-sm is-ghost">View</a>
                                            @if ($t->status === 'Preview')
                                                <a href="{{ route('admin.previews.editor', ['pdt' => $t->Id]) }}" class="adm-btn is-sm">Edit draft</a>
                                            @elseif ($t->draft)
                                                <a href="{{ route('admin.previews.editor', ['pdt' => $t->draft->Id]) }}" class="adm-btn is-sm">Continue draft</a>
                                            @else
                                                <a href="{{ route('admin.pdt.newVersion', ['pdt' => $t->Id]) }}" class="adm-btn is-sm">New version</a>
                                            @endif
                                            @if ($t->status === 'Active')
                                                <a href="{{ route('admin.pdt.activeEdit', ['pdt' => $t->Id]) }}" class="adm-btn is-sm is-ghost" title="Small in-place corrections that do not need a new version">Quick fix</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="adm-empty" id="tpl-none" style="display:none;">No template matches.</div>
                </div>
            </div>

            {{-- Tools --}}
            <div class="adm-grid2" style="margin-top:16px;">
                <div class="adm-card" style="margin-top:0;">
                    <div class="adm-card-h"><h2>Data quality</h2></div>
                    <div class="adm-card-b" style="display:grid; gap:10px;">
                        <a href="{{ route('admin.statusCheck') }}" class="adm-btn is-ghost" style="justify-content:space-between;">
                            Status health check
                            @if ($healthCount)<span class="adm-pill is-preview">{{ $healthCount }} to review</span>@else<span class="adm-pill is-active">all good</span>@endif
                        </a>
                        <a href="{{ route('admin.dedupe') }}" class="adm-btn is-ghost">Review &amp; deduplicate dictionary properties</a>
                        <a href="{{ route('admin.dedupeRefDocs') }}" class="adm-btn is-ghost">Review &amp; deduplicate reference documents</a>
                    </div>
                </div>
                <div class="adm-card" style="margin-top:0;">
                    <div class="adm-card-h"><h2>Export &amp; API</h2></div>
                    <div class="adm-card-b" style="display:grid; gap:10px;">
                        <a href="{{ route('exportdomainbsdd') }}" class="adm-btn is-ghost">Export the domain as bSDD JSON</a>
                        <a href="{{ route('admin.api-tester') }}" class="adm-btn is-ghost">API tester (all endpoints)</a>
                    </div>
                </div>
            </div>

            {{-- Users --}}
            <div class="adm-card" id="users" style="margin-top:16px;">
                <div class="adm-card-h"><h2>Users</h2><span class="adm-sub">Admin rights and newsletter subscription.</span></div>
                <form method="POST" action="{{ route('update.users') }}">
                    @csrf
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Profession</th><th>Institute</th><th>Admin</th><th>Subscribed</th></tr></thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->profession }}</td>
                                        <td>{{ $user->institute }}</td>
                                        <td style="white-space:nowrap;">
                                            <label style="margin-right:10px;"><input type="radio" name="isAdmin[{{ $user->id }}]" value="1" {{ $user->isAdmin ? 'checked' : '' }}> Yes</label>
                                            <label><input type="radio" name="isAdmin[{{ $user->id }}]" value="0" {{ !$user->isAdmin ? 'checked' : '' }}> No</label>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <label style="margin-right:10px;"><input type="radio" name="subscribe[{{ $user->id }}]" value="1" {{ $user->subscribe ? 'checked' : '' }}> Yes</label>
                                            <label><input type="radio" name="subscribe[{{ $user->id }}]" value="0" {{ !$user->subscribe ? 'checked' : '' }}> No</label>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="adm-card-b" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; border-top:1px solid var(--line);">
                        <button type="submit" class="adm-btn">Save users</button>
                        <div style="margin-left:auto;">{{ $users->fragment('users')->links() }}</div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('tpl-filter');
            const rows = [...document.querySelectorAll('#tpl-table tbody tr')];
            const none = document.getElementById('tpl-none');
            input.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let shown = 0;
                rows.forEach(r => { const hit = !q || r.dataset.name.includes(q); r.style.display = hit ? '' : 'none'; shown += hit; });
                none.style.display = shown ? 'none' : '';
            });
        })();
    </script>
</x-app-layout>
