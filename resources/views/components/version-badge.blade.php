@props(['version' => 0, 'revision' => 0])
{{-- Renders the lineage label "V{version}.{revision}" (e.g. V2.1). --}}
<span {{ $attributes->merge(['class' => 'flex-none inline']) }}>V{{ $version }}.{{ $revision }}</span>
