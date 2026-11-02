<x-app-layout>
    <div class="refpg">
        <div class="container sm:max-w-full py-9">
            @include('reference._head', ['kind' => Lg::pick('Unidade', 'Unit')])

            <div class="mt-4">
                <h1 class="flex-none inline">{{ $unit->code }}</h1>
                <p class="flex-none inline"> — {{ $unit->name }}</p>
            </div>
            <div class="ref-kind">{{ Lg::pick('Unidade', 'Unit') }} <small>(EN ISO 23387 · UnitType)</small></div>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">URI</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">
                        <a class="ref-link" href="{{ Uri::link('unit', $unit->code) }}">{{ rawurldecode(\App\Services\UnitsReference::unitUri($unit->code)) }}</a>
                    </td></tr>
                    <tr><th>GUID</th><td>{{ $unit->guid }}</td></tr>
                    <tr><th>{{ Lg::pick('Símbolo', 'Symbol') }}</th><td>{{ $unit->code }}</td></tr>
                    <tr><th>{{ Lg::pick('Nome', 'Name') }}</th><td>{{ $unit->name }}</td></tr>
                    <tr><th>{{ Lg::pick('Grandeza física', 'Physical quantity') }}</th><td>
                        @if ($quantity)
                            <a class="ref-link" href="{{ Uri::link('pq', $quantity->name) }}">{{ Lg::pick($quantity->namePt ?: $quantity->name, $quantity->name) }}</a>
                            @if (Lg::isPt() && $quantity->namePt)<span class="ref-muted">({{ $quantity->name }})</span>@endif
                        @else
                            <span class="ref-muted">{{ Lg::pick('não classificada', 'not classified') }}</span>
                        @endif
                    </td></tr>
                    <tr><th>{{ Lg::pick('Dimensão', 'Dimension') }}</th><td>
                        @if ($dimension && $dimension->canonical)
                            <a class="ref-link ref-dim" href="{{ Uri::link('dim', $dimension->canonical) }}">{{ \App\Services\UnitsReference::prettyDimension($dimension->canonical) }}</a>
                            <span class="ref-muted ref-dim">({{ $dimension->canonical }})</span>
                        @else
                            <span class="ref-muted">{{ Lg::pick('sem dimensão física', 'no physical dimension') }}</span>
                        @endif
                    </td></tr>
                    @if ($unit->scale || $unit->base || $unit->coefficient !== null || $unit->offset !== null)
                        <tr><th>Scale</th><td>{{ $unit->scale }}</td></tr>
                        <tr><th>Base</th><td>{{ $unit->base }}</td></tr>
                        <tr><th>Coefficient</th><td>{{ $unit->coefficient }}</td></tr>
                        <tr><th>Offset</th><td>{{ $unit->offset }}</td></tr>
                    @endif
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

            @if ($quantity && $siblings->isNotEmpty())
                <h3 class="ref-sec">{{ Lg::pick('Outras unidades de', 'Other units of') }} {{ Lg::pick($quantity->namePt ?: $quantity->name, $quantity->name) }} ({{ $siblings->count() }})</h3>
                <ul class="ref-chips">
                    @foreach ($siblings as $u)
                        <li><a class="ref-link" href="{{ Uri::link('unit', $u->code) }}" title="{{ $u->name }}">{{ $u->code }}</a></li>
                    @endforeach
                </ul>
            @endif

            @include('reference._properties')

            <p class="text-sm ref-muted mt-4">{{ Lg::pick('Representação legível por máquina:', 'Machine-readable:') }}
                <a class="ref-link" href="?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
