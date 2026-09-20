<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Export PDTs.pt domain in JSON format of buildingSMART Data Dictionary</h1>

            <p class="mt-3 text-sm text-gray-600">
                Dictionary <strong>{{ \App\Services\UriService::dictionaryVersion() }}</strong> —
                <code>{{ \App\Services\UriService::dictionaryUri() }}</code>.
                The export carries the latest Active version of each PDT and only the properties
                those templates actually use. Every entry's <code>OwnedUri</code> is its identifier
                in the scheme above.
            </p>

            @if ($collisions)
                {{-- bSDD rejects duplicate codes, so the export stops. Codes come from the
                     records' own names and are never renamed here: the records have to be
                     reconciled first. --}}
                <div class="mt-4 border-l-4 border-red-500 bg-red-50 p-4">
                    <strong class="text-red-800">
                        Export blocked: {{ count($collisions) }} identifier collision(s).
                    </strong>
                    <p class="text-sm mt-1">
                        Each code below is claimed by more than one record. bSDD rejects duplicate
                        codes, and nothing is renamed automatically — reconcile these in
                        <a href="{{ route('admin.dedupe') }}" class="underline">Dedupe dictionary</a>
                        and export again.
                    </p>
                    <ul class="mt-3 text-sm">
                        @foreach ($collisions as $collision)
                        <li class="mb-2">
                            <code>{{ \App\Services\UriService::uri($collision['entity'], $collision['code']) }}</code>
                            <ul class="ml-5 text-gray-700">
                                @foreach ($collision['records'] as $record)
                                <li><code>{{ $record }}</code></li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-4 text-sm text-green-800">
                    No identifier collisions: every code resolves to exactly one record.
                </p>
            @endif

            <form method="POST" action="{{ route('productdatatemplates.exportJsonPSETS') }}">
                @csrf
                <br>
                <x-button-primary-pdts type="submit" title="Export in bsdd JSON format PSETS" />
            </form>

        </div>
    </div>
</x-app-layout>
