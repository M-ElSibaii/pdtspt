<x-app-layout>
    <div class="refpg">
        <div class="container sm:max-w-full py-9">
            @include('reference._head', ['kind' => Lg::pick('Grandeza física', 'Physical quantity')])

            <div class="mt-4">
                <h1 class="flex-none inline">{{ Lg::pick($quantity->namePt ?: $quantity->name, $quantity->name) }}</h1>
                @if (Lg::isPt() && $quantity->namePt && $quantity->namePt !== $quantity->name)
                    <p class="flex-none inline"> — {{ $quantity->name }}</p>
                @endif
            </div>
            <div class="ref-kind">{{ Lg::pick('Grandeza física', 'Physical quantity') }} <small>(EN ISO 23387 · QuantityKindType)</small></div>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">URI</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">
                        <a class="ref-link" href="{{ Uri::link('pq', $quantity->name) }}">{{ rawurldecode(\App\Services\UnitsReference::quantityKindUri($quantity->name)) }}</a>
                    </td></tr>
                    <tr><th>GUID</th><td>{{ $quantity->guid }}</td></tr>
                    <tr><th>{{ Lg::pick('Nome', 'Name') }}</th><td>{{ $quantity->name }} <span class="ref-muted">({{ $quantity->languageIsoCode }})</span></td></tr>
                    @if ($quantity->namePt)
                        <tr><th>{{ Lg::pick('Nome em português', 'Portuguese name') }}</th><td>{{ $quantity->namePt }}</td></tr>
                    @endif
                    <tr><th>{{ Lg::pick('Dimensão', 'Dimension') }}</th><td>
                        @if ($dimension && $dimension->canonical)
                            <a class="ref-link ref-dim" href="{{ Uri::link('dim', $dimension->canonical) }}">{{ \App\Services\UnitsReference::prettyDimension($dimension->canonical) }}</a>
                            <span class="ref-muted ref-dim">({{ $dimension->canonical }})</span>
                        @else
                            <span class="ref-muted">{{ Lg::pick('sem dimensão física', 'no physical dimension') }}</span>
                        @endif
                    </td></tr>
                    <tr><th>{{ Lg::pick('Autoridade externa', 'External authority') }}</th><td>
                        @if ($authority['reference'])
                            <a class="ref-link" href="{{ $authority['reference'] }}" target="_blank" rel="external noopener">{{ $authority['reference'] }}</a>
                            <span class="ref-muted">(QUDT)</span>
                        @else
                            <span class="ref-muted">{{ Lg::pick('QUDT: não mapeado —', 'QUDT: not yet mapped —') }}</span>
                            <a class="ref-link" href="{{ $authority['search'] }}" target="_blank" rel="external noopener">{{ Lg::pick('pesquisar QUDT', 'search QUDT') }}</a>
                        @endif
                    </td></tr>
                </tbody>
            </table>

            <h3 class="ref-sec">{{ Lg::pick('Unidades', 'Units') }} ({{ $units->count() }})</h3>
            @if ($units->isEmpty())
                <p class="ref-muted">{{ Lg::pick('Sem unidades: usada por propriedades sem unidade (texto, booleanos…).', 'No units: used by unit-less properties (text, booleans…).') }}</p>
            @else
                <ul class="ref-chips">
                    @foreach ($units as $u)
                        <li><a class="ref-link" href="{{ Uri::link('unit', $u->code) }}">{{ $u->code }}</a> <small>{{ $u->name }}</small></li>
                    @endforeach
                </ul>
            @endif

            @include('reference._properties')

            <p class="text-sm ref-muted mt-4">{{ Lg::pick('Representação legível por máquina:', 'Machine-readable:') }}
                <a class="ref-link" href="?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
