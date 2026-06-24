<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Preview drafts</h1>
            <p class="text-sm text-gray-600 mt-1">
                Draft Product Data Templates with status <strong>Preview</strong>. Drafts are
                free-editing: every change is saved in place, with no versioning or deprecation
                cascade. When a draft is ready, <strong>publish</strong> it to make it Active.
            </p>

            @if (session('error'))
                <div class="mt-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
            @endif

            {{-- Create a new draft — full PDT attributes (category is a dropdown; mandatory * enforced) --}}
            <form method="POST" action="{{ route('admin.previews.create') }}" class="mt-6 border rounded p-4 bg-slate-50" id="create-draft-form">
                @csrf
                <div class="font-semibold mb-2">Create a new draft</div>
                @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => old('pdt', []), 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                <div class="mt-3 flex items-center gap-3">
                    <button type="submit" class="btn btn-secondary">Create draft &amp; open editor</button>
                    <span id="create-draft-msg" class="text-sm"></span>
                </div>
            </form>
            <script>
                // Block submit on missing mandatory fields, opening the expander if needed.
                document.getElementById('create-draft-form').addEventListener('submit', function (e) {
                    const miss = [];
                    this.querySelectorAll('.js-mandatory').forEach(el => { if (!el.disabled && (el.value || '').trim() === '') miss.push(el.dataset.field); });
                    if (miss.length) {
                        e.preventDefault();
                        this.querySelectorAll('details').forEach(d => { if (d.querySelector('.js-mandatory')) d.open = true; });
                        document.getElementById('create-draft-msg').textContent = 'Missing mandatory: ' + miss.join(', ');
                        document.getElementById('create-draft-msg').className = 'text-sm text-red-700';
                    }
                });
            </script>

            {{-- Existing drafts --}}
            <div class="mt-6">
                @if ($drafts->isEmpty())
                    <p class="text-sm text-gray-600">No Preview drafts right now.</p>
                @else
                    <table class="text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Id</th>
                                <th class="text-left">Name (PT)</th>
                                <th class="text-left">Name (EN)</th>
                                <th class="text-left">Version</th>
                                <th class="text-left">Status</th>
                                <th class="text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drafts as $d)
                                <tr>
                                    <td class="p-1.5">{{ $d->Id }}</td>
                                    <td class="p-1.5">{{ $d->pdtNamePt }}</td>
                                    <td class="p-1.5">{{ $d->pdtNameEn }}</td>
                                    <td class="p-1.5"><x-version-badge :version="$d->versionNumber" :revision="$d->revisionNumber" /></td>
                                    <td class="p-1.5"><x-status-badge :status="$d->status" /></td>
                                    <td class="p-1.5">
                                        <a href="{{ route('admin.previews.editor', ['pdt' => $d->Id]) }}" class="btn btn-secondary">Open editor</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="mt-6">
                <a href="{{ route('admin') }}" class="btn btn-secondary">← Back to admin</a>
            </div>
        </div>
    </div>

    @include('admin.partials._refdoc-modal')
</x-app-layout>
