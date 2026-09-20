<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ Lg::t('Identificador ambíguo') }}</h1>
            <p class="py-2">
                {{ Lg::pick(
                    'Este identificador é reclamado por mais do que um registo, por isso não pode ser resolvido. Os registos têm de ser reconciliados no dicionário; nenhum foi escolhido automaticamente.',
                    'This identifier is claimed by more than one record, so it cannot be resolved. The records must be reconciled in the dictionary; none was picked automatically.'
                ) }}
            </p>
            <table id="tblprop" cellpadding="0" cellspacing="0">
                <tr><th class="lg:w-1/4">URI</th><td>{{ \App\Services\UriService::uri($entity, $code) }}</td></tr>
                @foreach ($records as $r)
                <tr><th>{{ Lg::pick('Registo', 'Record') }} {{ $loop->iteration }}</th><td><code>{{ $r }}</code></td></tr>
                @endforeach
            </table>
        </div>
    </div>
</x-app-layout>
