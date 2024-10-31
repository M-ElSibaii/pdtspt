<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ __('Visualizar Nível de Necessidade de Informação') }}</h1>
            <div class='py-6'>
                <div class="flex flex-row gap-2 py-4">
                    <a href="{{ route('loinDownloadJSON', $loindata->id, $loindata->objectName) }}" class="btn btn-secondary" style="color: black;">
                        <i class="fa fa-download"></i>&nbsp;JSON
                    </a>
                    <a href="{{ route('loinDownloadExcel', ['id' => $loindata->id, 'objectName' => $loindata->objectName]) }}" class="btn btn-secondary" style="color: black;">
                        <i class="fa fa-download"></i>&nbsp;CSV/XLS
                    </a>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th style="background-color: #f0f0f0; ">Nome de Projeto</th>
                            <td>{{$loindata->projectName}}</td>
                        </tr>
                        <tr>
                            <th>Objeto</th>
                            <td>{{$loindata->objectName}}</td>
                        </tr>
                        <tr>
                            <th>PDT Nome</th>
                            <td>{{$loindata->pdtName}}</td>
                        </tr>
                        <tr>
                            <th>Ator Fornecedor</th>
                            <td>{{$loindata->actorProviding}}</td>
                        </tr>
                        <tr>
                            <th>Ator Requerente</th>
                            <td>{{$loindata->actorRequesting}}</td>
                        </tr>


                        <tr>
                            <th>Fase de Projeto</th>
                            <td>{{$loindata->milestone}}</td>
                        </tr>
                        <tr>
                            <th>Propósito</th>
                            <td>{{$loindata->purpose}}</td>
                        </tr>

                        <!-- Add Geometrical Properties Section Header -->
                        <tr style="background-color: #f0f0f0; font-weight: bold;">
                            <th colspan="2" style="font-weight: bold;text-align: center;">Geometrical data</th>
                        </tr>
                        <tr>
                            <th>Detalhe</th>
                            <td>{{$loindata->detail}}</td>
                        </tr>
                        <tr>
                            <th>Dimensão</th>
                            <td>{{$loindata->dimension}}</td>
                        </tr>
                        <tr>
                            <th>Localização</th>
                            <td>{{$loindata->location}}</td>
                        </tr>
                        <tr>
                            <th>Aparência</th>
                            <td>{{$loindata->appearance}}</td>
                        </tr>
                        <tr>
                            <th>Comportamento Paramétrico</th>
                            <td>{{$loindata->parametricBehaviour}}</td>
                        </tr>

                        <!-- Add Alphanumerical Properties Section Header -->
                        <tr style="background-color: #f0f0f0; ">
                            <th colspan="2" style="font-weight: bold;text-align: center;">Alphanumerical data</th>
                        </tr>
                        <tr>
                            <th>IFC Class Name</th>
                            <td>{{$loindata->ifcClassName}}</td>
                        </tr>
                        <tr>
                            <th>IFC Class Description</th>
                            <td>{{$loindata->ifcClassDescription}}</td>
                        </tr>
                        <tr>
                            <th>IFC Class PredefinedType</th>
                            <td>{{$loindata->ifcClassPredefinedType}}</td>
                        </tr>

                        <tr>
                            <th>Sistema de classificação</th>
                            <td>{{$loindata->classificationSystem}}</td>
                        </tr>
                        <tr>
                            <th>Tabela de classificação</th>
                            <td>{{$loindata->classificationTable}}</td>
                        </tr>
                        <tr>
                            <th>Código de classificação</th>
                            <td>{{$loindata->classificationCode}}</td>
                        </tr>
                        <tr>
                            <th>IfcMaterial Name</th>
                            <td>{{$loindata->materialName}}</td>
                        </tr>
                        <tr>
                            <th>Propriedades</th>
                            <td>
                                <table class="table table-bordered" style="border: 1px solid black;">
                                    <thead>
                                        <tr style="border: 1px solid black;">
                                            <th style="text-align: left;background-color: #f0f0f0;">{{ ('Propriedade') }}</th>
                                            <th style="text-align: left;background-color: #f0f0f0;">{{ ('Grupo de propriedade') }}</th>
                                            <th style="text-align: left;background-color: #f0f0f0;">{{ ('Fonte') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $properties = json_decode($loindata->properties, true);
                                        @endphp
                                        @foreach($properties as $property)
                                        <tr style="border: 1px solid black;">
                                            <td>{{ $property['property'] }}</td>
                                            <td>{{ $property['group'] }}</td>
                                            <td>{{ $property['source'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                        <tr style="background-color: #f0f0f0; ">
                            <th colspan="2" style="font-weight: bold;text-align: center;">Documentacao</th>
                        </tr>
                        <table class="table table-bordered" style="border: 1px solid black;">

                            @php
                            // Decode the JSON document field
                            $documents = json_decode($loindata->documentation, true);
                            @endphp

                            @if (is_array($documents))
                            <!-- If documents is an array, display the table -->
                            <thead>
                                <tr style="border: 1px solid black;">
                                    <th style="text-align: left;background-color: #f0f0f0;">{{ ('Document') }}</th>
                                    <th style="text-align: left;background-color: #f0f0f0;">{{ ('Format') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $document)

                                <tr style="border: 1px solid black;">
                                    <td>{{ $document['document'] }}</td>
                                    <td>{{ $document['format'] }}</td>
                                </tr>
                                @endforeach
                                @else
                                <!-- If documents is not an array, just show the string "Não requerido" -->
                                <tr>
                                    <td>{{ $loindata->documentation }}</td>
                                </tr>
                                @endif

                            </tbody>

                        </table>

                        <div class="mt-4">
                            <form action="{{ route('loinDelete', $loindata->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="color: black;">
                                    {{ __('Delete LOIN') }}
                                </button>
                            </form>
                        </div>
            </div>
        </div>
    </div>

</x-app-layout>