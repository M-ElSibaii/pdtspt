<x-app-layout>
    <div class="container sm:max-w-full py-9">
        <h1>{{ __('Property in database - Table: Properties') }}</h1>

        <form method="POST" action="{{ route('properties.update', ['propertyId' => $property->Id]) }}">
            <input type=hidden name='propertyId' value='{{ $property->Id }}'>
            @csrf


            <!-- Your form fields for editing the property -->
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
                <label for="referenceDocumentGUID">{{ __('Reference Document GUID') }}</label>
                <select class="form-control" id="referenceDocumentGUID" name="referenceDocumentGUID">
                    <!-- Default 'n/a' option -->
                    <option value="n/a">n/a</option>

                    <!-- Populate dropdown with reference documents -->
                    @foreach ($referenceDocuments as $document)
                    <option value="{{ $document->GUID }}">{{ $document->rdName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="descriptionEn">{{ __('Description (English)') }}</label>
                <textarea name="descriptionEn" id="descriptionEn" class="form-control" readonly>{{ old('descriptionEn', $property->descriptionEn) }}</textarea>
            </div>

            <div class="form-group">
                <label for="descriptionPt">{{ __('Description (Portuguese)') }}</label>
                <textarea name="descriptionPt" id="descriptionPt" class="form-control" readonly>{{ old('descriptionPt', $property->descriptionPt) }}</textarea>
            </div>


            <div class="form-group">
                <label for="visualRepresentation">{{ __('Visual Representation') }}</label>
                <input type="text" name="visualRepresentation" id="visualRepresentation" class="form-control" value="{{ old('visualRepresentation', $property->visualRepresentation) }}" readonly>
            </div>

            <div class="form-group">
                <label for="propertyVersion">{{ __('Property Version') }}</label>
                <input type="text" name="propertyVersion" id="propertyVersion" class="form-control" value="{{ old('propertyVersion', $property->propertyVersion) }}" readonly>
            </div>

            <div class="form-group">
                <label for="propertyRevision">{{ __('Property Revision') }}</label>
                <input type="text" name="propertyRevision" id="propertyRevision" class="form-control" value="{{ old('propertyRevision', $property->propertyRevision) }}" readonly>
            </div>
            <x-button-primary-pdts link="{{route('dashboard')}}" type="submit" title="Update Property" />
        </form>


    </div>

</x-app-layout>