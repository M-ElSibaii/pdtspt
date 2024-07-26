<x-app-layout>
    <div class="container sm:max-w-full py-9">
        <h1>{{ __('Property in data dictionary database - Table: PropertiesDataDictionaries') }}</h1>

        <form method="POST" action="{{ route('properties.updatedd', ['propertyddId' => $propertydd->Id]) }}">
            <input type="hidden" name="propertyddId" value="{{ $propertydd->propertyId }}">
            @csrf

            <!-- Display and make only 'relationToOtherDataDictionaries' editable -->
            <div class="form-group">
                <label for="Id">{{ __('Id') }}</label>
                <input type="text" name="Id" id="Id" class="form-control" value="{{ old('Id', $propertydd->Id) }}" readonly>
            </div>

            <div class="form-group">
                <label for="GUID">{{ __('GUID') }}</label>
                <input type="text" name="GUID" id="GUID" class="form-control" value="{{ old('GUID', $propertydd->GUID) }}" readonly>
            </div>

            <div class="form-group">
                <label for="namePt">{{ __('Name (Portuguese)') }}</label>
                <input type="text" name="namePt" id="namePt" class="form-control" value="{{ old('namePt', $propertydd->namePt) }}" readonly>
            </div>

            <div class="form-group">
                <label for="nameEn">{{ __('Name (English)') }}</label>
                <input type="text" name="nameEn" id="nameEn" class="form-control" value="{{ old('nameEn', $propertydd->nameEn) }}" readonly>
            </div>

            <div class="form-group">
                <label for="definitionPt">{{ __('Definition (Portuguese)') }}</label>
                <input type="text" name="definitionPt" id="definitionPt" class="form-control" value="{{ old('definitionPt', $propertydd->definitionPt) }}" readonly>
            </div>

            <div class="form-group">
                <label for="definitionEn">{{ __('Definition (English)') }}</label>
                <input type="text" name="definitionEn" id="definitionEn" class="form-control" value="{{ old('definitionEn', $propertydd->definitionEn) }}" readonly>
            </div>

            <div class="form-group">
                <label for="status">{{ __('Status') }}</label>
                <input type="text" name="status" id="status" class="form-control" value="{{ old('status', $propertydd->status) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dateOfCreation">{{ __('Date of Creation') }}</label>
                <input type="text" name="dateOfCreation" id="dateOfCreation" class="form-control" value="{{ old('dateOfCreation', $propertydd->dateOfCreation) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dateOfActivation">{{ __('Date of Activation') }}</label>
                <input type="text" name="dateOfActivation" id="dateOfActivation" class="form-control" value="{{ old('dateofActivation', $propertydd->dateofActivation) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dateOfLastChange">{{ __('Date of Last Change') }}</label>
                <input type="text" name="dateOfLastChange" id="dateOfLastChange" class="form-control" value="{{ old('dateOfLastChange', $propertydd->dateOfLastChange) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dateOfRevision">{{ __('Date of Revision') }}</label>
                <input type="text" name="dateOfRevision" id="dateOfRevision" class="form-control" value="{{ old('dateOfRevision', $propertydd->dateOfRevision) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dateOfVersion">{{ __('Date of Version') }}</label>
                <input type="text" name="dateOfVersion" id="dateOfVersion" class="form-control" value="{{ old('dateOfVersion', $propertydd->dateOfVersion) }}" readonly>
            </div>

            <div class="form-group">
                <label for="versionNumber">{{ __('Version Number') }}</label>
                <input type="text" name="versionNumber" id="versionNumber" class="form-control" value="{{ old('versionNumber', $propertydd->versionNumber) }}" readonly>
            </div>

            <div class="form-group">
                <label for="revisionNumber">{{ __('Revision Number') }}</label>
                <input type="text" name="revisionNumber" id="revisionNumber" class="form-control" value="{{ old('revisionNumber', $propertydd->revisionNumber) }}" readonly>
            </div>

            <div class="form-group">
                <label for="creatorsLanguage">{{ __('Creators Language') }}</label>
                <input type="text" name="creatorsLanguage" id="creatorsLanguage" class="form-control" value="{{ old('creatorsLanguage', $propertydd->creatorsLanguage) }}" readonly>
            </div>

            <div class="form-group">
                <label for="countryOfUse">{{ __('Country of Use') }}</label>
                <input type="text" name="countryOfUse" id="countryOfUse" class="form-control" value="{{ old('countryOfUse', $propertydd->countryOfUse) }}" readonly>
            </div>

            <div class="form-group">
                <label for="countryOfOrigin">{{ __('Country of Origin') }}</label>
                <input type="text" name="countryOfOrigin" id="countryOfOrigin" class="form-control" value="{{ old('countryOfOrigin', $propertydd->countryOfOrigin) }}" readonly>
            </div>

            <div class="form-group">
                <label for="physicalQuantity">{{ __('Physical Quantity') }}</label>
                <input type="text" name="physicalQuantity" id="physicalQuantity" class="form-control" value="{{ old('physicalQuantity', $propertydd->physicalQuantity) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dimension">{{ __('Dimension') }}</label>
                <input type="text" name="dimension" id="dimension" class="form-control" value="{{ old('dimension', $propertydd->dimension) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dataType">{{ __('Data Type') }}</label>
                <input type="text" name="dataType" id="dataType" class="form-control" value="{{ old('dataType', $propertydd->dataType) }}" readonly>
            </div>

            <div class="form-group">
                <label for="dynamicProperty">{{ __('Dynamic Property') }}</label>
                <input type="text" name="dynamicProperty" id="dynamicProperty" class="form-control" value="{{ old('dynamicProperty', $propertydd->dynamicProperty) }}" readonly>
            </div>

            <div class="form-group">
                <label for="parametersOfTheDynamicProperty">{{ __('Parameters of the Dynamic Property') }}</label>
                <input type="text" name="parametersOfTheDynamicProperty" id="parametersOfTheDynamicProperty" class="form-control" value="{{ old('parametersOfTheDynamicProperty', $propertydd->parametersOfTheDynamicProperty) }}" readonly>
            </div>

            <div class="form-group">
                <label for="units">{{ __('Units') }}</label>
                <input type="text" name="units" id="units" class="form-control" value="{{ old('units', $propertydd->units) }}" readonly>
            </div>

            <div class="form-group">
                <label for="namesOfDefiningValues">{{ __('Names of Defining Values') }}</label>
                <input type="text" name="namesOfDefiningValues" id="namesOfDefiningValues" class="form-control" value="{{ old('namesOfDefiningValues', $propertydd->namesOfDefiningValues) }}" readonly>
            </div>

            <div class="form-group">
                <label for="definingValues">{{ __('Defining Values') }}</label>
                <input type="text" name="definingValues" id="definingValues" class="form-control" value="{{ old('definingValues', $propertydd->definingValues) }}" readonly>
            </div>

            <div class="form-group">
                <label for="tolerance">{{ __('Tolerance') }}</label>
                <input type="text" name="tolerance" id="tolerance" class="form-control" value="{{ old('tolerance', $propertydd->tolerance) }}" readonly>
            </div>

            <div class="form-group">
                <label for="digitalFormat">{{ __('Digital Format') }}</label>
                <input type="text" name="digitalFormat" id="digitalFormat" class="form-control" value="{{ old('digitalFormat', $propertydd->digitalFormat) }}" readonly>
            </div>

            <div class="form-group">
                <label for="textFormat">{{ __('Text Format') }}</label>
                <input type="text" name="textFormat" id="textFormat" class="form-control" value="{{ old('textFormat', $propertydd->textFormat) }}" readonly>
            </div>

            <div class="form-group">
                <label for="listOfPossibleValuesInLanguageN">{{ __('List of Possible Values in Language N') }}</label>
                <input type="text" name="listOfPossibleValuesInLanguageN" id="listOfPossibleValuesInLanguageN" class="form-control" value="{{ old('listOfPossibleValuesInLanguageN', $propertydd->listOfPossibleValuesInLanguageN) }}" readonly>
            </div>

            <div class="form-group">
                <label for="boundaryValues">{{ __('Boundary Values') }}</label>
                <input type="text" name="boundaryValues" id="boundaryValues" class="form-control" value="{{ old('boundaryValues', $propertydd->boundaryValues) }}" readonly>
            </div>

            <div class="form-group">
                <label for="updated_at">{{ __('Updated At') }}</label>
                <input type="text" name="updated_at" id="updated_at" class="form-control" value="{{ old('updated_at', $propertydd->updated_at) }}" readonly>
            </div>

            <div class="form-group">
                <label for="created_at">{{ __('Created At') }}</label>
                <input type="text" name="created_at" id="created_at" class="form-control" value="{{ old('created_at', $propertydd->created_at) }}" readonly>
            </div>

            <div class="form-group">
                <label for="depreciationExplanation">{{ __('Depreciation Explanation') }}</label>
                <input type="text" name="depreciationExplanation" id="depreciationExplanation" class="form-control" value="{{ old('depreciationExplanation', $propertydd->depreciationExplanation) }}" readonly>
            </div>

            <div class="form-group">
                <label for="depreciationDate">{{ __('Depreciation Date') }}</label>
                <input type="text" name="depreciationDate" id="depreciationDate" class="form-control" value="{{ old('depreciationDate', $propertydd->depreciationDate) }}" readonly>
            </div>

            <!-- Editable field 'relationToOtherDataDictionaries' -->
            <div class="form-group">
                <label for="relationToOtherDataDictionaries">{{ __('Relation to Other Data Dictionaries') }}</label>
                <input type="text" name="relationToOtherDataDictionaries" id="relationToOtherDataDictionaries" class="form-control" value="{{ old('relationToOtherDataDictionaries', $propertydd->relationToOtherDataDictionaries) }}">
            </div>

            <x-button-primary-pdts type="submit" title="Update Property" />
        </form>
    </div>
</x-app-layout>