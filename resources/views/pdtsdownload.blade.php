<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ __("Modelo de Dados do Produto baseado na EN ISO 23387") }}</h1>
            <div class="py-9">
                <div class="flex flex-row">
                    <div class="grow block">
                        <div class="flex-none inline">
                            <h1 class="flex-none inline">{{ $pdt[0]->pdtNamePt }}</h1>
                            <p class="flex-none inline"> - V{{ $pdt[0]->versionNumber }}.{{ $pdt[0]->revisionNumber }}</p>
                        </div>
                    </div>
                    <div class="flex flex-row gap-2 py-4">
                        <x-secondary-button id="json" class="btn">
                            <i class="fa fa-download"></i>&nbsp;JSON
                        </x-secondary-button>
                        <x-secondary-button id="csv" class="btn">
                            <i class="fa fa-download"></i>&nbsp;CSV/XLS
                        </x-secondary-button>
                        <x-secondary-button id="txt" class="btn">
                            <i class="fa fa-download"></i>&nbsp;TXT
                        </x-secondary-button>
                    </div>
                </div>
                <div>
                    <table class="table-auto" id="tblpdts" cellpadding="0" cellspacing="0">
                        <tr>
                            <th>Propriedade</th>
                            <th>Unidade</th>
                            <th>Descrição</th>
                            <th>Documento de referência</th>
                        </tr>
                        @foreach($gop as $group)
                        <tbody>
                            <tr>
                                <td class="text-left content-start bg-slate-300 p-3" colspan="4">
                                    <input class="text-left expand" type="checkbox" name="{{ $group->gopNamePt }}" id="{{ $group->gopNamePt }}" data-toggle="toggle">
                                    <label class="my-auto text-left" for="{{ $group->gopNamePt }}">Grupo de propriedades - {{ $group->gopNamePt }}</label>
                                </td>
                            </tr>
                        </tbody>

                        <tbody class="hide">
                            @foreach($joined_properties as $property)

                            @if($property->gopID == $group->Id)
                            <tr>
                                <td class="p-1.5 property-td">
                                    <a href="{{ route('datadictionaryview', ['propID' => $property->GUID , 'propV' => $property->versionNumber, 'propR' => $property->revisionNumber]) }}">{{ $property->namePt }}</a>
                                </td>
                                <td class="p-1.5">
                                    {{ $property->units }}
                                </td>
                                <td class="p-1.5">
                                    <div class="flex flex-col">
                                        <p>{{$property->descriptionPt}}</p>
                                        @if($property->visualRepresentation == True)
                                        <div class="col-sm">
                                            <img src="{{ asset ('img/'.$property->nameEn.'.png')}}" alt='{{$property->nameEn}}' class="property-image">
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                @if ($referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName == 'n/a')
                                <td class="p-1.5">
                                    <a>{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}</abbr></a>
                                </td>
                                @else
                                <td class="p-1.5">
                                    <a href="{{ route('referencedocumentview', ['rdGUID' => $property->referenceDocumentGUID]) }}">
                                        <p title="{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->title }}">{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}</p>
                                    </a>
                                </td>
                                @endif
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        @endforeach
                    </table>
                    <div class="my-6 text-end">
                        <a href="/dashboard">
                            <x-secondary-button id="backButton" type="button">
                                Anterior
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <table hidden id="tblpdtsh">
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
                {{ $property->namePt }}
            </td>
            <td>
                {{ $property->units }}
            </td>
            <td>
                {{$property->descriptionPt}}
            </td>
            <td>
                {{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}
            </td>

        </tr>
        @endif
        @endforeach
        @endforeach
    </table>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
    <script src="https://rawcdn.githack.com/FuriosoJack/TableHTMLExport/v2.0.0/src/tableHTMLExport.js"></script>


    <script>
        $(document).ready(function() {
            $('[data-toggle="toggle"]').change(function() {
                $(this).parents().next('.hide').toggle();
            });
        });
        $("#json").on("click", function() {
            $("#tblpdtsh").tableHTMLExport({
                type: "json",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.json",
            });
        });
        $("#csv").on("click", function() {
            $("#tblpdtsh").tableHTMLExport({
                type: "csv",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.csv"
            });
        });

        $("#txt").on("click", function() {
            $("#tblpdtsh").tableHTMLExport({
                type: "txt",
                filename: "{{ $pdt[0]->pdtNameEn }} data template.txt"
            });
        });
    </script>


</x-app-layout>