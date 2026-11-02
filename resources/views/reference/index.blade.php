<x-app-layout>
    <div class="refpg">
        <div class="container sm:max-w-full py-9">
            @include('reference._head')

            <h1 class="mt-4">{{ Lg::pick('Unidades, grandezas e dimensões', 'Units, quantities and dimensions') }}</h1>
            <p class="ref-muted" style="margin-top:-.5rem;">
                {{ Lg::pick(
                    'A camada de referência EN ISO 23387 usada pelas propriedades do dicionário: cada unidade pertence a uma grandeza física, e cada grandeza tem uma dimensão SI.',
                    'The EN ISO 23387 reference layer used by the dictionary properties: each unit belongs to a physical quantity, and each quantity has an SI dimension.') }}
            </p>

            @if ($q !== '')
                <p>
                    {{ Lg::pick('Resultados para', 'Results for') }} <strong>“{{ $q }}”</strong>:
                    {{ $quantities->count() }} {{ Lg::pick('grandezas', 'quantities') }},
                    {{ $quantities->sum(fn ($pq) => $pq->units->count()) + $unclassified->count() }} {{ Lg::pick('unidades', 'units') }},
                    {{ $dimensions->count() }} {{ Lg::pick('dimensões', 'dimensions') }}
                    · <a class="ref-link" href="{{ route('reference.index') }}">{{ Lg::pick('ver tudo', 'show all') }}</a>
                </p>
            @endif

            <h3 class="ref-sec">{{ Lg::pick('Grandezas físicas e as suas unidades', 'Physical quantities and their units') }} ({{ $quantities->count() }})</h3>
            <table class="ref-list">
                <thead><tr><th style="width:28%">{{ Lg::pick('Grandeza física', 'Physical quantity') }}</th><th style="width:14%">{{ Lg::pick('Dimensão', 'Dimension') }}</th><th>{{ Lg::pick('Unidades', 'Units') }}</th></tr></thead>
                <tbody>
                    @foreach ($quantities as $pq)
                        <tr>
                            <td>
                                <a class="ref-link" href="{{ Uri::link('pq', $pq->name) }}">{{ Lg::pick($pq->namePt ?: $pq->name, $pq->name) }}</a>
                                @if (Lg::isPt() && $pq->namePt && $pq->namePt !== $pq->name)<div class="ref-muted text-xs">{{ $pq->name }}</div>@endif
                            </td>
                            <td class="ref-dim">
                                @if ($pq->dimension)
                                    <a class="ref-link" href="{{ Uri::link('dim', $pq->dimension->canonical) }}">{{ \App\Services\UnitsReference::prettyDimension($pq->dimension->canonical) }}</a>
                                @else
                                    <span class="ref-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @forelse ($pq->units as $u)
                                    <a class="ref-link" href="{{ Uri::link('unit', $u->code) }}" title="{{ $u->name }}">{{ $u->code }}</a>@if (!$loop->last), @endif
                                @empty
                                    <span class="ref-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($unclassified->isNotEmpty())
                <h3 class="ref-sec">{{ Lg::pick('Unidades sem grandeza classificada', 'Units without a classified quantity') }} ({{ $unclassified->count() }})</h3>
                <ul class="ref-chips">
                    @foreach ($unclassified as $u)
                        <li><a class="ref-link" href="{{ Uri::link('unit', $u->code) }}" title="{{ $u->name }}">{{ $u->code }}</a></li>
                    @endforeach
                </ul>
            @endif

            <h3 class="ref-sec">{{ Lg::pick('Dimensões', 'Dimensions') }} ({{ $dimensions->count() }})</h3>
            <ul class="ref-chips">
                @foreach ($dimensions as $d)
                    <li><a class="ref-link ref-dim" href="{{ Uri::link('dim', $d->canonical) }}" title="{{ $d->canonical }}">{{ \App\Services\UnitsReference::prettyDimension($d->canonical) }}</a></li>
                @endforeach
            </ul>

            <p class="text-sm ref-muted mt-4">API:
                <a class="ref-link" href="{{ url('/api/units') }}">/api/units</a> ·
                <a class="ref-link" href="{{ url('/api/quantityKinds') }}">/api/quantityKinds</a> ·
                <a class="ref-link" href="{{ url('/api/dimensions') }}">/api/dimensions</a></p>
        </div>
    </div>
</x-app-layout>
