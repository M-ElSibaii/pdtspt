{{-- Reworked property picker for one GOP. Expects $gopId and $dictFields. --}}
<div class="js-picker border rounded p-3 bg-white mt-3" data-gop-id="{{ $gopId }}" data-gop-name="{{ $gopNameEn }}">
    <div class="font-semibold text-sm mb-2">Add properties to this group</div>

    {{-- Search Active dictionary (with definitions) --}}
    <div class="flex items-center gap-2">
        <input type="text" class="js-pick-q border rounded p-2 text-sm flex-1" placeholder="Search Active dictionary by name…">
        <button type="button" class="btn btn-secondary js-pick-search">Search</button>
    </div>

    {{-- Excel upload -> exact-nameEn match -> auto-select + gap list --}}
    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="font-semibold">or upload names:</span>
        <input type="file" class="js-pick-file" accept=".xlsx,.xls,.csv">
        <button type="button" class="btn btn-secondary js-pick-upload">Upload &amp; match</button>
        <span class="js-pick-counts text-gray-600"></span>
        <button type="button" class="btn btn-secondary js-pick-gap" style="display:none;">Download missing (Excel)</button>
    </div>

    {{-- On-screen "not found" list from the last Excel match (item 2) --}}
    <div class="js-pick-notfound text-sm mt-2" style="display:none;">
        <div class="font-semibold text-red-700">Not found in dictionary (need creating):</div>
        <ul class="js-pick-notfound-list list-disc ml-5"></ul>
    </div>

    <div class="js-pick-results text-sm mt-2" style="max-height:260px; overflow:auto;"></div>

    <div class="mt-2 flex items-center gap-3">
        <button type="button" class="btn btn-secondary js-pick-add" disabled>Add selected</button>
        <span class="js-pick-status text-sm"></span>
    </div>

    {{-- Create a brand-new dictionary property with full mandatory attributes --}}
    <details class="mt-3">
        <summary class="cursor-pointer text-xs font-semibold">Property not in the dictionary? Create a NEW one (full attributes)</summary>
        <div class="js-newprop mt-2">
            @include('admin.partials._attr-fields', ['fields' => $dictFields, 'values' => [], 'prefix' => 'newp' . $gopId, 'idAttr' => 'newp' . $gopId, 'enums' => $dictEnums])
        </div>
        <div class="mt-2 flex items-center gap-3">
            <button type="button" class="btn btn-secondary js-newprop-add">Create &amp; add</button>
            <span class="js-newprop-status text-sm"></span>
        </div>
    </details>
</div>
