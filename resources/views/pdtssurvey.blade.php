<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ __('Análises e comentários do Modelo de Dados do Produto') }}</h1>
            <section class="">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif
                <p>O objetivo deste questionário é apoiar o consenso da indústria rumo a PDTs uniformizados a nível nacional</p>
                <div class="mb-6">
                    <h1 class="flex-none inline">{{ $pdt[0]->pdtNamePt }}</h1>
                    <p class="flex-none inline"> - V{{ $pdt[0]->versionNumber }}.{{ $pdt[0]->revisionNumber }}</p>
                </div>
                <form class="overflow-scroll" name="form" id="form" method="post" action="{{ route('saveAnswers') }}" style="
                overflow: scroll;
            ">
                    <input type="hidden" name="pdtName" value="{{ $pdt[0]->pdtNamePt }}">

                    @csrf
                    <table class="table-auto" id="tblpdts" cellpadding="0" cellspacing="0">

                        <tr>
                            <th>Propriedade</th>
                            <th>Unidade</th>
                            <th>Descrição</th>
                            <th>Documento de referência</th>
                            <th>Questão</th>
                            <th>Comentários</th>
                        </tr>



                        @foreach($gop as $group)
                        <tbody>
                            <tr>
                                <td class="text-left content-start bg-slate-300 p-3" colspan="6">
                                    <input class="text-left expand" type="checkbox" name="{{ $group->gopNamePt }}" id="{{ $group->gopNamePt }}" data-toggle="toggle">
                                    <label class="my-auto text-left cursor-pointer" for="{{ $group->gopNamePt }}">Grupo de propriedades - {{ $group->gopNamePt }}</label>
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
                                <td class="p-1.5">
                                    <div class="flex flex-col">
                                        @csrf
                                        @php
                                        $answer = $answers->where('properties_Id', $property->Id)->sortByDesc('created_at')->first();
                                        $yesChecked = $answer && $answer->answer == 'yes' ? 'checked' : '';
                                        $noChecked = $answer && $answer->answer == 'no' ? 'checked' : '';
                                        $noOpinionChecked = !$answer || $answer->answer == 'no_opinion' ? 'checked' : '';
                                        @endphp
                                        <div class="form-check form-check-inline">
                                            <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes" {{$yesChecked}}>
                                            <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="answerYes-{{$property->Id}}">Sim</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no" {{$noChecked}}>
                                            <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="answerNo-{{$property->Id}}">Não</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion" {{$noOpinionChecked}}>
                                            <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="answerNoOpinion-{{$property->Id}}">Sem opinião</label>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-1.5">
                                    <x-nav-link type="button" id="loadComments-{{$property->Id}}" onclick="loadComments(this, '{{$property->Id}}')">Comentários ({{ \App\Models\comments::where('properties_Id', $property->Id)->count() }})</x-nav-link>
                                </td>
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
                        <x-primary-button id="saveButton" type="submit">
                            Guardar Respostas
                        </x-primary-button>
                    </div>

                </form>
            </section>
        </div>

        <x-modal-popup />

    </div>
    <script>
        // $(".alert").alert();

        $(document).ready(function() {
            $('[data-toggle="toggle"]').change(function() {
                $(this).parents().next('.hide').toggle();
            });
        });

        function insertComment(id) {

            var data = {
                'properties_Id': id,
                'body': $('#message').val(),
            };
            $('#message').val(''),

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

            $.ajax({
                type: "POST",
                url: "pdtssurveystore",
                dataType: 'json',
                data: data,
                success: function(response) {
                    console.log(response);
                    if (response.status == 400) {
                        alert('Nenhum conteúdo no feedback. O campo de comentário é obrigatório')

                    } else {
                        addComment(response.comment[0], id);

                    }
                }
            });

        };

        function closeModal(id) {

            $("div[id='comments-addbutton-" + id + "']").replaceWith('<div id="comments-addbutton-' + id + '">\
                        <a data-te-ripple-init data-te-ripple-color="light" \
                            type="submit" \
                            class="inline-flex items-center px-4 py-2 bg-slate-700 dark:bg-slate-200 rounded-md font-semibold text-xs text-white dark:text-gray-900 uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-white focus:bg-slate-900 dark:focus:bg-white active:bg-slate-900 dark:active:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150" \
                            onclick="openModal(' + id + ')" \
                            type="button">\
                            Adicionar comentário\
                        </a>\
                    </div>');
        }

        function openModal(id) {

            $("div[id='comments-addbutton-" + id + "']").replaceWith('<div id="comments-addbutton-' + id + '" class="flex flex-col addcommentrow">\
                <lable>Adicionar comentário</lable>\
                <textarea class="form-control" name="message" id="message"></textarea>\
                <div class="flex flex-row gap-4 my-2">\
                    <a type="button" style="float:right" data-te-ripple-init data-te-ripple-color="light" class="inline-flex items-center px-4 py-2 bg-slate-700 dark:bg-slate-200 rounded-md font-semibold text-xs text-white dark:text-gray-900 uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-white focus:bg-slate-900 dark:focus:bg-white active:bg-slate-900 dark:active:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150" onclick="insertComment(' + id + ')" id="insertComment">Adicionar comentário</a>\
                    <a type="button" style="float:right" data-te-ripple-init data-te-ripple-color="light" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150" onclick="closeModal(' + id + ')">Cancelar</a>\
                </div>\
            </div>');
        }

        function unloadComments(id) {
            $("td[id^='comments-line-" + id + "']").remove('');
            $("a[id^='loadComments-" + id + "']").attr("onclick", "loadComments(this, " + id + ")");
        }

        function loadComments(e, id) {

            $("td[id^='comments-line-']").remove('');
            $("a[id^='loadComments-" + id + "']").attr("onclick", "unloadComments(" + id + ")");
            $('<td class="text-left content-start p-6" id="comments-line-' + id + '" colspan="6">\
                    <h4 class="mb-6">Comentários</h4>\
                    <div id="comments-section-' + id + '">\
                    </div>\
                    <div id="comments-addbutton-' + id + '">\
                        <a data-te-ripple-init data-te-ripple-color="light" \
                            type="submit" \
                            class="inline-flex items-center px-4 py-2 bg-slate-700 dark:bg-slate-200 rounded-md font-semibold text-xs text-white dark:text-gray-900 uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-white focus:bg-slate-900 dark:focus:bg-white active:bg-slate-900 dark:active:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150" \
                            onclick="openModal(' + id + ')" \
                            type="button">\
                            Adicionar comentário\
                        </a>\
                    </div>\
                </td>').insertAfter($(e).parent().parent());

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                type: "POST",
                url: "/comments/" + id,
                success: function(response) {
                    console.log(response.comments)
                    for (let index = 0; index < response.comments.length; index++) {
                        addComment(response.comments[index], id);

                    }
                }
            });

        }

        function addComment(comment, lineId) {

            var section = '';
            section = "<div id='commentbodysection" + comment.id + "' class='w-full mb-4'>\
                    <div class='flex flex-row'>";
            if (comment.user.photo != null) {
                section += "<img src='{{ asset(' + comment.user.photo + ') }}' alt='{{ ' + comment.user.name + ' }}' class='img-fluid rounded-circle mr-3' style='width: 40px; height: 40px;'>";
            } else {
                section += "<img src='{{ asset('img/users/default.png ') }}' alt='{{ " + comment.user.name + " }}' class='img-fluid rounded-circle mr-3' style='width: 40px; height: 40px;'>";
            }
            section += "<div class='flex flex-col w-full'>\
                <div class='flex flex-row gap-2 align-bottom'>\
                    <span class='font-bold my-auto'>" + comment.user.name + "</span>\
                    <span class='text-xs font-thin my-auto'>" + moment(comment.created_at).fromNow() + "</span>";
            if (comment.user.name == "{{Auth::user()->name}}") {
                section += "<a class='text-xs font-thin my-auto' type='button' data-te-toggle='modal' data-te-target='#DeleteModal' style='color: red;' onclick='openDeleteModal(" + comment.id + ")'>Apagar</a>";
            }
            section += '</div>\
                        <div class="div-comment flex-gow">\
                        <p class="m-1">' + comment.body + '</p>\
                    </div>\
                </div>\
                    </div>\
            </div>';
            $('#comments-section-' + comment.properties_Id).append(section);

            var comments = $('#loadComments-' + lineId).text();
            // document.getElementById("para");
            console.log(comments)
            var oldNumberComments = comments.replace('Comentários (','');
            oldNumberComments = comments.replace(')','');
            newNumberComments = oldNumberComments + 1;
            console.log(newNumberComments)

            $('#loadComments-' + lineId).text('Comentários (' + newNumberComments + ')');

        }

        function openDeleteModal(id) {
            $('#delete_feedback').attr('data-id', id)
        }


        $(document).on('click', '#delete_feedback', function(e) {
            e.preventDefault();
            var data = {
                'comment_id': $(this).attr('data-id'),
            }
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "DELETE",
                url: "/deletefeedback",
                data: data,
                success: function(response) {
                    $('#commentbodysection' + response.comment_id).remove();
                    alert("Feedback apagado com sucesso!")

                }
            });
        });
    </script>
</x-app-layout>