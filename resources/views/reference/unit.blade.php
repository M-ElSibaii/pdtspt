<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="flex-none inline">
                <h1 class="flex-none inline">{{ $unit->code }}</h1>
                <p class="flex-none inline"> — {{ $unit->name }}</p>
            </div>
            <h3 class="py-2">Unidade / Unit <span class="text-sm text-gray-500">(EN ISO 23387 · UnitType)</span></h3>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{ $unit->guid }}</td></tr>
                    <tr><th>URI</th><td>
                        <a href="{{ \App\Services\UnitsReference::unitUri($unit->code) }}" target="_blank">{{ \App\Services\UnitsReference::unitUri($unit->code) }}</a>
                    </td></tr>
                    <tr><th>Código / Code</th><td>{{ $unit->code }}</td></tr>
                    <tr><th>Nome / Name</th><td>{{ $unit->name }}</td></tr>
                    <tr><th>Grandeza física / Physical quantity</th><td>
                        @if ($quantity)
                            <a href="{{ \App\Services\UnitsReference::quantityKindUri($quantity->name) }}">{{ $quantity->name }}</a>
                            <span class="text-gray-500">({{ $quantity->languageIsoCode }})</span>
                        @else
                            <span class="text-gray-500">without</span>
                        @endif
                    </td></tr>
                    <tr><th>Dimensão / Dimension</th><td>
                        @if ($dimension && $dimension->canonical)
                            <a href="{{ \App\Services\UnitsReference::dimensionUri($dimension->canonical) }}">{{ $dimension->canonical }}</a>
                        @else
                            <span class="text-gray-500">— (física adiada / physics deferred)</span>
                        @endif
                    </td></tr>
                    @if ($unit->scale || $unit->base || $unit->coefficient !== null || $unit->offset !== null)
                        <tr><th>Scale</th><td>{{ $unit->scale }}</td></tr>
                        <tr><th>Base</th><td>{{ $unit->base }}</td></tr>
                        <tr><th>Coefficient</th><td>{{ $unit->coefficient }}</td></tr>
                        <tr><th>Offset</th><td>{{ $unit->offset }}</td></tr>
                    @endif
                    <tr><th>Autoridade externa / External authority</th><td>
                        @if ($authority['reference'])
                            <a href="{{ $authority['reference'] }}" target="_blank" rel="external noopener">{{ $authority['reference'] }}</a>
                            <span class="text-gray-500">(QUDT)</span>
                        @else
                            <span class="text-gray-500">QUDT: não mapeado / not yet mapped —</span>
                            <a href="{{ $authority['search'] }}" target="_blank" rel="external noopener">pesquisar / search QUDT</a>
                        @endif
                    </td></tr>
                </tbody>
            </table>
            <p class="text-sm text-gray-500 mt-3">Representação legível por máquina / machine-readable:
                <a href="{{ \App\Services\UnitsReference::unitUri($unit->code) }}?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
