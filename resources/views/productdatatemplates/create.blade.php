<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ __('Create PDTs') }}</h1>
                <!-- resources/views/productdatatemplates/create.blade.php -->

                <form method="POST" action="{{ route('productdatatemplates.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="pdtNameEn">{{ __('PDT Name (English)') }}</label>
                        <input type="text" class="form-control" id="pdtNameEn" name="pdtNameEn" required>
                    </div>

                    <div class="form-group">
                        <label for="pdtNamePt">{{ __('PDT Name (Portuguese)') }}</label>
                        <input type="text" class="form-control" id="pdtNamePt" name="pdtNamePt" required>
                    </div>

                    <div class="form-group">
                        <label for="status">{{ __('Status') }}</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="Preview">Preview</option>
                            <!--<option value="Active">Active</option>
                            <option value="InActive">InActive</option>
                            
-->
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="category">{{ __('Category') }}</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="Construção" selected>Construção</option>
                            <option value="Material de Construção">Material de Construção</option>
                            <option value="Obras Geotécnicas">Obras Geotécnicas</option>
                            <option value="Escavação e Estabilização">Escavação e Estabilização</option>
                            <option value="Fundação e Estacas">Fundação e Estacas</option>
                            <option value="Estruturas de Retenção de Terra">Estruturas de Retenção de Terra</option>
                            <option value="Betão">Betão</option>
                            <option value="Aço">Aço</option>
                            <option value="Estruturas de Madeira">Estruturas de Madeira</option>
                            <option value="Alvenaria e Tijolo">Alvenaria e Tijolo</option>
                            <option value="Materiais Compostos e Especializados">Materiais Compostos e Especializados</option>
                            <option value="Paredes">Paredes</option>
                            <option value="Telhados">Telhados</option>
                            <option value="Revestimento">Revestimento</option>
                            <option value="Isolamento">Isolamento</option>
                            <option value="Janelas">Janelas</option>
                            <option value="Portas">Portas</option>
                            <option value="Divisórias">Divisórias</option>
                            <option value="Tetos">Tetos</option>
                            <option value="Pisos">Pisos</option>
                            <option value="Tinta">Tinta</option>
                            <option value="Revestimentos de Parede">Revestimentos de Parede</option>
                            <option value="Sanitário">Sanitário</option>
                            <option value="Cozinha">Cozinha</option>
                            <option value="Ferrovias">Ferrovias</option>
                            <option value="Vias Rodoviárias">Vias Rodoviárias</option>
                            <option value="Sistemas de HVAC">Sistemas de HVAC</option>
                            <option value="Sistemas Elétricos">Sistemas Elétricos</option>
                            <option value="Plumbing">Plumbing</option>
                            <option value="Proteção Contra Incêndio">Proteção Contra Incêndio</option>
                            <option value="Serviços Civis e de Utilidade">Serviços Civis e de Utilidade</option>
                            <option value="Infraestrutura de TI">Infraestrutura de TI</option>
                            <option value="Obras e Paisagismo">Obras e Paisagismo</option>
                            <option value="Sistemas de Segurança e Proteção">Sistemas de Segurança e Proteção</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="revisionNumber">{{ __('editionNumber') }}</label>
                        <input type="text" class="form-control" id="editionNumber" name="editionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="versionNumber">{{ __('versionNumber') }}</label>
                        <input type="text" class="form-control" id="versionNumber" name="versionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="revisionNumber">{{ __('revisionNumber') }}</label>
                        <input type="text" class="form-control" id="revisionNumber" name="revisionNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="GUID">{{ __('GUID') }}</label>
                        <input type="text" class="form-control" id="GUID" name="GUID" required>
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
                        <textarea class="form-control" id="descriptionEn" name="descriptionEn" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="descriptionPt">{{ __('Description (Portuguese)') }}</label>
                        <textarea class="form-control" id="descriptionPt" name="descriptionPt" rows="3"></textarea>
                    </div>

                    <!-- Date fields -->

                    <div class="form-group">
                        <label for="dateOfEdition">{{ __('Date of Edition') }}</label>
                        <input type="text" class="form-control" id="dateOfEdition" name="dateOfEdition" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfRevision">{{ __('Date of Revision') }}</label>
                        <input type="text" class="form-control" id="dateOfRevision" name="dateOfRevision" value="{{ now() }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="dateOfVersion">{{ __('Date of Version') }}</label>
                        <input type="text" class="form-control" id="dateOfVersion" name="dateOfVersion" value="{{ now() }}" readonly>
                    </div>

                    <!-- Add all other fields based on the PDT table -->
                    <!-- Note: Make sure the input names match the column names in the database -->
                    <x-primary-button type="submit">
                        Add PDT
                    </x-primary-button>
                </form>


            </div>
        </div>
    </div>
</x-app-layout>