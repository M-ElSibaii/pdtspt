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

                    <!-- Upload xlxs with property names -->
                    <div>
                        <h4>{{ __('Upload Property Names file') }}</h4>
                        <p> 1- Excel sheet should have a page for each group of property and its name should match group of property name</p>
                        <p> 2- Page must have only a list with the property names in PascalCase </p>

                        <div id="uploadForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" id="excelFile" name="excelFile" accept=".xlsx,.xls,.csv">
                            <button type="button" id="uploadButton">{{ __('Upload and Match') }}</button>
                        </div>

                    </div>


                    <!-- Section to display unmatched properties -->
                    <div id="unmatchedPropertiesContainer" style="margin-top: 20px;">
                        <h4>{{ __('Unmatched Properties list') }}</h4>
                        <ul id="unmatchedProperties" style="list-style-type: disc; padding-left: 20px;">
                            <!-- Unmatched properties will be appended here -->
                        </ul>
                    </div>
                    <br>


                    <!-- Search Bar -->
                    <h4>{{ __('Properties search bar') }}</h4>
                    <br>
                    <div class="form-group mb-4">
                        <input
                            type="text"
                            id="propertySearch"
                            class="form-control"
                            placeholder="{{ __('Search properties by name...') }}"
                            onkeyup="filterProperties()" />
                    </div>

                    @php
                    // Create an array to track duplicates
                    $nameEnOccurrences = [];

                    // Count occurrences of each nameEn
                    foreach ($dataDictionary as $property) {
                    $nameEnOccurrences[$property->nameEn] = ($nameEnOccurrences[$property->nameEn] ?? 0) + 1;
                    }

                    // Identify duplicated nameEn values
                    $duplicatedNames = array_filter($nameEnOccurrences, function ($count) {
                    return $count > 1;
                    });
                    @endphp

                    <!-- Scrollable Properties List -->
                    <div id="propertyList" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                        @foreach ($dataDictionary as $property)
                        <div
                            class="form-check mb-2 {{ array_key_exists($property->nameEn, $duplicatedNames) ? 'highlight-duplicate' : '' }}">
                            <input
                                type="checkbox"
                                class="form-check-input property-checkbox"
                                id="property-{{ $property->Id }}"
                                name="selectedProperties[]"
                                value="{{ $property->Id }}" />
                            <label class="form-check-label" for="property-{{ $property->Id }}">
                                {{ $property->nameEn }} / {{ $property->namePt }}
                                V {{ $property->versionNumber }}.{{ $property->revisionNumber }}
                            </label>
                        </div>
                        @endforeach


                    </div>

                    <br>
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

    <script>
        // JavaScript function to filter properties
        function filterProperties() {
            const searchInput = document.getElementById('propertySearch').value.toLowerCase();
            const propertyItems = document.querySelectorAll('#propertyList .form-check');

            propertyItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchInput) ? '' : 'none';
            });
        }

        document.getElementById('uploadButton').addEventListener('click', function() {
            console.log("Upload button clicked"); // Log when the upload button is clicked

            const formData = new FormData();
            const fileInput = document.getElementById('excelFile');

            if (fileInput.files.length === 0) {
                console.log("No file selected"); // Log if no file is selected
                alert("Please select a file before uploading.");
                return;
            }

            formData.append('excelFile', fileInput.files[0]);
            formData.append('selectedGroupName', "{{ $selectedGroup->gopNameEn }}");

            console.log("Form data appended, ready to send request");

            fetch("{{ route('properties.uploadExcel') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData,
                })
                .then(response => {
                    console.log("Response received from the server"); // Log when a response is received
                    return response.json();
                })
                .then(data => {
                    console.log("Data parsed successfully:", data); // Log the data received from the server

                    const matchedIds = data.matchedPropertyIds;
                    const unmatchedProperties = data.unmatchedProperties;

                    // Select checkboxes for matched properties
                    matchedIds.forEach(id => {
                        const checkbox = document.getElementById(`property-${id}`);
                        if (checkbox) {
                            console.log(`Checkbox for property ${id} found, selecting it`); // Log when a checkbox is found and selected
                            checkbox.checked = true;
                        } else {
                            console.log(`Checkbox for property ${id} not found`); // Log if no checkbox is found for the given property ID
                        }
                    });

                    // Display unmatched properties
                    const unmatchedList = document.getElementById('unmatchedProperties');
                    unmatchedList.innerHTML = ''; // Clear any previous unmatched properties

                    unmatchedProperties.forEach(name => {
                        console.log("Unmatched property:", name); // Log each unmatched property
                        const listItem = document.createElement('li');
                        listItem.textContent = name;
                        unmatchedList.appendChild(listItem);
                    });
                })
                .catch(error => {
                    console.error('Error:', error); // Log any error that occurs during the fetch
                    alert("An error occurred while processing the file.");
                });
        });
    </script>
</x-app-layout>