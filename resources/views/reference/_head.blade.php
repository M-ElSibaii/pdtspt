{{-- Shared header of the units / physical quantities / dimensions pages: way back to the
     index, a search box, and the page's kind. Expects $kind (label) and optionally $standard. --}}
<style>
    .refpg { background: #fff; }
    .refpg .ref-crumbs { font-size: .875rem; color: #6b7280; display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: space-between; }
    .refpg .ref-search { display: flex; gap: .4rem; }
    .refpg .ref-search input { border: 1px solid #d1d5db; border-radius: .375rem; padding: .3rem .6rem; font-size: .875rem; min-width: 14rem; }
    .refpg .ref-search button { border: 1px solid #1f2937; background: #1f2937; color: #fff; border-radius: .375rem; padding: .3rem .8rem; font-size: .8rem; }
    .refpg .ref-kind { font-size: 1.1rem; font-weight: 800; color: #374151; margin: .25rem 0 1rem; }
    .refpg .ref-kind small { font-weight: 400; color: #6b7280; font-size: .8rem; }
    .refpg .ref-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin: .5rem 0 1rem; padding: 0; list-style: none; }
    .refpg .ref-chips li { border: 1px solid #e5e7eb; border-radius: 999px; padding: .15rem .65rem; font-size: .85rem; background: #f9fafb; }
    .refpg .ref-chips li small { color: #6b7280; }
    .refpg h3.ref-sec { font-size: 1.05rem; margin-top: 1.75rem; }
    .refpg .ref-muted { color: #6b7280; }
    .refpg .ref-dim { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .refpg table.ref-list { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .refpg table.ref-list th, .refpg table.ref-list td { border-bottom: 1px solid #eee; padding: .35rem .5rem; text-align: left; vertical-align: top; }
    .refpg table.ref-list th { color: #6b7280; font-weight: 600; font-size: .8rem; }
</style>
<div class="ref-crumbs">
    <span>
        <a class="ref-link" href="{{ route('reference.index') }}">{{ Lg::pick('Unidades, grandezas e dimensões', 'Units, quantities and dimensions') }}</a>
        @isset($kind) &rsaquo; {{ $kind }} @endisset
    </span>
    <form class="ref-search" method="GET" action="{{ route('reference.index') }}" role="search">
        <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="{{ Lg::pick('Procurar unidade ou grandeza…', 'Search a unit or quantity…') }}" aria-label="{{ Lg::pick('Procurar', 'Search') }}">
        <button type="submit">{{ Lg::pick('Procurar', 'Search') }}</button>
    </form>
</div>
