<x-app-layout>

    <div class="container sm:max-w-full py-9">
        <h1>{{ __('Criar Nível de Necessidade de Informação') }}</h1>
        <!-- Display Existing LOIN Fields -->
        <div class="summary-section mb-4">
            <h2>{{ __('Attributos') }}</h2>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>{{ __('Nome de Projeto:') }}</strong> {{ $nomeProjeto }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Actor Fornecedor:') }}</strong> {{ $atorFornecedor }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('phase:') }}</strong> {{ $phase}}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="objectDropdown"><strong>{{ __('Select Object:') }}</strong></label>
                    <select name="object" id="objectDropdown" class="form-control" required>
                        <option value="">{{ __('-- Select an Object --') }}</option>
                        @foreach ($objects as $object)
                        <option value="{{ $object->id }}">{{ $object->object }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Actor Requerente:') }}</strong> {{ $atorRequerente }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Proposito:') }}</strong> {{ $proposito }}</p>
                </div>
            </div>

        </div>

        <form method="POST" action="{{ route('createLoin2store') }}" id="loin-form">
            @csrf

            <div class="loin-container" id="loin-instances">
                <div class="tab">
                    <div class="row">
                        <div class="col-md-3">
                            <button class="tablinks" onclick="openData(event, 'geometrical')">Informações Geométrica</button>
                        </div>
                        <div class="col-md-3">
                            <button class="tablinks" onclick="openData(event, 'documentation')">Documentações</button>
                        </div>
                        <div class="col-md-3">
                            <button class="tablinks" onclick="openData(event, 'alphanumerical')">Informações Alfanuméricas</button>
                        </div>
                        <div class="col-md-3">
                            <button class="tablinks" onclick="openData(event, 'classification')">Classificação</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="projectId" name="projectId" value="{{ $projectId }}">
                <input type="hidden" id="nomeProjeto" name="nomeProjeto" value="{{ $nomeProjeto }}">
                <input type="hidden" id="atorFornecedor" name="atorFornecedor" value="{{ $atorFornecedor }}">
                <input type="hidden" id="atorRequerente" name="atorRequerente" value="{{ $atorRequerente }}">
                <input type="hidden" id="proposito" name="proposito" value="{{ $proposito }}">
                <input type="hidden" id="phase" name="phase" value="{{ $phase }}">
                <input type="hidden" name="userId" value="{{ auth()->id() }}">
                <input type="hidden" name="sistemaClassificacao" value="{{ $sistemaClassificacao }}">


                <div class="loin-instance">
                    <div id="classification" class="tabcontent">
                        <h3>{{ __('Classificação') }}</h3>
                        <p><strong>{{ __('Sistema de Classificação:') }}</strong> {{ $sistemaClassificacao }}</p>

                        <div class="form-group">
                            <label for="classificationTable"><strong>{{ __('Tabela de Classificação (opcional):') }}</strong></label>
                            <input type="text" id="classificationTable" name="classificationTable" class="form-control" placeholder="Inserir tabela de classificação">
                        </div>
                        <div class="form-group">
                            <label for="classificationCode"><strong>{{ __('Código de Classificação:') }}</strong></label>
                            <input type="text" id="classificationCode" name="classificationCode" class="form-control" placeholder="Inserir código OU (Requerido / Não requerido)">
                        </div>
                    </div>


                    <div id="geometrical" class="tabcontent">



                        <!-- Geometric Information -->
                        <h2>{{ __('Informações Geométricas') }}</h2>
                        <div class="form-group">
                            <label for="detalhe"><strong>{{ __('Detalhe') }}</strong></label>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="Não requerido" class="form-check-input" id="detalhe0" checked>
                                <label class="form-check-label" for="detalhe0">{{ __('Não requerido') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="Tamanho forma e dimensões aproximadas" class="form-check-input" id="detalhe1">
                                <label class="form-check-label" for="detalhe1">{{ __('Tamanho, forma e dimensões aproximadas') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="Geometria e orientação já definidas" class="form-check-input" id="detalhe2">
                                <label class="form-check-label" for="detalhe2">{{ __('Geometria e orientação já definidas') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="Detalhe de acabamentos superfícies inclinadas e aberturas já representadas" class="form-check-input" id="detalhe3">
                                <label class="form-check-label" for="detalhe3">{{ __('Detalhe de acabamentos, superfícies inclinadas e aberturas já representadas') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="As dimensões externas do elemento estão corretamente definidas" class="form-check-input" id="detalhe4">
                                <label class="form-check-label" for="detalhe4">{{ __('As dimensões externas do elemento estão corretamente definidas') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="detalhe[]" value="Conexões ligações e a representação de quaisquer regiões que possam ter impacto na coordenação com outros sistemas" class="form-check-input" id="detalhe5">
                                <label class="form-check-label" for="detalhe5">{{ __('Conexões, ligações e a representação de quaisquer regiões que possam ter impacto na coordenação com outros sistemas') }}</label>
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="dimensao"><strong>{{ __('Dimensao') }}</strong></label>
                            <select name="dimensao" id="dimensao" class="form-control" required>
                                <option value="Não requerido">Não requerido</option>
                                <option value="0D">0D</option>
                                <option value="1D">1D</option>
                                <option value="2D">2D</option>
                                <option value="3D">3D</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="localizacao"><strong>{{ __('Localizacao') }}</strong></label>
                            <select name="localizacao" id="localizacao" class="form-control" required>
                                <option value="Não requerido">Não requerido</option>
                                <option value="Absolute">Absolute</option>
                                <option value="Relative">Relative</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="aparencia"><strong>{{ __('Aparencia') }}</strong></label>
                            <select name="aparencia" id="aparencia" class="form-control" required>
                                <option value="Não requerido">Não requerido</option>
                                <option value="Aparência genérica">Aparência genérica</option>
                                <option value="Cor equivalente a sua visualização real, sem apresentar texturas">Cor equivalente a sua visualização real, sem apresentar texturas</option>
                                <option value="Aparência equivalente a visualização real, com textura equivalente ao material à vista">Aparência equivalente a visualização real, com textura equivalente ao material à vista</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="comportamentoParametrico"><strong>{{ __('Comportamento Paramétrico') }}</strong></label>
                            <select name="comportamentoParametrico" id="comportamentoParametrico" class="form-control" required>
                                <option value="Não requerido">Não requerido</option>
                                <option value="Totalmente ">Totalmente</option>
                                <option value="Parcialmente">Parcialmente</option>
                            </select>
                        </div>
                    </div>

                    <div id="documentation" class="tabcontent">
                        <!-- Documentation -->
                        <h2>{{ __('Documentação') }}</h2>

                        <!-- Table for Properties -->
                        <div class="table-container mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Nome do Documento') }}</th>
                                        <th>{{ __('Formato') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="documents_table_body">
                                    <!-- document and format will be populated here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Input and Button for Adding documents -->
                        <div class="input-container">
                            <input type="text" id="document_input" placeholder="{{ __('Enter Document Name') }}" style="margin-right: 130px">
                            <input type="text" id="format_input" placeholder="{{ __('Enter Format') }}" style="margin-right: 25px">
                            <button type="button" id="add_document_button" class="btn btn-secondary">{{ __('Adicionar documentos') }}</button>

                            <!-- Hidden input to store properties with source information -->
                            <input type="hidden" name="documents" id="documents">
                        </div>
                    </div>

                    <div id="alphanumerical" class="tabcontent">
                        <!-- Alphanumeric Information -->

                        <br>
                        <div class="text-center">
                            <h2>{{ __('Propriedades') }}</h2>
                        </div>
                        <br>
                        <div class="text-center mb-2">
                            <h4>{{ __('Procurar e adicionar propriedades a partir da lista ou adicionar novas propriedades manualmente') }}</h4>
                        </div>
                        <br>
                        <div class="input-container" style="width: 100%; position: relative;">
                            <input type="text" style="width: 100%; position: relative;" id="property_search_input" placeholder="Procurar propriedades">
                            <div id="property_search_dropdown" style="display: none; position: absolute; background: white; border: 1px solid #e0e0e0; max-height: 200px; overflow-y: auto; width: 100%;">
                                <!-- Search results will be appended here -->
                            </div>
                        </div>

                        <!-- Table for Properties -->
                        <div class="table-container mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Propriedades') }}</th>
                                        <th>{{ __('Grupos de propriedades') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="properties_table_body">
                                    <!-- Properties and groups will be populated here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Input and Button for Adding Properties -->
                        <div class="input-container">
                            <input type="text" id="property_input" placeholder="{{ __('Enter Property') }}" style="margin-right: 130px">
                            <input type="text" id="group_input" placeholder="{{ __('Enter Group of Properties') }}" style="margin-right: 25px">
                            <button type="button" id="add_property_button" class="btn btn-secondary">{{ __('Adicionar propriedade manualmente') }}</button>

                            <!-- Hidden input to store properties with source information -->
                            <input type="hidden" name="manual_properties" id="manual_properties">
                        </div>
                        <br>


                        <!-- PDT Properties -->
                        <div class="container mt-3" id="pdt_properties_container">
                            <div class="text-center mb-2">
                                <h4>
                                    {{ __('Propriedades de PDT: ') }}
                                </h4>
                                <select name="pdt" class="form-control" id="pdt-select" onchange="fetchPdtProperties(this.value)">
                                    <option value="" disabled selected>{{ __('Select a PDT') }}</option>
                                    @foreach($pdtslatestversions as $pdt)
                                    @if($pdt['pdtNameEn'] !== 'Master')
                                    <option value="{{ $pdt->Id }}">{{ $pdt->pdtNamePt }} ( {{ $pdt->pdtNameEn }} ) V {{ $pdt->editionNumber }}.{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>


                            <!-- Dynamic properties container -->
                            <div id="dynamic_pdt_properties"></div>
                        </div>

                        <!-- Master PDT Properties -->
                        <div class="container mt-3" id="master_properties_container">
                            <div class="text-center mb-2">
                                <h4>{{ __('Propriedades de "') }}{{ __('Master Data Template"') }}</h4>
                            </div>

                            @foreach($Mastergops as $gop)
                            @php
                            // Filter the properties by group of property (gopID)
                            $groupedProperties = $MasterProperties->where('gopID', $gop->Id);
                            @endphp
                            @if($groupedProperties->count() > 0)
                            <div class="gop-group" style="border: 1px solid #e0e0e0; border-radius: 5px; padding: 5px; background-color: #f9f9f9;">
                                <div class="d-flex align-items-center">
                                    <div class="gop-header d-flex align-items-center" style="cursor: pointer;" onclick="toggleProperties('master_properties_group_{{ $gop->Id }}')">
                                        <span class="clickable-indicator">▼</span>
                                        <h4 class="mr-2 mb-0">{{ $gop->gopNameEn }}</h4>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link select-all" data-gop="{{ $gop->Id }}" data-state="select">Select All</button>
                                </div>

                                <div class="scrollable-container" id="master_properties_group_{{ $gop->Id }}" style="display: none;">
                                    @foreach($groupedProperties as $property)
                                    @php
                                    // Get the corresponding property details from propertiesindd
                                    $PDTPropertyDetails = $propertiesindd->firstWhere('Id', $property->propertyId);

                                    // Get the property name
                                    $PDTPropertyName = $PDTPropertyDetails->nameEn ?? 'N/A';


                                    @endphp
                                    <div class="property-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                        <input type="checkbox" name="selected_master_pdt_properties[]" value="{{ $PDTPropertyName }},{{ $gop->gopNameEn }}" id="master_property_{{ $loop->parent->index }}_{{ $loop->index }}" data-gop="{{ $gop->Id }}" style="margin-right: 10px;">
                                        <label for="master_property_{{ $loop->parent->index }}_{{ $loop->index }}" style="cursor: pointer; display: flex; align-items: center;">
                                            <span class="property-description" title="{{ $PDTPropertyDetails->definitionEn ?? 'N/A' }}" style="margin-right: 5px;">{{ $PDTPropertyName }}</span>
                                        </label>
                                    </div>

                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        <br>
                        <!-- IFC Properties -->
                        <div class="text-center">
                            <h2>{{ __('Definições do objeto em IFC') }}</h2>
                            <br>
                        </div>
                        <div class="row">

                            <div class="col-md-6">
                                <label for="ifcClassName"><strong>{{ __('Selecionar Classe IFC:') }}</strong></label>
                                <select name="ifcClasses[]" class="form-control ifc-class-select" style="width: 100%;" onchange="fetchIfcProperties(this.value)">
                                    <option value="" disabled selected>{{ __('Selecionar Classe IFC') }}</option>
                                    @foreach($ifcClasses as $ifcClass)
                                    <option value="{{ $ifcClass['id'] }}">{{ $ifcClass['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="ifcClassName"><strong>{{ __('Nome da Classe IFC:') }}</strong></label>
                                <select name="ifcClassName" id="ifcClassName" class="form-control">
                                    <option value="Requerido">Requerido</option>
                                    <option value="Não requerido" selected>Não requerido</option> <!-- Default to Não requerido -->
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row">

                            <div class="col-md-6">
                                <label for="ifcClassDescription"><strong>{{ __('Descrição da Classe IFC:') }}</strong></label>
                                <select name="ifcClassDescription" id="ifcClassDescription" class="form-control">
                                    <option value="Requerido">Requerido</option>
                                    <option value="Não requerido" selected>Não requerido</option> <!-- Default to Não requerido -->
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="ifcClassPredefinedType"><strong>{{ __('Tipo Predefinido da Classe IFC:') }}</strong></label>
                                <select name="ifcClassPredefinedType" id="ifcClassPredefinedType" class="form-control">
                                    <option value="Requerido">Requerido</option>
                                    <option value="Não requerido" selected>Não requerido</option> <!-- Default to Não requerido -->
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="container mt-1" id="ifc_properties_container">


                            <div id="dynamic_ifc_properties">

                            </div>


                            <br>
                            <div class="text-center">
                                <h2>{{ __('Materiais') }}</h2>
                            </div>
                            <div>
                                <label for="materialName"><strong>{{ __('Nome do IfcMaterial:') }}</strong></label>
                                <select name="materialName" id="materialName" class="form-control" required>
                                    <option value="Requerido">Requerido</option>
                                    <option value="Não requerido" selected>Não requerido</option> <!-- Default to Não requerido -->
                                </select>
                            </div>

                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary" style="color: black;">{{ __('Guardar LOIN') }}</button>
                    </div>
        </form>
    </div>
    <!-- Existing LOIN Records -->
    <h2>{{ __('Níveis de Necessidade de Informação do projeto :') }}{{ $nomeProjeto }}</h2> <a href="{{ route('loinViewProject', $projectId) }}" class="btn btn-primary" style="color: black;">{{ __('Ver/discarregar LOINs do projeto') }}</a>
    @if($loins->count() > 0)

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>{{ __('Nome de objeto') }}</th>
                <th>{{ __('Proposito') }}</th>
                <th>{{ __('Actor Fornecedor') }}</th>
                <th>{{ __('Actor Requerente') }}</th>
                <th>{{ __('Fase de Projeto') }}</th>
                <th>{{ __('Acções') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loins as $loin)
            <tr>
                <td>{{ $loin->objectName }}</td>
                <td>{{ $loin->purpose }}</td>
                <td>{{ $loin->actorProviding }}</td>
                <td>{{ $loin->actorRequesting }}</td>
                <td>{{ $loin->milestone }}</td>
                <td>
                    <a href="{{ route('loinView', $loin->id) }}" class="btn btn-primary" style="color: black;">{{ __('Ver/Descarregar/Apagar') }}</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>{{ __('Não foram encontradas entradas para Níveis de Necessidade de Informação.') }}</p>
    @endif
    <!-- Blade template -->
    <input type="hidden" id="masterPropertiesData" value="{{ json_encode($masterPropertiesArray) }}">

    <!-- JavaScript -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        //start array for properties for search bar and add Master data
        let allPropertiesInPage = [];

        function addMasterProperties() {

            const hiddenInput = document.getElementById('masterPropertiesData');
            const masterPropertiesArray = JSON.parse(hiddenInput.value);

            masterPropertiesArray.forEach(property => {
                if (!allPropertiesInPage.some(p => p.name === property.name && p.group === property.group)) {
                    allPropertiesInPage.push({
                        name: property.name,
                        group: property.group,
                        description: property.description,
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', addMasterProperties);


        function addPropertyToTable(property) {
            const tableBody = document.getElementById('properties_table_body');
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${property.name}</td>
            <td>${property.group}</td>
        `;
            tableBody.appendChild(row);
        }


        // Function to add selected property to the table and hidden input
        function addSelectedProperty(property, group) {
            // Update manualProperties array
            manualProperties.push({
                property: property,
                group: group
            });

            // Update hidden input
            document.getElementById('manual_properties').value = JSON.stringify(manualProperties);

            // Append new row to the properties table
            const tableBody = document.getElementById('properties_table_body');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${property}</td><td>${group}</td>`;
            tableBody.appendChild(newRow);
        }

        // Close dropdown if clicking outside
        document.addEventListener('click', function(e) {
            if (!propertySearchInput.contains(e.target) && !propertySearchDropdown.contains(e.target)) {
                propertySearchDropdown.style.display = 'none';
            }
        });

        //toggle visibility of gops

        function toggleProperties(containerId) {
            const container = document.getElementById(containerId);
            const indicator = container.previousElementSibling.querySelector('.clickable-indicator');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                indicator.textContent = '▲'; // Change indicator to up
            } else {
                container.style.display = 'none';
                indicator.textContent = '▼'; // Change indicator to down
            }
        }

        // group properties
        // Generic function to handle "Select All" functionality
        function initializeSelectAllButtons() {
            const selectAllButtons = document.querySelectorAll('.select-all');

            selectAllButtons.forEach(function(button) {
                button.removeEventListener('click', handleSelectAll); // Ensure no duplicate listeners
                button.addEventListener('click', handleSelectAll); // Attach event listener
            });
        }

        // Handler function for "Select All"
        function handleSelectAll() {
            const gopId = this.getAttribute('data-gop');
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-gop="${gopId}"]`);
            const state = this.getAttribute('data-state');

            if (state === 'select') {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
                this.setAttribute('data-state', 'deselect');
                this.textContent = 'Deselect All';
            } else {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                this.setAttribute('data-state', 'select');
                this.textContent = 'Select All';
            }
        }




        //tabs
        function openData(evt, dataName) {
            evt.preventDefault(); // Prevent the form submission behavior
            var i, tabcontent, tablinks;

            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            document.getElementById(dataName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Store manual properties in an array
        let manualProperties = [];

        document.getElementById('add_property_button').addEventListener('click', function() {
            const propertyInput = document.getElementById('property_input').value.trim();
            const groupInput = document.getElementById('group_input').value.trim();

            if (propertyInput === '' || groupInput === '') {
                alert('Both fields are required.');
                return;
            }

            // Add new property to manualProperties array
            manualProperties.push({
                property: propertyInput,
                group: groupInput
            });

            // Update hidden input field with manual properties
            document.getElementById('manual_properties').value = JSON.stringify(manualProperties);

            // Append new row to the table
            const tableBody = document.getElementById('properties_table_body');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
            <td>${propertyInput}</td>
            <td>${groupInput}</td>
        `;
            tableBody.appendChild(newRow);

            // Clear input fields
            document.getElementById('property_input').value = '';
            document.getElementById('group_input').value = '';
        });

        // Add Documents Script

        const documents = [];
        document.getElementById('add_document_button').addEventListener('click', function() {
            const docName = document.getElementById('document_input').value;
            const format = document.getElementById('format_input').value;

            if (docName && format) {
                documents.push({
                    document: docName,
                    format: format
                });

                // Update the table body
                const tableBody = document.getElementById('documents_table_body');
                const newRow = document.createElement('tr');
                newRow.innerHTML = `<td>${docName}</td><td>${format}</td>`;
                tableBody.appendChild(newRow);

                // Update the hidden field for documents
                document.getElementById('documents').value = JSON.stringify(documents);

                // Clear input fields
                document.getElementById('document_input').value = '';
                document.getElementById('format_input').value = '';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initializeSelectAllButtons(); // Initialize for static content

        });
        // Group Checkbox Script 

        document.querySelectorAll('.property-group-checkbox').forEach(function(groupCheckbox) {
            groupCheckbox.addEventListener('change', function() {
                const groupId = this.getAttribute('data-group-id');
                const checkboxes = document.querySelectorAll('.property-checkbox[data-group-id="' + groupId + '"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = groupCheckbox.checked;
                });
            });
        });
        //IFC and pdt search

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 for IFC class dropdown
            $('.ifc-class-select').select2({
                placeholder: "{{ __('Selecionar IFC Class') }}",
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for PDT dropdown
            $('#pdt-select').select2({
                placeholder: "{{ __('Selecionar PDT') }}",
                allowClear: true,
                width: '100%'
            });
        });

        //fetch pdt data
        function fetchPdtProperties(pdtId) {
            if (!pdtId) {
                console.error('Invalid PDT ID');
                return;
            }

            const container = document.getElementById('dynamic_pdt_properties');
            container.innerHTML = '<p>Loading properties...</p>';

            fetch(`/fetch-pdt-properties/${pdtId}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    container.innerHTML = ''; // Clear existing content

                    if (data.success && data.groupedProperties && Object.keys(data.groupedProperties).length > 0) {
                        for (const [propertyGroup, properties] of Object.entries(data.groupedProperties)) {
                            const safePropertyGroup = propertyGroup.replace(/[^a-zA-Z0-9_-]/g, '_');
                            const propertiesArray = Object.values(properties);

                            const groupHTML = `
                        <div class="pdt-group" id="group_${safePropertyGroup}" style="border: 1px solid #e0e0e0; border-radius: 5px; padding: 5px; background-color: #f9f9f9;">
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center" style="cursor: pointer;" onclick="toggleProperties('pdt_group_${safePropertyGroup}')">
                                    <span class="clickable-indicator">▼</span>
                                    <h4 class="mr-2 mb-0">${propertyGroup}</h4>
                                </div>
                                <button type="button" class="btn btn-sm btn-link select-all" data-gop="${safePropertyGroup}" data-state="select">Select All</button>
                            </div>
                            <div class="scrollable-container" id="pdt_group_${safePropertyGroup}" style="display: none;">
                                ${propertiesArray.map(property => `
                                    <div class="property-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                        <input type="checkbox" name="selected_pdt_properties[]" value="${property.name},${property.group}" id="pdt_property_${property.name}_${safePropertyGroup}" data-gop="${safePropertyGroup}">
                                        <label for="pdt_property_${property.name}_${safePropertyGroup}" style="cursor: pointer; display: flex; align-items: center;">
                                            <span title="${property.description || 'No description'}" style="margin-right: 5px;">
                                                ${property.name}
                                            </span>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;

                            container.innerHTML += groupHTML;

                            // Add properties to the global array without duplicates
                            if (Array.isArray(properties)) {
                                properties.forEach(property => {
                                    {
                                        allPropertiesInPage.push({
                                            name: property.name,
                                            group: property.group,
                                            description: property.description || 'No description',
                                        });
                                    }
                                });
                            }
                        }
                    } else {
                        container.innerHTML = '<p>No properties found for this PDT.</p>';
                    }

                    initializeSelectAllButtons(); // Reinitialize the buttons
                    console.log('Updated allPropertiesInPage:', allPropertiesInPage); // Debugging log
                })
                .catch(error => {
                    console.error('Error fetching PDT properties:', error);
                    container.innerHTML = '<p>Error fetching properties. Please try again later.</p>';
                });
        }



        // fetch ifc properties
        function fetchIfcProperties(ifcClass) {
            if (!ifcClass) return;

            // Show a loading indicator (optional)
            const container = document.getElementById('dynamic_ifc_properties');
            container.innerHTML = `
              <div class="text-center mb-2">
              <h4>{{ __('Propriedades IFC') }}</h4>
              </div>
              <p>Loading properties...</p>`;
            // Make an AJAX request to fetch properties for the selected IFC class
            fetch(`/fetch-ifc-properties/${ifcClass}`)
                .then(response => response.json())
                .then(data => {
                    // Clear existing content
                    container.innerHTML = '';

                    if (data.length > 0) {
                        // Group properties by Property Set
                        const groupedProperties = data.reduce((groups, property) => {
                            const group = property.propertySet || 'N/A';
                            if (!groups[group]) {
                                groups[group] = [];
                            }
                            groups[group].push(property);
                            return groups;
                        }, {});

                        // Generate the HTML dynamically
                        for (const [propertySet, properties] of Object.entries(groupedProperties)) {
                            const groupHTML = `
                        <div class="gop-group" style="border: 1px solid #e0e0e0; border-radius: 5px; padding: 5px; background-color: #f9f9f9;">
                            <div class="d-flex align-items-center">
                            <div class="d-flex align-items-center" style="cursor: pointer;" onclick="toggleProperties('ifc_group_${propertySet}')">
                                <span class="clickable-indicator">▼</span>
                                <h4 class="mr-2 mb-0">${propertySet}</h4>
                            </div>
                            <button type="button" class="btn btn-sm btn-link select-all" data-gop="${propertySet}" data-state="select">Select All</button>
                        </div>
                               
                            <div class="scrollable-container" id="ifc_group_${propertySet}" style="display: none;">
                                ${properties.map(property => `
                                    <div class="property-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                        <input type="checkbox" name="selected_ifc_properties[]" value="${property.propertyName},${property.propertySet}" id="ifc_property_${property.propertyName}" data-gop="${propertySet}">
                                        <label for="ifc_property_${property.propertyName}" style="cursor: pointer; display: flex; align-items: center;">
                                            <span title="${property.propertyDescription}" style="margin-right: 5px;">
                                                ${property.propertyName}
                                            </span>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                            container.innerHTML += groupHTML;
                            // Add properties to `allPropertiesInPage`
                            properties.forEach(property => {
                                allPropertiesInPage.push({
                                    name: property.propertyName,
                                    group: property.propertySet,
                                    description: property.propertyDescription,
                                });
                            });
                        }
                    }


                    initializeSelectAllButtons(); // Reinitialize the "Select All" buttons

                })

                .catch(error => {
                    console.error('Error fetching IFC properties:', error);
                    container.innerHTML = `
                <div class="text-center mb-2">
                    <h4>{{ __('Propriedades IFC') }}</h4>
                </div>
                <p>Error fetching properties. Please try again later.</p>
            `;
                });
        }

        //search bar
        document.getElementById('property_search_input').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const dropdown = document.getElementById('property_search_dropdown');
            dropdown.innerHTML = ''; // Clear previous results

            if (query.length === 0) {
                dropdown.style.display = 'none'; // Hide dropdown if input is empty
                return; // Exit early if there is no query
            }

            const searchResults = allPropertiesInPage.filter(property =>
                property.name.toLowerCase().includes(query)
            );

            if (searchResults.length > 0) {
                dropdown.style.display = 'block';
                searchResults.forEach(property => {
                    const resultItem = document.createElement('div');
                    resultItem.classList.add('dropdown-item');
                    resultItem.textContent = `${property.name} (${property.group})`; // Show group next to name

                    // Show the description on hover
                    resultItem.title = property.description; // Using title attribute for tooltip effect

                    resultItem.addEventListener('click', function() {
                        addPropertyToTable(property);
                        dropdown.style.display = 'none';
                    });
                    dropdown.appendChild(resultItem);
                });
            } else {
                dropdown.style.display = 'none'; // Hide dropdown if no results found
            }
        });
    </script>


</x-app-layout>