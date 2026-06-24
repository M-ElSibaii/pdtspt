{{--
  Schema-driven attribute form for one element.
  Expects:
    $fields  - SchemaAttributeService::describe($table) descriptors
    $values  - associative current values (or [])
    $prefix  - form field-name prefix (inputs are name="{prefix}[{field}]")
    $idAttr  - optional html id prefix (defaults to $prefix)
  System/lineage fields are shown read-only (auto-managed). Mandatory fields are marked *
  and carry the js-mandatory class for inline blocking. Primary (name/description/
  definition) show outside the expander; everything else is collapsed.
--}}
@php
    $values = $values ?? [];
    $idAttr = $idAttr ?? $prefix;
    $enums  = $enums ?? [];   // optional [fieldName => [allowed values]] (e.g. bSDD dataType/units)
    // $editableOverride: null = default (system fields locked, rest editable). An array =
    // ONLY those field names are editable; everything else is shown read-only (Mode 2).
    $editableOverride = $editableOverride ?? null;
    $primary = collect($fields)->filter(fn($f) => $f['primary'] && !$f['system']);
    $rest    = collect($fields)->reject(fn($f) => $f['primary'] && !$f['system']);

    if (!function_exists('_peField')) {
        /** Render one schema field as an input/select/textarea (locked => read-only). */
        function _peField(array $f, string $prefix, string $idAttr, array $values, array $enums = [], ?array $editableOverride = null): string
        {
            $name = $f['name'];
            $val = $values[$name] ?? '';
            $inputName = $prefix . '[' . $name . ']';
            $id = $idAttr . '_' . $name;

            // Locked = system field, or (in override mode) not on the allow-list.
            $locked = $f['system'] || ($editableOverride !== null && !in_array($name, $editableOverride, true));
            if ($locked) {
                $ph = $f['system'] ? 'auto-managed' : 'read-only';
                return '<input class="w-full border rounded p-2 text-sm bg-gray-100 text-gray-500" value="'
                    . e($val) . '" placeholder="' . $ph . '" readonly disabled>';
            }

            $req = $f['mandatory'] ? 'required' : '';
            // js-attr: collectable by the editor's AJAX (data-field); js-mandatory: blocking.
            // data-original: current value, so staged editors (Mode 4) can diff client-side.
            $cls = 'js-attr ' . ($f['mandatory'] ? 'js-mandatory ' : '') . 'w-full border rounded p-2 text-sm';
            $common = 'class="' . $cls . '" id="' . e($id) . '" name="' . e($inputName)
                . '" data-field="' . e($name) . '" data-original="' . e($val) . '" ' . $req;

            // Shared SEARCHABLE lookup for referenceDocumentGUID and constructionObjectGUID.
            // A text input (type-to-filter via datalist) shows the label; a hidden .js-attr
            // carries the GUID. min-width:0 keeps the flex row from overflowing the screen.
            // Ref-doc gets an inline "+ new"; construction object is select-only.
            if ($name === 'referenceDocumentGUID' || $name === 'constructionObjectGUID') {
                $isRef = $name === 'referenceDocumentGUID';
                $opts = $isRef ? \App\Services\RefDocs::all() : \App\Services\ConObjs::all();
                $listId = 'dl_' . $id;
                $curLabel = '';
                foreach ($opts as $o) {
                    if ((string) $o->GUID === (string) $val) { $curLabel = $o->label; break; }
                }
                if ($curLabel === '' && $val !== '' && $val !== 'n/a') { $curLabel = $val; }
                $dl = '<datalist id="' . e($listId) . '">';
                foreach ($opts as $o) {
                    $dl .= '<option data-guid="' . e($o->GUID) . '" value="' . e($o->label) . '"></option>';
                }
                $dl .= '</datalist>';
                $search = '<input type="text" class="js-lookup-search w-full border rounded p-2 text-sm" list="' . e($listId)
                    . '" data-for="' . e($id) . '" value="' . e($curLabel) . '" placeholder="type to search…" autocomplete="off" style="min-width:0">';
                $hidden = '<input type="hidden" class="js-attr" id="' . e($id) . '" name="' . e($inputName)
                    . '" data-field="' . e($name) . '" data-original="' . e($val) . '" value="' . e($val) . '">';
                $newBtn = $isRef ? '<button type="button" class="btn btn-secondary js-refdoc-new" title="Add a new reference document">+</button>' : '';
                return '<div class="flex items-center gap-1" style="min-width:0">' . $search . $hidden . $newBtn . '</div>' . $dl;
            }

            // Controlled bSDD vocabulary override (independent of the DB column type).
            if (isset($enums[$name])) {
                $opts = $enums[$name];
                if (count($opts) > 20) {
                    // Big list (units ~600): type-to-filter via native datalist.
                    $listId = 'dl_' . $id;
                    $dl = '<datalist id="' . e($listId) . '">';
                    foreach ($opts as $o) {
                        $dl .= '<option value="' . e($o) . '"></option>';
                    }
                    $dl .= '</datalist>';
                    return '<input type="text" list="' . e($listId) . '" ' . $common
                        . ' value="' . e($val) . '" placeholder="type to filter…">' . $dl;
                }
                $html = '<select ' . $common . '>';
                $html .= $f['mandatory'] ? '' : '<option value=""></option>';
                foreach ($opts as $o) {
                    $sel = ((string) $val === (string) $o) ? 'selected' : '';
                    $html .= '<option value="' . e($o) . '" ' . $sel . '>' . e($o) . '</option>';
                }
                return $html . '</select>';
            }

            switch ($f['inputKind']) {
                case 'select':
                    $opts = $f['mandatory'] ? '' : '<option value=""></option>';
                    foreach (($f['enum'] ?? []) as $o) {
                        $sel = ((string) $val === (string) $o) ? 'selected' : '';
                        $opts .= '<option value="' . e($o) . '" ' . $sel . '>' . e($o) . '</option>';
                    }
                    return '<select ' . $common . '>' . $opts . '</select>';
                case 'textarea':
                    return '<textarea ' . $common . ' rows="2">' . e($val) . '</textarea>';
                case 'int':
                    return '<input type="number" ' . $common . ' value="' . e($val) . '">';
                case 'date':
                    return '<input type="date" ' . $common . ' value="' . e($val) . '">';
                default:
                    return '<input type="text" ' . $common . ' value="' . e($val) . '">';
            }
        }
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    @foreach ($primary as $f)
        <div class="{{ $f['inputKind'] === 'textarea' ? 'md:col-span-2' : '' }}">
            <label class="block text-xs font-semibold mb-1">
                {{ $f['label'] }}@if($f['mandatory']) <span class="text-red-600">*</span>@endif
            </label>
            {!! _peField($f, $prefix, $idAttr, $values, $enums, $editableOverride) !!}
        </div>
    @endforeach
</div>

<details class="mt-3">
    <summary class="cursor-pointer text-xs font-semibold text-gray-700">All attributes ({{ $rest->count() }}) — expand</summary>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
        @foreach ($rest as $f)
            <div class="{{ $f['inputKind'] === 'textarea' ? 'md:col-span-2' : '' }}">
                <label class="block text-xs mb-1">
                    {{ $f['label'] }}@if($f['mandatory']) <span class="text-red-600">*</span>@endif
                    @if($f['system'])<span class="text-xs text-gray-400">(auto)</span>@endif
                </label>
                {!! _peField($f, $prefix, $idAttr, $values, $enums, $editableOverride) !!}
            </div>
        @endforeach
    </div>
</details>
