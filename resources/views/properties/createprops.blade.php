<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ $selectedPdt->pdtNameEn }} Data Template</h1>
                <h1>{{ __('Create Properties') }}</h1>

                @if(isset($selectedPdt) && $groupofproperties->count() > 0)
                @foreach($groupofproperties as $group)
                <h2>Group of property: {{ $group->gopNameEn }}</h2>
                <br>
                <!-- Button to add new properties manually -->
                <form method="POST" action="{{ route('properties.addFromDictionary') }}" target="_blank">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">
                    <input type="hidden" name="gopId" value="{{ $group->Id }}">
                    <input type="hidden" name="propertyId" value="{{ $nextIdDataDictionary }}">
                    <x-secondary-button type="submit">{{ __('Add Properties From Data Dictionary') }}</x-secondary-button>
                </form>
                <br>

                <!-- Button to add new properties manually -->
                <form method="POST" action="{{ route('properties.addNew') }}" target="_blank">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">
                    <input type="hidden" name="gopId" value="{{ $group->Id }}">
                    <x-secondary-button type="submit">{{ __('Add New Properties') }}</x-secondary-button>
                </form>
                <br>

                <!-- Table to display added properties -->
                @php
                $properties = \App\Models\Properties::where('pdtId', $selectedPdt->Id)
                ->where('gopId', $group->Id)
                ->get();
                @endphp

                @if($properties->count() > 0)
                <table id='tblpdts'>
                    <!-- Table headers -->
                    <tr>
                        <th>{{ __('Property Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Unit') }}</th>
                        <th>{{ __('Actions') }}</th> <!-- New column for buttons -->
                    </tr>
                    <!-- Table rows - Display properties for this group -->
                    @foreach ($properties as $property)
                    @php
                    $additionalInfo = \App\Models\propertiesDataDictionaries::where('Id', $property->propertyId)
                    ->first();
                    @endphp
                    <tr>
                        <td>{{ $additionalInfo->nameEn ?? '' }}</td>
                        <td>{{ $property->descriptionEn ?? '' }}</td>
                        <td>{{ $additionalInfo->units ?? '' }}</td>
                        <td><a href="{{ route('properties.edit', ['propertyId' => $property->Id]) }}" class="btn btn-warning">Edit Properties table</a>
                            <a href="{{ route('properties.editdd', ['propertyddId' => $property->propertyId]) }}" class="btn btn-warning"> Edit Dictionary table</a>
                            <a href="{{ url('datadictionaryview/' . $additionalInfo->Id . '-' . $additionalInfo->GUID) }}" class="btn btn-warning">Data dictionary view</a>
                        </td>
                    </tr>
                    @endforeach
                </table>
                <br>
                @else
                <p>{{ __('No properties found for this group.') }}</p>
                @endif
                @endforeach
                @else
                <p>{{ __('No groups of properties found for the selected PDT.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>