<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif
                <h1>{{ __('Add New Properties to data dictionary and to group of property in selected PDT') }}</h1>

                <h2>{{ $selectedPdt->pdtNameEn }}</h2>
                <h3>{{ $selectedGroup->gopNameEn }}</h3>

                <form method="POST" action="{{ route('properties.addPropertyManual') }}">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">
                    <input type="hidden" name="gopId" value="{{ $selectedGroup->Id }}">
                    <input type="hidden" name="nextIdDataDictionary" value="{{ $nextIdDataDictionary }}">

                    <div class="form-group">
                        <label for="nameEnSc">{{ __('Name (English) Senctence case') }}</label>
                        <input type="text" class="form-control" id="nameEnSc" name="nameEnSc" required>
                    </div>
                    <div class="form-group">
                        <label for="nameEn">{{ __('Name (English) PascalCase') }}</label>
                        <input type="text" class="form-control" id="nameEn" name="nameEn" required>
                    </div>
                    <div class="form-group">
                        <label for="namePtSc">{{ __(' Name (Portuguese) Senctence case, no accents') }}</label>
                        <input type="text" class="form-control" id="namePtSc" name="namePtSc" required>
                    </div>
                    <div class="form-group">
                        <label for="namePt">{{ __(' Name (Portuguese) PascalCase') }}</label>
                        <input type="text" class="form-control" id="namePt" name="namePt" required>
                    </div>
                    <div class="form-group">
                        <label for="GUID">{{ __('GUID') }}</label>
                        <input type="text" class="form-control" id="GUID" name="GUID" required>
                    </div>
                    <div class="form-group">
                        <label for="definitionEn">{{ __('Definition (English)') }}</label>
                        <textarea class="form-control" id="definitionEn" name="definitionEn" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="definitionPt">{{ __('Definition (Portuguese)') }}</label>
                        <textarea class="form-control" id="definitionPt" name="definitionPt" rows="3" required></textarea>
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
                        <label for="status">{{ __('Status') }}</label>
                        <input type="text" class="form-control" id="status" name="status" value="Active">
                    </div>

                    <div class="form-group">
                        <label for="dateOfCreation">{{ __('Date of Creation') }}</label>
                        <input type="text" class="form-control" id="dateOfCreation" name="dateOfCreation" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateofActivation">{{ __('Date of Activation') }}</label>
                        <input type="text" class="form-control" id="dateofActivation" name="dateofActivation" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfLastChange">{{ __('Date of Last Change') }}</label>
                        <input type="text" class="form-control" id="dateOfLastChange" name="dateOfLastChange" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfRevision">{{ __('Date of Revision') }}</label>
                        <input type="text" class="form-control" id="dateOfRevision" name="dateOfRevision" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfVersion">{{ __('Date of Version') }}</label>
                        <input type="text" class="form-control" id="dateOfVersion" name="dateOfVersion" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="versionNumber">{{ __('Version Number') }}</label>
                        <input type="number" class="form-control" id="versionNumber" name="versionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="revisionNumber">{{ __('Revision Number') }}</label>
                        <input type="number" class="form-control" id="revisionNumber" name="revisionNumber" required>
                    </div>


                    <div class="form-group">
                        <label for="listOfReplacedProperties">{{ __('List of Replaced Properties') }}</label>
                        <input type="text" class="form-control" id="listOfReplacedProperties" name="listOfReplacedProperties">
                    </div>

                    <div class="form-group">
                        <label for="listOfReplacingProperties">{{ __('List of Replacing Properties') }}</label>
                        <input type="text" class="form-control" id="listOfReplacingProperties" name="listOfReplacingProperties">
                    </div>

                    <div class="form-group">
                        <label for="relationToOtherDataDictionaries">{{ __('Relation to Other Data Dictionaries') }}</label>
                        <input type="text" class="form-control" id="relationToOtherDataDictionaries" name="relationToOtherDataDictionaries">
                    </div>

                    <div class="form-group">
                        <label for="creatorsLanguage">{{ __('Creators Language') }}</label>
                        <input type="text" class="form-control" id="creatorsLanguage" name="creatorsLanguage" value="pt-PT">
                    </div>

                    <div class="form-group">
                        <label for="visualRepresentation">{{ __('Visual Representation') }}</label>
                        <input type="text" class="form-control" id="visualRepresentation" name="visualRepresentation">
                    </div>

                    <div class="form-group">
                        <label for="countryOfUse">{{ __('Country of Use') }}</label>
                        <input type="text" class="form-control" id="countryOfUse" name="countryOfUse" value="PT">
                    </div>

                    <div class="form-group">
                        <label for="countryOfOrigin">{{ __('Country of Origin') }}</label>
                        <input type="text" class="form-control" id="countryOfOrigin" name="countryOfOrigin" value="PT">
                    </div>

                    <div class="form-group">
                        <label for="physicalQuantity">{{ __('Physical Quantity') }}</label>
                        <input type="text" class="form-control" id="physicalQuantity" name="physicalQuantity">
                    </div>


                    <div class="form-group">
                        <label for="dimension">{{ __('Dimension') }}</label>
                        <input type="text" class="form-control" id="dimension" name="dimension">
                    </div>

                    <div class="form-group">
                        <label for="dataType">{{ __('Data Type') }}</label>
                        <input type="text" class="form-control" id="dataType" name="dataType" value="String">
                    </div>

                    <div class="form-group">
                        <label for="dynamicProperty">{{ __('Dynamic Property') }}</label>
                        <input type="text" class="form-control" id="dynamicProperty" name="dynamicProperty" value="False">
                    </div>

                    <div class="form-group">
                        <label for="parametersOfTheDynamicProperty">{{ __('Parameters of the Dynamic Property') }}</label>
                        <input type="text" class="form-control" id="parametersOfTheDynamicProperty" name="parametersOfTheDynamicProperty">
                    </div>

                    <div class="form-group">
                        <label for="units">{{ __('Units') }}</label>
                        <input type="text" class="form-control" id="units" name="units" required>
                    </div>

                    <div class="form-group">
                        <label for="namesOfDefiningValues">{{ __('Names of Defining Values') }}</label>
                        <input type="text" class="form-control" id="namesOfDefiningValues" name="namesOfDefiningValues">
                    </div>

                    <div class="form-group">
                        <label for="definingValues">{{ __('Defining Values') }}</label>
                        <input type="text" class="form-control" id="definingValues" name="definingValues">
                    </div>

                    <div class="form-group">
                        <label for="tolerance">{{ __('Tolerance') }}</label>
                        <input type="text" class="form-control" id="tolerance" name="tolerance">
                    </div>

                    <div class="form-group">
                        <label for="digitalFormat">{{ __('Digital Format') }}</label>
                        <input type="text" class="form-control" id="digitalFormat" name="digitalFormat">
                    </div>

                    <div class="form-group">
                        <label for="textFormat">{{ __('Text Format') }}</label>
                        <input type="text" class="form-control" id="textFormat" name="textFormat" value="(UTF-8, 32)" readonly>
                    </div>

                    <div class="form-group">
                        <label for="listOfPossibleValuesInLanguageN">{{ __('List of Possible Values in Language N') }}</label>
                        <input type="text" class="form-control" id="listOfPossibleValuesInLanguageN" name="listOfPossibleValuesInLanguageN">
                    </div>

                    <div class="form-group">
                        <label for="boundaryValues">{{ __('Boundary Values') }}</label>
                        <input type="text" class="form-control" id="boundaryValues" name="boundaryValues">
                    </div>


                    <x-secondary-button type="submit">{{ __('Add New Property') }}</x-secondary-button>
                </form>

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