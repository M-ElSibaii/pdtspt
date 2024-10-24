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
                        <li>{{ $milestone->milestone }}
                            <form action="{{ route('loinsattributedestroy', ['project' => $project->id,'type' => 'milestones', 'id' => $milestone->id]) }}" method="POST" style="display:inline;">
                                @csrf

                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger" onclick="return confirm('Are you sure you want to delete this item?')">Excluir</button>
                            </form>
                        </li>
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
                        <li>{{ $actor->actor }}
                            <form action="{{ route('loinsattributedestroy', ['project' => $project->id,'type' => 'actors', 'id' => $actor->id]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger" onclick="return confirm('Are you sure you want to delete this item?')">Excluir</button>
                            </form>

                        </li>
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
                        <li>{{ $purpose->purpose }}
                            <form action="{{ route('loinsattributedestroy', ['project' => $project->id,'type' => 'purposes', 'id' => $purpose->id]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger" onclick="return confirm('Are you sure you want to delete this item?')">Excluir</button>
                            </form>

                        </li>
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
                        <li>{{ $object->object }} (IFC Class: {{ $object->ifcClass }})
                            <form action="{{ route('loinsattributedestroy', ['project' => $project->id,'type' => 'objects', 'id' => $object->id]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger" onclick="return confirm('Are you sure you want to delete this item?')">Excluir</button>
                            </form>

                        </li>
                        @endforeach
                    </ul>
                </div>

                <button type="submit" class="btn btn-secondary">{{ __('Salvar Attributes') }}</button>
                <a href="{{ route('loincreate1', $project->id) }}" class="btn btn-primary" style="color: black;">{{ __('Criar LOINs') }}</a>
            </div>
        </form>
    </div>

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
            removeBtn.classList.add('btn', 'btn-danger');
            removeBtn.textContent = 'X';
            removeBtn.onclick = function() {
                removeField(removeBtn);
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
        function removeField(button) {
            const fieldContainer = button.parentElement;
            fieldContainer.remove();
        }

        // Validate form before submission
        function validateForm() {
            const fieldsToValidate = [
                'milestones[]',
                'actors[]',
                'purposes[]',
                'objects[]'
            ];

            let isValid = true;

            fieldsToValidate.forEach(field => {
                const inputs = document.getElementsByName(field);
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid'); // Bootstrap invalid class
                        input.addEventListener('input', () => input.classList.remove('is-invalid'));
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });

            if (!isValid) {
                alert('Please fill in all required fields.');
            }
            return isValid;
        }
    </script>
</x-app-layout>