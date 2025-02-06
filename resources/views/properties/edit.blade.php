<x-app-layout>
    <div class="container sm:max-w-full py-9">
        <h1>{{ __('Property in database - Table: Properties') }}</h1>

        <form method="POST" action="{{ route('properties.update', ['propertyId' => $property->Id]) }}">
            <input type=hidden name='propertyId' value='{{ $property->Id }}'>
            @csrf


            <!-- Your form fields for editing the property -->
            <div class="form-group">
                <label for="Id">{{ __('Id') }}</label>
                <input type="text" name="Id" id="Id" class="form-control" value="{{ old('Id', $property->Id) }}" readonly>
            </div>

            <div class="form-group">
                <label for="propertyId">{{ __('propertyIdInDictionary') }}</label>
                <input type="text" name="propertyId" id="propertyId" class="form-control" value="{{ old('propertyId', $property->propertyId) }}" readonly>
            </div>

            <div class="form-group">
                <label for="propertyId">{{ __('property name in Dictionary') }}</label>
                <input type="text" name="nameEn" id="nameEn" class="form-control" value="{{ $propertyNameInDD->nameEn }}" readonly>
            </div>


            <div class="form-group">
                <label for="GUID">{{ __('GUID') }}</label>
                <input type="text" name="GUID" id="GUID" class="form-control" value="{{ old('GUID', $property->GUID) }}" readonly>
            </div>

            <div class="form-group">
                <label for="gopID">{{ __('gopID') }}</label>
                <input type="text" name="gopID" id="gopID" class="form-control" value="{{ old('gopID', $property->gopID) }}" readonly>
            </div>

            <div class="form-group">
                <label for="pdtID">{{ __('pdtID') }}</label>
                <input type="text" name="pdtID" id="pdtID" class="form-control" value="{{ old('pdtID', $property->pdtID) }}" readonly>
            </div>


            <div class="form-group">
                <label for="referenceDocumentGUID">{{ __('Reference Document') }}</label>

                <!-- Search input to filter radio buttons -->
                <input type="text" id="searchReferenceDocument" class="form-control mb-2" placeholder="Search Reference Document" onkeyup="filterReferenceDocuments()">

                <!-- Scrollable container for radio buttons -->
                <div class="scrollable-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">
                    <!-- 'None' option to deselect -->
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="referenceDocumentGUID" id="referenceDocumentNone" value="n/a" {{ $property->referenceDocumentGUID == 'n/a' ? 'checked' : '' }}>
                        <label class="form-check-label" for="referenceDocumentNone">
                            None
                        </label>
                    </div>

                    @foreach ($referenceDocuments as $document)
                    @if ($document->GUID != 'n/a') <!-- Only include documents where GUID is not 'n/a' -->
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="referenceDocumentGUID" id="referenceDocument{{ $document->GUID }}" value="{{ $document->GUID }}" {{ $property->referenceDocumentGUID == $document->GUID ? 'checked' : '' }}>
                        <label class="form-check-label" for="referenceDocument{{ $document->GUID }}">
                            {{ $document->rdName }}: {{ $document->title }}
                        </label>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            <script>
                // JavaScript function to filter the radio buttons based on the input value
                function filterReferenceDocuments() {
                    const searchInput = document.getElementById('searchReferenceDocument').value.toLowerCase();
                    const radioButtons = document.querySelectorAll('.form-check');

                    radioButtons.forEach(radioButton => {
                        const label = radioButton.querySelector('label').textContent.toLowerCase();
                        radioButton.style.display = label.includes(searchInput) ? '' : 'none'; // Show or hide based on the search input
                    });
                }
            </script>



            <div class="form-group">
                <label for="descriptionEn">{{ __('Description (English)') }}</label>
                <textarea name="descriptionEn" id="descriptionEn" class="form-control">{{ old('descriptionEn', $property->descriptionEn) }}</textarea>
            </div>

            <div class="form-group">
                <label for="descriptionPt">{{ __('Description (Portuguese)') }}</label>
                <textarea name="descriptionPt" id="descriptionPt" class="form-control">{{ old('descriptionPt', $property->descriptionPt) }}</textarea>
            </div>


            <div class="form-group">
                <label for="visualRepresentation">{{ __('Visual Representation') }}</label>
                <input type="text" name="visualRepresentation" id="visualRepresentation" class="form-control" value="{{ old('visualRepresentation', $property->visualRepresentation) }}" readonly>
            </div>

            <x-button-primary-pdts type="submit" title="Update Property" />
        </form>


    </div>

</x-app-layout>