<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1 class="flex-none inline">{{ $quantity->name }}</h1>
            <h3 class="py-2">Grandeza física / Physical quantity <span class="text-sm text-gray-500">(EN ISO 23387 · QuantityKindType)</span></h3>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{ $quantity->guid }}</td></tr>
                    <tr><th>URI</th><td>
                        <a href="{{ \App\Services\UnitsReference::quantityKindUri($quantity->name) }}" target="_blank">{{ \App\Services\UnitsReference::quantityKindUri($quantity->name) }}</a>
                    </td></tr>
                    <tr><th>Nome / Name</th><td>{{ $quantity->name }}</td></tr>
                    <tr><th>Idioma / Language</th><td>{{ $quantity->languageIsoCode }}</td></tr>
                    <tr><th>Dimensão / Dimension</th><td>
                        @if ($dimension && $dimension->canonical)
                            <a href="{{ \App\Services\UnitsReference::dimensionUri($dimension->canonical) }}">{{ $dimension->canonical }}</a>
                        @else
                            <span class="text-gray-500">— (física adiada / physics deferred)</span>
                        @endif
                    </td></tr>
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

            <h3 class="py-2 mt-4">Unidades / Units ({{ $units->count() }})</h3>
            <ul class="list-disc ml-6">
                @foreach ($units as $u)
                    <li><a href="{{ \App\Services\UnitsReference::unitUri($u->code) }}">{{ $u->code }}</a></li>
                @endforeach
            </ul>
            <p class="text-sm text-gray-500 mt-3">Representação legível por máquina / machine-readable:
                <a href="{{ \App\Services\UnitsReference::quantityKindUri($quantity->name) }}?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
