<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" id="create-pdt">
            <h1>Create a Product Data Template</h1>
            <p class="text-sm text-gray-600 mt-1">
                A PDT starts from a <strong>Construction Object</strong>. Select an existing one or
                create a new one, then fill the PDT. Both are created as a <strong>Preview</strong>
                draft — you'll add groups &amp; properties next, then publish. Fields marked
                <span class="text-red-600">*</span> are mandatory.
            </p>

            @if (session('createErrors'))
                <div class="mt-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                    <strong>Please fix:</strong>
                    <ul class="list-disc ml-5">@foreach (session('createErrors') as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pdt.create.store') }}" id="create-form">
                @csrf

                {{-- ---------- Step 1: Construction Object ---------- --}}
                <div class="mt-6 border rounded shadow-sm">
                    <div class="px-4 py-2 border-b bg-slate-50 font-semibold">1 · Construction Object</div>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-4 mb-3">
                            <label class="flex items-center gap-1"><input type="radio" name="co_mode" value="existing" class="js-co-mode" checked> Use existing</label>
                            <label class="flex items-center gap-1"><input type="radio" name="co_mode" value="new" class="js-co-mode"> Create new</label>
                        </div>

                        <div class="js-co-existing">
                            <label class="block text-xs font-semibold mb-1">Construction object <span class="text-red-600">*</span></label>
                            <select name="constructionObjectGUID" class="w-full border rounded p-2 text-sm">
                                <option value="">— select —</option>
                                @foreach ($existingCos as $co)
                                    <option value="{{ $co->GUID }}">{{ $co->constructionObjectNamePt }} / {{ $co->constructionObjectNameEn }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="js-co-new" style="display:none;">
                            @include('admin.partials._attr-fields', ['fields' => $coFields, 'values' => old('co', []), 'prefix' => 'co', 'idAttr' => 'co'])
                        </div>
                    </div>
                </div>

                {{-- ---------- Step 2: PDT ---------- --}}
                <div class="mt-4 border rounded shadow-sm">
                    <div class="px-4 py-2 border-b bg-slate-50 font-semibold">2 · Product Data Template</div>
                    <div class="p-4 js-pdt-block">
                        @include('admin.partials._attr-fields', ['fields' => $pdtFields, 'values' => old('pdt', []), 'prefix' => 'pdt', 'idAttr' => 'pdt'])
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="btn btn-secondary" id="create-submit">Create draft &amp; continue</button>
                    <a href="{{ route('admin.previews') }}" class="btn btn-secondary">Cancel</a>
                    <span id="create-msg" class="text-sm text-red-700"></span>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('create-pdt');
            const existing = root.querySelector('.js-co-existing');
            const isNew = root.querySelector('.js-co-new');

            function setDisabled(scope, disabled) {
                scope.querySelectorAll('input,select,textarea').forEach(el => { el.disabled = disabled; });
            }
            function syncMode() {
                const mode = root.querySelector('.js-co-mode:checked').value;
                existing.style.display = mode === 'existing' ? '' : 'none';
                isNew.style.display = mode === 'new' ? '' : 'none';
                // Disabled inputs don't submit and don't trigger required-validation.
                setDisabled(existing, mode !== 'existing');
                setDisabled(isNew, mode !== 'new');
            }
            root.querySelectorAll('.js-co-mode').forEach(r => r.addEventListener('change', syncMode));
            syncMode();

            // Inline mandatory blocking: collect empty visible js-mandatory + the CO selection.
            document.getElementById('create-form').addEventListener('submit', function (e) {
                const msg = document.getElementById('create-msg');
                const missing = [];
                root.querySelectorAll('.js-mandatory').forEach(el => {
                    if (!el.disabled && el.offsetParent !== null && (el.value || '').trim() === '') {
                        const label = el.closest('div')?.querySelector('label')?.textContent.trim() || el.name;
                        missing.push(label.replace(/\s*\*.*/, ''));
                    }
                });
                const mode = root.querySelector('.js-co-mode:checked').value;
                if (mode === 'existing') {
                    const sel = existing.querySelector('select[name="constructionObjectGUID"]');
                    if (sel && !sel.value) missing.push('Construction object');
                }
                if (missing.length) {
                    e.preventDefault();
                    msg.textContent = 'Missing mandatory: ' + [...new Set(missing)].join(', ');
                    // Open any expander that hides a missing field.
                    root.querySelectorAll('details').forEach(d => {
                        if (d.querySelector('.js-mandatory')) d.open = true;
                    });
                }
            });
        })();
    </script>
</x-app-layout>
