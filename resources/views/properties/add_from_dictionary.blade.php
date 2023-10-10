<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ __('Add Properties from Data Dictionary') }}</h1>

                <h2>{{ $selectedPdt->pdtNameEn }}</h2>
                <h3>{{ $selectedGroup->gopNameEn }}</h3>

                <form method="POST" action="{{ route('properties.addFromDictionary') }}">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">
                    <input type="hidden" name="gopId" value="{{ $selectedGroup->Id }}">

                    <div class="form-group">
                        <label for="selectedProperties">{{ __('Select Properties') }}</label>
                        <select multiple class="form-control" id="selectedProperties" name="selectedProperties[]" required>
                            @foreach ($dataDictionary as $property)
                            <option value="{{ $property->Id }}">{{ $property->nameEn }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-secondary-button type="submit">{{ __('Add Selected Properties') }}</x-secondary-button>
                </form>

                <!-- Display added properties in a table -->
                @if(isset($selectedProperties) && $selectedProperties->count() > 0)
                <h3>{{ __('Added Properties') }}</h3>
                <table>
                    <!-- Table headers -->
                    <tr>
                        <th>{{ __('Property Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Unit') }}</th>
                    </tr>
                    <!-- Table rows - Display added properties -->
                    @foreach($selectedProperties as $property)
                    <tr>
                        <td>{{ $property->property->nameEn }}</td>
                        <td>{{ $property->descriptionEn }}</td>
                        <td>{{ $property->unit }}</td>
                    </tr>
                    @endforeach
                </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>