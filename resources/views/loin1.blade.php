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
                    <p><strong>{{ __('PDT Nome:') }}</strong>{{ $pdts ? $pdts->pdtNameEn : 'n/a' }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>{{ __('Objeto / Nome:') }}</strong> {{ $nomeObjeto }} / {{ $nome }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Actor Requerente:') }}</strong> {{ $atorRequerente }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('IFC Class:') }}</strong> {{ $ifcElement }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>{{ __('Sistema de classificação:') }}</strong> {{ $sistemaClassificacao }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Tabela de classificação:') }}</strong> {{ $tabelaClassificacao }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>{{ __('Código de classificação:') }}</strong> {{ $codigoClassificacao }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('loin.store') }}" id="loin-form">
            @csrf

            <div class="loin-container" id="loin-instances">
                <input type="hidden" id="nomeProjeto" name="nomeProjeto" value="{{ $nomeProjeto }}">
                <input type="hidden" id="atorFornecedor" name="atorFornecedor" value="{{ $atorFornecedor }}">
                <input type="hidden" id="atorRequerente" name="atorRequerente" value="{{ $atorRequerente }}">
                <input type="hidden" id="ifcElement" name="ifcElement" value="{{ $ifcElement }}">
                <input type="hidden" id="nomeObjeto" name="nomeObjeto" value="{{ $nomeObjeto }}">
                <input type="hidden" id="nome" name="nome" value="{{ $nome }}">
                <input type="hidden" id="pdtName" name="pdtName" value="{{ $pdts ? $pdts->pdtNameEn : 'n/a' }}">
                <input type="hidden" name="userId" value="{{ auth()->id() }}">
                <input type="hidden" name="sistemaClassificacao" value="{{ $sistemaClassificacao }}">
                <input type="hidden" name="tabelaClassificacao" value="{{ $tabelaClassificacao }}">
                <input type="hidden" name="codigoClassificacao" value="{{ $codigoClassificacao }}">


                <div class="loin-instance">
                    <!-- Form Fields -->
                    <div class="form-group">
                        <label for="faseDeProjeto"><strong>{{ __('Fase de Projeto') }}</strong></label>
                        <input type="text" name="faseDeProjeto" id="faseDeProjeto" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="proposito"><strong>{{ __('Proposito') }}</strong></label>
                        <input type="text" name="proposito" id="proposito" class="form-control" required>
                    </div>

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

                    <!-- Documentation -->
                    <h2>{{ __('Documentação') }}</h2>
                    <div class="form-group">
                        <input type="text" name="documentacao" id="documentacao" class="form-control" placeholder="{{ __('Não requerido') }}" value="{{ old('documentacao', 'Não requerido') }}">
                    </div>

                    <!-- Alphanumeric Information -->
                    <h2>{{ __('Informações Alfanuméricas') }}</h2>

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

                    <!-- IFC Properties -->
                    <div class="container mt-3" id="ifc_properties_container">
                        <h3>Selecionar propriedades dos conjuntos de propriedades de {{$ifcElement}}</h3>
                        <div class="scrollable-container">
                            @foreach ($ifcProperties as $property)
                            <div class="property-item">
                                <input type="checkbox" name="selected_ifc_properties[]" value="{{ $property['propertyName'] }},{{ $property['propertySet'] }}" id="ifc_property_{{ $loop->index }}">
                                <label for="ifc_property_{{ $loop->index }}">
                                    {{ $property['propertyName'] }} --- ({{ $property['propertySet'] }})
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- PDT Properties -->
                    @if($pdts)
                    <div class="container mt-3" id="pdt_properties_container">
                        <h3>{{ __('Propriedades de "') }}{{ $pdts->pdtNameEn }}{{ __(' Data Template"') }}</h3>
                        <div class="scrollable-container">
                            @foreach($properties as $property)
                            @php
                            $PDTPropertyName = $propertiesindd->firstWhere('Id', $property->propertyId)->nameEn ?? 'N/A';
                            $groupOfPropertyName = $gops->firstWhere('Id', $property->gopID)->gopNameEn ?? 'N/A';
                            @endphp
                            <div class="property-item">
                                <input type="checkbox" name="selected_pdt_properties[]" value="{{ $PDTPropertyName }},{{ $groupOfPropertyName }}" id="pdt_property_{{ $loop->index }}">
                                <label for="pdt_property_{{ $loop->index }}">
                                    {{ $PDTPropertyName }} --- ({{ $groupOfPropertyName }})
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Master PDT Properties -->
                    <div class="container mt-3" id="master_pdt_properties_container">
                        <h3>{{ __('Propriedades de "Master Data Template"') }}</h3>
                        <div class="scrollable-container">
                            @foreach($MasterProperties as $property)
                            @php
                            $PDTPropertyName = $propertiesindd->firstWhere('Id', $property->propertyId)->nameEn ?? 'N/A';
                            $groupOfPropertyName = $Mastergops->firstWhere('Id', $property->gopID)->gopNameEn ?? 'N/A';
                            @endphp
                            <div class="property-item">
                                <input type="checkbox" name="selected_master_pdt_properties[]" value="{{ $PDTPropertyName }},{{ $groupOfPropertyName }}" id="master_property_{{ $loop->index }}">
                                <label for="master_property_{{ $loop->index }}">
                                    {{ $PDTPropertyName }} --- ({{ $groupOfPropertyName }})
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-secondary">{{ __('Guardar LOIN e adicionar outra instância para o mesmo projeto') }}</button>
                    </div>

                </div>
            </div>
        </form>

        <!-- Existing LOIN Records -->
        @if($loins->count() > 0)
        <h2>{{ __('Níveis de Necessidade de Informação do projeto') }}</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>{{ __('Nome de Projeto') }}</th>
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
                    <td>{{ $loin->projectName }}</td>
                    <td>{{ $loin->objectName }}</td>
                    <td>{{ $loin->purpose }}</td>
                    <td>{{ $loin->actorProviding }}</td>
                    <td>{{ $loin->actorRequesting }}</td>
                    <td>{{ $loin->projectPhase }}</td>
                    <td>
                        <a href="{{ route('loinView', $loin->id) }}" class="btn btn-primary">{{ __('Ver/Editar') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>{{ __('Não foram encontradas entradas para Níveis de Necessidade de Informação.') }}</p>
        @endif

        <!-- JavaScript -->
        <script>
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
        </script>

    </div>
</x-app-layout>