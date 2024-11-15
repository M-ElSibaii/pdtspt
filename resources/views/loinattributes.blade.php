<x-app-layout>

    <div class="container sm:max-w-full py-9">
        @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
        @endif
        <h1>{{ __('Criar Nível de Necessidade de Informação') }}</h1>

        <form method="POST" action="{{ route('loinattributesstore', $project->id) }}" onsubmit="return validateForm()">
            @csrf
            <input type="hidden" name="projectId" value="{{ $project->id }}">
            <div class="loin-container">
                <h2>{{ __('Atributos de Nível de Necessidade de Informação') }}</h2>
                <br>

                <!-- Milestones Section -->
                <div class="form-group">
                    <label for="milestone"><strong>{{ __('Phases') }}</strong></label>
                    <div id="milestone-container">
                        <input type="text" name="milestones[]" class="form-control" placeholder="Milestone">
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addMilestoneField()">Add More Milestones</button>

                    <!-- List Milestones -->
                    <ul>
                        @foreach($milestones as $milestone)
                        <div class="attribute" data-id="{{ $milestone->id }}" data-type="milestone">
                            {{ $milestone->milestone }}
                            <button class="delete-attribute" style="border: none; background: none; color: red; font-size: 1.2em; cursor: pointer;">&times;</button>

                        </div>
                        @endforeach
                    </ul>
                </div>

                <!-- Actors Section -->
                <div class="form-group">
                    <label for="actor"><strong>{{ __('Ator') }}</strong></label>
                    <div id="actor-container">
                        <input type="text" name="actors[]" class="form-control" placeholder="Actor">

                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addActorField()">Add More Actors</button>

                    <!-- List Actors -->
                    <ul>
                        @foreach($actors as $actor)
                        <div class="attribute" data-id="{{ $actor->id }}" data-type="actor">
                            {{ $actor->actor }}
                            <button class="delete-attribute" style="border: none; background: none; color: red; font-size: 1.2em; cursor: pointer;">&times;</button>
                        </div>
                        @endforeach
                    </ul>
                </div>

                <!-- Purposes Section -->
                <div class="form-group">
                    <label for="purpose"><strong>{{ __('Propósitos') }}</strong></label>
                    <div id="purpose-container">
                        <input type="text" name="purposes[]" class="form-control" placeholder="Purpose">

                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addPurposeField()">Add More Purposes</button>

                    <!-- List Purposes -->
                    <ul>
                        @foreach($purposes as $purpose)
                        <div class="attribute" data-id="{{ $purpose->id }}" data-type="purpose">
                            {{ $purpose->purpose }}
                            <button class="delete-attribute" style="border: none; background: none; color: red; font-size: 1.2em; cursor: pointer;">&times;</button>
                        </div>
                        @endforeach
                    </ul>
                </div>

                <!-- Objects and IFC Class Section -->
                <div class="form-group">
                    <label for="object"><strong>{{ __('Objeto') }}</strong></label>
                    <div id="object-container" class="row mb-2">
                        <div class="col-md-6">
                            <input type="text" name="objects[]" class="form-control" placeholder="Object">

                        </div>
                        <div class="col-md-6">
                            <select name="ifcClasses[]" class="form-control">
                                <option value="" disabled selected>{{ __('Selecionar IFC Class') }}</option>
                                @foreach($ifcClasses as $ifcClass)
                                <option value="{{ $ifcClass['id'] }}">{{ $ifcClass['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addObjectField()">Add More Objects</button>

                    <!-- List objects with IFC Class -->
                    <ul>
                        @foreach($objects as $object)
                        <div class="attribute" data-id="{{ $object->id }}" data-type="object">
                            {{ $object->object }} - {{ $object->ifcClass }}
                            <button class="delete-attribute" style="border: none; background: none; color: red; font-size: 1.2em; cursor: pointer;">&times;</button>
                        </div>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <!-- Green "Salvar Attributes" Button -->
                    <button type="submit" class="btn btn-success text-black">
                        {{ __('Salvar Attributes') }}
                    </button>

                    <!-- Blue "Criar LOINs" Button -->
                    <a href="{{ route('loincreate1', $project->id) }}" class="btn btn-primary text-black">
                        {{ __('Criar LOINs') }}
                    </a>

                </div>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).on('click', '.delete-attribute', function(e) {
            e.preventDefault();
            const attributeDiv = $(this).closest('.attribute');
            const attributeId = attributeDiv.data('id');
            const attributeType = attributeDiv.data('type');

            if (confirm('Are you sure you want to delete this attribute?')) {
                $.ajax({
                    url: '{{ route("loinattributesdelete", ["projectId" => $project->id]) }}', // Update this route as needed
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}', // Include CSRF token for security
                        attributeType: attributeType,
                        id: attributeId,
                    },
                    success: function(response) {
                        attributeDiv.remove();
                        alert(response.success || 'Attribute deleted successfully.');
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error || 'An error occurred while deleting the attribute.');
                    }
                });
            }
        });
    </script>
    <script>
        // Add new milestone field
        function addMilestoneField() {
            const container = document.getElementById('milestone-container');
            const newField = document.createElement('div');
            newField.classList.add('input-group', 'mb-2');
            newField.innerHTML = `
                <input type="text" name="milestones[]" class="form-control" placeholder="Milestone">  
                <button type="button" class="text-danger" onclick="removeField(this)">X</button>
            `;
            container.appendChild(newField);
        }

        // Add new actor field
        function addActorField() {
            const container = document.getElementById('actor-container');
            const newField = document.createElement('div');
            newField.classList.add('input-group', 'mb-2');
            newField.innerHTML = `
                <input type="text" name="actors[]" class="form-control" placeholder="Actor">
                <button type="button" class="text-danger" onclick="removeField(this)">X</button>
            `;
            container.appendChild(newField);
        }

        // Add new purpose field
        function addPurposeField() {
            const container = document.getElementById('purpose-container');
            const newField = document.createElement('div');
            newField.classList.add('input-group', 'mb-2');
            newField.innerHTML = `
                <input type="text" name="purposes[]" class="form-control" placeholder="Purpose">
                <button type="button" class="text-danger" onclick="removeField(this)">X</button>
            `;
            container.appendChild(newField);
        }

        // Add new object field with IFC class select
        function addObjectField() {
            const container = document.getElementById('object-container');
            const newRow = document.createElement('div');
            newRow.classList.add('row', 'mb-2');

            // Object input
            const objectCol = document.createElement('div');
            objectCol.classList.add('col-md-6', 'input-group');
            const objectField = document.createElement('input');
            objectField.type = 'text';
            objectField.name = 'objects[]';
            objectField.classList.add('form-control');
            objectField.placeholder = 'Object';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('text-danger');
            removeBtn.textContent = 'X';
            removeBtn.onclick = function() {
                removeField(newRow); // Pass the entire row container
            };

            objectCol.appendChild(objectField);
            objectCol.appendChild(removeBtn);

            // IFC class select
            const ifcCol = document.createElement('div');
            ifcCol.classList.add('col-md-6');
            const ifcField = document.createElement('select');
            ifcField.name = 'ifcClasses[]';
            ifcField.classList.add('form-control');
            ifcField.innerHTML = `
        <option value="" disabled selected>{{ __('Selecionar IFC Class') }}</option>
        @foreach($ifcClasses as $ifcClass)
            <option value="{{ $ifcClass['id'] }}">{{ $ifcClass['name'] }}</option>
        @endforeach
    `;
            ifcCol.appendChild(ifcField);

            // Append new row with object and IFC class select
            newRow.appendChild(objectCol);
            newRow.appendChild(ifcCol);
            container.appendChild(newRow);
        }

        // Remove field function
        function removeField(row) {
            row.remove(); // Remove the entire row container
        }
    </script>
</x-app-layout>