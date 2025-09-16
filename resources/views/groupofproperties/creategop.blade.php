<!-- resources/views/groupofproperties/create2.blade.php -->
<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif
                <h1>{{ __('Add Group of Properties') }}</h1>

                <!-- Display selected PDT and associated groups of properties -->
                <h2>{{ __('Selected PDT') }}: {{ $selectedPdt->pdtNameEn }}</h2>
                @if(count($associatedGroups) > 0)
                <h3>{{ __('Associated Groups of Properties') }}</h3>
                <table id='tblpdts' cellpadding='0' cellspacing='0'>
                    <!-- Table headers -->
                    <tr>
                        <th>{{ __('Group Name (English)') }}</th>
                        <th>{{ __('Group Name (Portuguese)') }}</th>
                        <th>{{ __('Definition (English)') }}</th>
                        <th>{{ __('Definition (Portuguese)') }}</th>
                    </tr>
                    <!-- Table rows -->
                    @foreach($associatedGroups as $group)
                    <tr>
                        <td>{{ $group->gopNameEn }}</td>
                        <td>{{ $group->gopNamePt }}</td>
                        <td>{{ $group->definitionEn }}</td>
                        <td>{{ $group->definitionPt }}</td>

                        <!-- Add other columns based on the Group of Properties table -->
                    </tr>
                    @endforeach
                </table>
                <br>
                @else
                <p>{{ __('No associated groups of properties.') }}</p>
                @endif

                <!-- Form to add a new group of properties -->
                <form method="POST" action="{{ route('groupofproperties.storegop') }}">
                    @csrf
                    <input type="hidden" name="pdtId" value="{{ $selectedPdt->Id }}">

                    <div class="form-group">
                        <label for="gopNameEn">{{ __('Group Name (English)') }}</label>
                        <input type="text" class="form-control" id="gopNameEn" name="gopNameEn" required>
                    </div>

                    <div class="form-group">
                        <label for="gopNamePt">{{ __('Group Name (Portuguese)') }}</label>
                        <input type="text" class="form-control" id="gopNamePt" name="gopNamePt" required>
                    </div>

                    <div class="form-group">
                        <label for="GUID">{{ __('GUID') }}</label>
                        <input type="text" class="form-control" id="GUID" name="GUID" required>
                    </div>

                    <div class="form-group">
                        <label for="definitionEn">{{ __('Definition (English)') }}</label>
                        <textarea class="form-control" id="definitionEn" name="definitionEn" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="definitionPt">{{ __('Definition (Portuguese)') }}</label>
                        <textarea class="form-control" id="definitionPt" name="definitionPt" rows="3"></textarea>
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
                        <input type="text" class="form-control" id="status" name="status">
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
                        <input type="text" class="form-control" id="dateOfRevision" name="dateOfRevision" required value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfVersion">{{ __('Date of Version') }}</label>
                        <input type="text" class="form-control" id="dateOfVersion" name="dateOfVersion" required value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="updated_at">{{ __('updated_at') }}</label>
                        <input type="text" class="form-control" id="updated_at" name="updated_at" required value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="created_at">{{ __('created_at') }}</label>
                        <input type="text" class="form-control" id="created_at" name="created_at" required value="{{ now() }}" readonly>
                    </div>


                    <div class="form-group">
                        <label for="versionNumber">{{ __('Version Number') }}</label>
                        <input type="text" class="form-control" id="versionNumber" name="versionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="revisionNumber">{{ __('Revision Number') }}</label>
                        <input type="text" class="form-control" id="revisionNumber" name="revisionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="listOfReplacedProperties">{{ __('List of Replaced Groups of Properties') }}</label>
                        <textarea class="form-control" id="listOfReplacedProperties" name="listOfReplacedProperties" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="listOfReplacingProperties">{{ __('List of Replacing Groups of Properties') }}</label>
                        <textarea class="form-control" id="listOfReplacingProperties" name="listOfReplacingProperties" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="relationToOtherDataDictionaries">{{ __('Relation to Other Data Dictionaries') }}</label>
                        <textarea class="form-control" id="relationToOtherDataDictionaries" name="relationToOtherDataDictionaries" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="creatorsLanguage">{{ __('Creators Language') }}</label>
                        <input type="text" class="form-control" id="creatorsLanguage" name="creatorsLanguage">
                    </div>

                    <div class="form-group">
                        <label for="visualRepresentation">{{ __('Visual Representation') }}</label>
                        <textarea class="form-control" id="visualRepresentation" name="visualRepresentation" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="countryOfUse">{{ __('Country of Use') }}</label>
                        <textarea class="form-control" id="countryOfUse" name="countryOfUse" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="countryOfOrigin">{{ __('Country of Origin') }}</label>
                        <textarea class="form-control" id="countryOfOrigin" name="countryOfOrigin" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="categoryOfGroupOfProperties">{{ __('Category of Group of Properties') }}</label>
                        <textarea class="form-control" id="categoryOfGroupOfProperties" name="categoryOfGroupOfProperties" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="parentGroupOfProperties">{{ __('Parent Group of Properties') }}</label>
                        <textarea class="form-control" id="parentGroupOfProperties" name="parentGroupOfProperties" rows="3"></textarea>
                    </div>

                    <!-- Add other input fields based on the Group of Properties table -->
                    <!-- ... -->

                    <x-primary-button type="submit">
                        {{ __('Add Group of Properties') }}
                    </x-primary-button>

                    <!-- Button to go to createprops page with PDT Id -->
<div class="form-group mt-4">
    <a href="{{ url('/properties/createprops?pdtId=' . $selectedPdt->Id) }}" class="btn btn-secondary">
        {{ __('Go to Properties Creation') }}
    </a>
</div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>