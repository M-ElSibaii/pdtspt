<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1 class="flex-none inline">{{ $dimension->canonical }}</h1>
            <h3 class="py-2">{{ Lg::pick('Dimensão', 'Dimension') }} <span class="text-sm text-gray-500">(EN ISO 23387 · DimensionType · ISO 80000)</span></h3>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{ $dimension->guid }}</td></tr>
                    <tr><th>URI</th><td>
                        <a href="{{ Uri::link('dim', $dimension->canonical) }}" target="_blank">{{ \App\Services\UnitsReference::dimensionUri($dimension->canonical) }}</a>
                    </td></tr>
                    <tr><th>Canonical</th><td>{{ $dimension->canonical }}</td></tr>
                    @foreach ($exponents as $label => $value)
                        <tr><th>{{ $label }}</th><td>{{ $value === null ? '—' : $value }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            <h3 class="py-2 mt-4">{{ Lg::pick('Grandezas físicas', 'Physical quantities') }} ({{ $quantities->count() }})</h3>
            <ul class="list-disc ml-6">
                @forelse ($quantities as $q)
                    <li><a href="{{ Uri::link('pq', $q->name) }}">{{ $q->name }}</a></li>
                @empty
                    <li class="text-gray-500">— ({{ Lg::pick('nenhuma ligada ainda', 'none linked yet') }})</li>
                @endforelse
            </ul>
            <p class="text-sm text-gray-500 mt-3">{{ Lg::pick('Representação legível por máquina:', 'Machine-readable:') }}
                <a href="?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
