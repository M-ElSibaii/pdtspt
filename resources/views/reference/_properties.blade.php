{{-- Dictionary properties measured in a unit (or in any unit of a quantity). Expects $properties. --}}
<h3 class="ref-sec">{{ Lg::pick('Propriedades que a usam', 'Properties that use it') }} ({{ $properties->count() }}{{ $properties->count() >= 200 ? '+' : '' }})</h3>
@if ($properties->isEmpty())
    <p class="ref-muted">{{ Lg::pick('Nenhuma propriedade do dicionário usa esta unidade.', 'No dictionary property uses it.') }}</p>
@else
    <table class="ref-list">
        <thead><tr><th>{{ Lg::pick('Propriedade', 'Property') }}</th><th>{{ Lg::pick('Unidade', 'Unit') }}</th><th>{{ Lg::pick('Versão', 'Version') }}</th><th>{{ Lg::pick('Estado', 'Status') }}</th></tr></thead>
        <tbody>
            @foreach ($properties as $p)
                @php
                    try { $href = \App\Services\UriService::buildLink('prop', $p); } catch (\Throwable $e) { $href = null; }
                    $label = Lg::pick($p->namePtSc ?: $p->namePt, $p->nameEnSc ?: $p->nameEn) ?: ($p->nameEn ?: $p->namePt);
                @endphp
                <tr>
                    <td>@if ($href)<a class="ref-link" href="{{ $href }}">{{ $label }}</a>@else{{ $label }}@endif</td>
                    <td>{{ $p->units }}</td>
                    <td>v{{ $p->versionNumber }}.{{ $p->revisionNumber }}</td>
                    <td>{{ $p->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
