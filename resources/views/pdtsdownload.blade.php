<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("View and Download Product Data Template") }}
        </h2>
    </x-slot>

    <body>
        <main class="flex-shrink-0">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class="home_content container">
                            <div class="row">
                                <h2> {{ $pdt[0]->pdtNameEn }} Data Template V{{ $pdt[0]->versionNumber }}.{{ $pdt[0]->revisionNumber }}

                                    <button id="json" class="btn"><i class="fa fa-download"></i> JSON</button>
                                    <button id="csv" class="btn"><i class="fa fa-download"></i> CSV/XLS</button>
                                    <button id="txt" class="btn"><i class="fa fa-download"></i> TXT</button>

                                </h2>
                            </div>
                            <table class=table id="tblpdts" cellpadding="0" cellspacing="0">
                                <tr>
                                    <th>Group of properties</th>
                                    <th>Property name</th>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Reference Document</th>
                                </tr>
                                @foreach($gop as $group)

                                @foreach($joined_properties as $property)

                                @if($property->gopID == $group->Id)

                                <tr>
                                    <td>
                                        {{ $group->gopNameEn }}
                                    </td>

                                    <td>
                                        <form class="mb-3" action="{{ route('datadictionaryview', ['propID' => $property->GUID , 'propV' => $property->versionNumber, 'propR' => $property->revisionNumber]) }}">
                                            <button class="btn btn-link" type="submit">{{ $property->nameEn }}</button>
                                        </form>
                                    </td>
                                    <td>
                                        {{ $property->units }}
                                    </td>
                                    <td>
                                        <div class="row">
                                            <p>{{$property->descriptionEn}}</p>
                                            @if($property->visualRepresentation == 'True')
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
                                        <form class="mb-3" action="{{ route('referencedocumentview', ['rdGUID' => $property->referenceDocumentGUID]) }}">
                                            <button class="btn btn-link" type="submit">
                                                <a><abbr title="{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->title }}">{{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}</abbr></a>
                                            </button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                                @endif
                                @endforeach
                        </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </main>
        </table>


        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
        <script src="https://rawcdn.githack.com/FuriosoJack/TableHTMLExport/v2.0.0/src/tableHTMLExport.js"></script>


        <script>
            $("#json").on("click", function() {
                $("#tblpdts").tableHTMLExport({
                    type: "json",
                    filename: "$pdtname data template.json",
                });
            });
            $("#csv").on("click", function() {
                $("#tblpdts").tableHTMLExport({
                    type: "csv",
                    filename: "$pdtname data template.csv"
                });
            });

            $("#txt").on("click", function() {
                $("#tblpdts").tableHTMLExport({
                    type: "txt",
                    filename: "$pdtname data template.txt"
                });
            });
        </script>

    </body>
</x-app-layout>