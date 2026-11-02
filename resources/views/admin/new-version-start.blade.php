<x-app-layout>
    @include('admin.partials._admin-styles')
    <div class="adm" style="background:#f8fafc;">
        <div class="adm-page" style="max-width:760px;">

            <div class="adm-head">
                <div>
                    <h1>New version of “{{ $current->pdtNameEn }}”</h1>
                    <div class="adm-sub">{{ $current->pdtNamePt }} · live <span class="adm-ver">v{{ $current->versionNumber }}.{{ $current->revisionNumber }}</span>
                        · {{ $counts['gops'] }} groups · {{ $counts['properties'] }} properties</div>
                </div>
            </div>

            @if (session('error'))
                <div class="adm-banner is-bad">{{ session('error') }}</div>
            @endif

            @if ((int) $pdt->Id !== (int) $current->Id)
                <div class="adm-banner is-warn"><div>You opened <span class="adm-ver">v{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</span>,
                    which is not the current version. The new version is always based on the current one,
                    <span class="adm-ver">v{{ $current->versionNumber }}.{{ $current->revisionNumber }}</span>.</div></div>
            @endif

            <div class="adm-card">
                <div class="adm-card-b">
                    <h2 style="margin-bottom:10px;">What happens</h2>
                    <ol style="margin:0 0 0 18px; padding:0; line-height:1.7; list-style:decimal;">
                        <li>A <strong>draft copy</strong> of v{{ $current->versionNumber }}.{{ $current->revisionNumber }} is made. It is offline — the public site keeps showing the live version.</li>
                        <li>Edit the draft as much as you like, over as many sessions as you need. Changes save as you go.</li>
                        <li>When ready, <strong>Review &amp; publish</strong> shows every change against the live version and whether it becomes a new <em>version</em> or a <em>revision</em>.</li>
                        <li>Only when you press <strong>Publish</strong> does the draft go live; the current version is kept as read-only history.</li>
                    </ol>
                    <p class="adm-muted adm-small" style="margin-top:12px;">Changed your mind? A draft can be discarded at any time without affecting the live version.</p>
                </div>
                <div class="adm-card-b" style="border-top:1px solid var(--line); display:flex; gap:8px; flex-wrap:wrap;">
                    <form method="POST" action="{{ route('admin.pdt.newVersion.create', ['pdt' => $current->Id]) }}">
                        @csrf
                        <button type="submit" class="adm-btn">Create draft &amp; start editing</button>
                    </form>
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin') }}" class="adm-btn is-ghost">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
