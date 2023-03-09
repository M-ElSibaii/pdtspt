<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Ver e descarregar o Modelo de Dados do Produto baseado na EN ISO 23387") }}
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="home_content container">
                    <div class="row">
                        <h2> O Modelo de Dados do Produto ({{ $pdt[0]->pdtNamePt }}) V{{ $pdt[0]->versionNumber }}.{{ $pdt[0]->revisionNumber }}

                            <button id="json" class="btn"><i class="fa fa-download"></i> JSON</button>
                            <button id="csv" class="btn"><i class="fa fa-download"></i> CSV/XLS</button>
                            <button id="txt" class="btn"><i class="fa fa-download"></i> TXT</button>

                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="tblpdts" cellpadding="0" cellspacing="0">
                            <tr>
                                <th style="width: 15%;">Grupo de propriedades</th>
                                <th>Propriedade </th>
                                <th style="width: 7%;">Unidade</th>
                                <th style="width: 40%;">Descrição</th>
                                <th style="width: 16%;">Documento de referência</th>
                            </tr>
                            @foreach($gop as $group)

                            @foreach($joined_properties as $property)

                            @if($property->gopID == $group->Id)

                            <tr>
                                <td>
                                    {{ $group->gopNamePt }}
                                </td>
                                <td>
                                    <a href="{{ route('datadictionaryview', ['propID' => $property->GUID , 'propV' => $property->versionNumber, 'propR' => $property->revisionNumber]) }}">{{ $property->namePt }}</a>
                                </td>
                                <td>
                                    {{ $property->units }}
                                </td>
                                <td>
                                    <div class="row">
                                        <p>{{$property->descriptionPt}}</p>
                                        @if($property->visualRepresentation == True)
                                        <div class="col-sm">
                                            <img src="{{ asset ('img/'.$property->nameEn.'.png')}}" alt='{{$property->nameEn}}' class="property-image">
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                @if ($referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName == 'n/a')
                                <td>
                                    <a>{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}</abbr></a>
                                </td>
                                @else
                                <td>
                                    <a href="{{ route('referencedocumentview', ['rdGUID' => $property->referenceDocumentGUID]) }}">
                                        <abbr title="{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->title }}">{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}</abbr>
                                    </a>
                                </td>
                                @endif
                            </tr>
                            @endif
                            @endforeach
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
    <script src="https://rawcdn.githack.com/FuriosoJack/TableHTMLExport/v2.0.0/src/tableHTMLExport.js"></script>


    <script>
        $("#json").on("click", function() {
            $("#tblpdts").tableHTMLExport({
                type: "json",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.json",
            });
        });
        $("#csv").on("click", function() {
            $("#tblpdts").tableHTMLExport({
                type: "csv",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.csv"
            });
        });

        $("#txt").on("click", function() {
            $("#tblpdts").tableHTMLExport({
                type: "txt",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.txt"
            });
        });
    </script>


</x-app-layout>