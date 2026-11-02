<x-app-layout>
    <div class="refpg">
        <div class="container sm:max-w-full py-9">
            @include('reference._head', ['kind' => Lg::pick('Dimensão', 'Dimension')])

            <div class="mt-4">
                <h1 class="flex-none inline ref-dim">{{ \App\Services\UnitsReference::prettyDimension($dimension->canonical) }}</h1>
            </div>
            <div class="ref-kind">{{ Lg::pick('Dimensão', 'Dimension') }} <small>(EN ISO 23387 · DimensionType · ISO 80000)</small></div>

            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr><th class="lg:w-1/4 md:w-1/4 sm:w-1/2">URI</th><td class="lg:w-3/4 md:w-3/4 sm:w-1/2">
                        <a class="ref-link" href="{{ Uri::link('dim', $dimension->canonical) }}">{{ rawurldecode(\App\Services\UnitsReference::dimensionUri($dimension->canonical)) }}</a>
                    </td></tr>
                    <tr><th>GUID</th><td>{{ $dimension->guid }}</td></tr>
                    <tr><th>{{ Lg::pick('Forma canónica', 'Canonical form') }}</th><td class="ref-dim">{{ $dimension->canonical }}</td></tr>
                    @foreach ($exponents as $label => $value)
                        <tr><th>{{ $label }}</th><td>{{ $value === null ? '—' : (rtrim(rtrim((string) $value, '0'), '.') ?: '0') }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            <h3 class="ref-sec">{{ Lg::pick('Grandezas físicas', 'Physical quantities') }} ({{ $quantities->count() }})</h3>
            @if ($quantities->isEmpty())
                <p class="ref-muted">{{ Lg::pick('Nenhuma grandeza com nome tem esta dimensão.', 'No named quantity has this dimension.') }}</p>
            @else
                <ul class="ref-chips">
                    @foreach ($quantities as $q)
                        <li><a class="ref-link" href="{{ Uri::link('pq', $q->name) }}">{{ Lg::pick($q->namePt ?: $q->name, $q->name) }}</a></li>
                    @endforeach
                </ul>
            @endif

            <h3 class="ref-sec">{{ Lg::pick('Unidades', 'Units') }} ({{ $units->count() }})</h3>
            <ul class="ref-chips">
                @foreach ($units as $u)
                    <li><a class="ref-link" href="{{ Uri::link('unit', $u->code) }}" title="{{ $u->name }}">{{ $u->code }}</a>
                        @if ($u->physicalQuantity && $quantities->count() > 1)<small>{{ Lg::pick($u->physicalQuantity->namePt ?: $u->physicalQuantity->name, $u->physicalQuantity->name) }}</small>@endif
                    </li>
                @endforeach
            </ul>

            <p class="text-sm ref-muted mt-4">{{ Lg::pick('Representação legível por máquina:', 'Machine-readable:') }}
                <a class="ref-link" href="?format=json">JSON</a></p>
        </div>
    </div>
</x-app-layout>
