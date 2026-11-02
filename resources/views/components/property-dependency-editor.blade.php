@props([
    'guid',  // source property lineage GUID
    'title' => 'Property dependencies',
])

<div class="js-propdep-editor mt-4 border rounded shadow-sm" data-guid="{{ $guid }}">
    <div class="px-4 py-2 border-b bg-slate-50 font-semibold">{{ $title }}</div>
    <div class="p-4 text-sm">
        <div class="js-propdep-list mb-3 text-gray-700">…</div>

        <div class="border-t pt-3">
            <div class="font-semibold mb-1">Add a dependency</div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <select class="js-propdep-kind border rounded p-1 text-sm">
                    <option value="additive_context">additive context parameters</option>
                    <option value="combinative_context">combinative context parameters</option>
                    <option value="reference_proxy">reference proxy property</option>
                    <option value="alternative_proxy">alternative proxy properties</option>
                    <option value="function">function dependency</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <input type="text" class="js-propdep-q border rounded p-1 text-sm" placeholder="search target property…">
                <button type="button" class="btn btn-secondary js-propdep-search">Search</button>
            </div>
            <div class="js-propdep-results mb-2"></div>
            <div class="font-semibold text-xs mt-2">Chosen targets (ordered):</div>
            <ol class="js-propdep-chosen list-decimal ml-5 mb-2"><li class="text-gray-500 list-none">none</li></ol>
            <div class="js-propdep-fn mb-2" style="display:none;">
                <label class="text-xs font-semibold">Function expression (stored, not executed):</label>
                <textarea class="js-propdep-expr w-full border rounded p-1 text-sm" rows="2" placeholder="e.g. GWP_total = GWP_fossil + GWP_biogenic"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-secondary js-propdep-add">Add dependency</button>
                <span class="js-propdep-status text-xs"></span>
            </div>
        </div>
    </div>
</div>

@once
    @include('components.partials._property-dependency-editor-script')
@endonce
