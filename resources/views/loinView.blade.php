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
                            <th>IFC Class</th>
                            <td>{{$loindata->ifcElement}}</td>
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
                            <td>{{$loindata->projectPhase}}</td>
                        </tr>
                        <tr>
                            <th>Propósito</th>
                            <td>{{$loindata->purpose}}</td>
                        </tr>

                        <!-- Add Geometrical Properties Section Header -->
                        <tr style="background-color: #f0f0f0; font-weight: bold;">
                            <th colspan="2" style="font-weight: bold;text-align: center;">Geometrical Properties</th>
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
                            <th colspan="2" style="font-weight: bold;text-align: center;">Alphanumerical Properties</th>
                        </tr>
                        <tr>
                        <tr>
                            <th>Nome</th>
                            <td>{{$loindata->name}}</td>
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
                            <th style="background-color: #f0f0f0; ">Documentação</th>
                            <td>{{$loindata->documentation}}</td>
                        </tr>
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