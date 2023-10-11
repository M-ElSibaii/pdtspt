<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ __('Add Properties from Data Dictionary') }}</h1>

                <h2>{{ $selectedPdt->pdtNameEn }}</h2>
                <h3>{{ $selectedGroup->gopNameEn }}</h3>

                <form method="POST" action="{{ route('properties.addFromDataDictionary') }}">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">
                    <input type="hidden" name="gopId" value="{{ $selectedGroup->Id }}">

                    <div class="form-group">
                        <label for="selectedProperties">{{ __('Select Properties') }}</label>
                        <select multiple class="form-control" id="selectedProperties" name="selectedProperties[]" required>
                            @foreach ($dataDictionary as $property)
                            <option value="{{ $property->Id }}">{{ $property->nameEn }} / {{ $property->namePt }} V {{ $property->versionNumber }}.{{ $property->revisionNumber }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-secondary-button type="submit">{{ __('Add Selected Properties') }}</x-secondary-button>

                </form>
                <br>
                <!-- Table to display added properties -->
                @php
                $properties = \App\Models\Properties::where('pdtId', $selectedPdt->Id)
                ->where('gopId', $selectedGroup->Id)
                ->get();
                @endphp

                @if($properties->count() > 0)
                <table id='tblpdts'>
                    <!-- Table headers -->
                    <tr>
                        <th>{{ __('Property Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Unit') }}</th>
                    </tr>
                    <!-- Table rows - Display properties for this group -->
                    @foreach($properties as $property)
                    @php
                    $additionalInfo = \App\Models\propertiesDataDictionaries::where('GUID', $property->GUID)
                    ->orderByDesc('versionNumber')
                    ->orderByDesc('revisionNumber')
                    ->first();
                    @endphp
                    <tr>
                        <td>{{ $additionalInfo->nameEn ?? '' }}</td>
                        <td>{{ $property->descriptionEn ?? '' }}</td>
                        <td>{{ $additionalInfo->units ?? '' }}</td>
                    </tr>
                    @endforeach
                </table>
                <br>
                @else
                <p>{{ __('No properties found for this group.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>