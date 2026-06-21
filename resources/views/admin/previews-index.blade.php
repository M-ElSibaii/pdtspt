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

            {{-- Create a new draft --}}
            <form method="POST" action="{{ route('admin.previews.create') }}" class="mt-6 border rounded p-4 bg-slate-50">
                @csrf
                <div class="font-semibold mb-2">Create a new draft</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Name (EN)</label>
                        <input type="text" name="pdtNameEn" required class="w-full border rounded p-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Name (PT)</label>
                        <input type="text" name="pdtNamePt" required class="w-full border rounded p-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Category (optional)</label>
                        <input type="text" name="category" class="w-full border rounded p-2 text-sm">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-secondary">Create draft &amp; open editor</button>
                </div>
            </form>

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
</x-app-layout>
