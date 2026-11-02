<x-app-layout>
    @include('admin.partials._admin-styles')
    <div class="adm" style="background:#f8fafc;">
        <div class="adm-page">

            <div class="adm-head">
                <div>
                    <h1>Drafts</h1>
                    <div class="adm-sub">Templates being prepared offline — new templates, and new versions of published ones.
                        Nothing here is visible on the public site until it is published from its review page.</div>
                </div>
                <div class="adm-actions">
                    <a href="{{ route('admin') }}" class="adm-btn is-ghost">← Admin</a>
                    <a href="{{ route('admin.pdt.create') }}" class="adm-btn">+ Create a new template</a>
                </div>
            </div>

            @foreach (['success' => 'is-ok', 'error' => 'is-bad'] as $key => $cls)
                @if (session($key))
                    <div class="adm-banner {{ $cls }}">{{ session($key) }}</div>
                @endif
            @endforeach

            <div class="adm-card">
                @if ($drafts->isEmpty())
                    <div class="adm-empty">No drafts right now. To change a published template, use <strong>New version</strong> on it from the admin page.</div>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead><tr><th>Template</th><th>Kind</th><th>Content</th><th>Last edited</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($drafts as $d)
                                    <tr>
                                        <td><strong>{{ $d->pdtNameEn }}</strong><div class="adm-small adm-muted">{{ $d->pdtNamePt }}</div></td>
                                        <td>
                                            @if ($d->base)
                                                <span class="adm-pill is-draft">New version</span>
                                                <div class="adm-small adm-muted">of live <span class="adm-ver">v{{ $d->base->versionNumber }}.{{ $d->base->revisionNumber }}</span></div>
                                            @else
                                                <span class="adm-pill is-draft">New template</span>
                                            @endif
                                        </td>
                                        <td class="adm-small">{{ $d->gopCount }} groups · {{ $d->propCount }} properties</td>
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

            {{-- Quick create without a construction object (the main path is "Create a new template") --}}
            <details class="adm-card" style="margin-top:16px;" {{ old('pdt') ? 'open' : '' }}>
                <summary class="adm-card-h" style="cursor:pointer; border-bottom:0;">
                    <h2>Quick-create a blank template draft</h2>
                    <span class="adm-sub">Template attributes only — choose the construction object later in the editor.</span>
                </summary>
                <form method="POST" action="{{ route('admin.previews.create') }}" class="adm-card-b" id="create-draft-form" style="border-top:1px solid var(--line);">
                    @csrf
                    @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => old('pdt', []), 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                    <div class="mt-3 flex items-center gap-3">
                        <button type="submit" class="adm-btn">Create draft &amp; open editor</button>
                        <span id="create-draft-msg" class="adm-small"></span>
                    </div>
                </form>
            </details>
            <script>
                // Block submit on missing mandatory fields, opening the expander if needed.
                document.getElementById('create-draft-form').addEventListener('submit', function (e) {
                    const miss = [];
                    this.querySelectorAll('.js-mandatory').forEach(el => { if (!el.disabled && (el.value || '').trim() === '') miss.push(el.dataset.field); });
                    if (miss.length) {
                        e.preventDefault();
                        this.querySelectorAll('details').forEach(d => { if (d.querySelector('.js-mandatory')) d.open = true; });
                        const msg = document.getElementById('create-draft-msg');
                        msg.textContent = 'Missing mandatory: ' + miss.join(', ');
                        msg.style.color = 'var(--bad)';
                    }
                });
            </script>
        </div>
    </div>

    @include('admin.partials._refdoc-modal')
</x-app-layout>
