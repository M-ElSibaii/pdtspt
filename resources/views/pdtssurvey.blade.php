<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Análises e comentários do Modelo de Dados do Produto') }}
        </h2>
    </x-slot>

    <body>
        <main class="flex-shrink-0">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <section class="container">
                            @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            @endif
                            <h2>Análise e comentário de PDTs</h2>
                            <h6>O objetivo deste questionário é apoiar o consenso da indústria rumo a PDTs uniformizados a nível nacional</h6>
                            <h3> {{ $pdt[0]->pdtNameEn }} Data Template V{{ $pdt[0]->versionNumber }}.{{ $pdt[0]->revisionNumber }}</h3>
                            <form name="form" id="form" method="post">
                                @csrf

                                @foreach($gop as $group)

                                <div class="ac">
                                    <input class="ac-input" id="ac-{{$group->Id}}" name="ac-{{$group->Id}}" type="checkbox" />
                                    <label class="ac-label" for="ac-{{$group->Id}}">
                                        <h3>{{$group->gopNamePt}}
                                            <h6 class='text-muted'>{{$group->gopNameEn}}</h6>
                                        </h3>
                                    </label>
                                    <article class="ac-text">

                                        @foreach($joined_properties as $property)
                                        @if($property->gopID == $group->Id)
                                        <div class="container shadow" style="border: thin solid lightGrey">
                                            <div class="ac-sub">
                                                <input class="ac-input" id="ac-{{ $property->GUID }}" name="ac-{{ $property->GUID }}" type="checkbox" />
                                                <label class="ac-label" for="ac-{{ $property->GUID }}">
                                                    <a href="{{ route('datadictionaryview', ['propID' => $property->GUID , 'propV' => $property->versionNumber, 'propR' => $property->revisionNumber]) }}" target="_blank">
                                                        <h4>{{ $property->namePt }}</h4>
                                                        <h6 class='text-muted'>{{ $property->nameEn }}</h6>
                                                    </a>
                                                    <h6 class='text-muted'>Descrição: {{$property->descriptionPt}}<br>
                                                        Description: {{$property->descriptionEn}}

                                                    </h6>

                                                    <div class="form-group">
                                                        @csrf
                                                        @if (is_null($answers->where('properties_Id', $property->Id)->first()?->answer) OR $answers->where('properties_Id',$property->Id)->sortByDesc('created_at')->first()->answer == 'no_opinion')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes">
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Sim </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no">
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> Não </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion" checked>
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> Sem opinião </label>
                                                        </div>
                                                        @elseif ($answers->where('properties_Id',$property->Id)->sortByDesc('created_at')->first()->answer == 'no')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes">
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Sim </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no" checked>
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> Não </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion">
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> Sem opinião </label>
                                                        </div>
                                                        @elseif ($answers->where('properties_Id',$property->Id)->sortByDesc('created_at')->first()->answer == 'yes')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes" checked>
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Sim </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no">
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> Não </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion">
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> Sem opinião </label>
                                                        </div>
                                                        @endif


                                                    </div>

                                                    feedbacks ({{ \App\Models\comments::where('properties_Id', $property->Id)->whereNull('parent_id')->count() }})

                                                </label>
                                                <article class="ac-sub-text">
                                                    <section class="gradient-custom">
                                                        <div class="container">
                                                            <div class="row d-flex justify-content-left">
                                                                <div class="col-md-8 col-lg-8 col-xl-8">
                                                                    <div class="card">
                                                                        <div class="card-body p-4">
                                                                            <div class="row">
                                                                                <div class="col">

                                                                                    <div class="row d-flex justify-content-center">
                                                                                        <h4> Secção de feedback da propriedade </h4>
                                                                                    </div>
                                                                                    <div id="comments-section-{{ $property->Id }}" class="comment-form">
                                                                                        @foreach ($comments as $comment)
                                                                                        @if ($comment->properties_Id == $property->Id && $comment->parent_id == null)
                                                                                        <div id="commentbodysection{{ $comment->id }}" class="d-flex flex-start mt-1">
                                                                                            <div class="flex-grow-1 flex-shrink-1">

                                                                                                <div class="d-flex">
                                                                                                    @if ($comment->user->photo)
                                                                                                    <img src="{{ asset($comment->user->photo) }}" alt="{{ $comment->user->name }}" class="img-fluid rounded-circle mr-3" style="width: 65px; height: 65px;">
                                                                                                    @else
                                                                                                    <img src="{{ asset('img/users/default.png') }}" alt="{{ $comment->user->name }}" class="img-fluid rounded-circle mr-3" style="width: 65px; height: 65px;">
                                                                                                    @endif

                                                                                                    <div class="div-username">
                                                                                                        <h5>{{$comment->user->name}}</h5>
                                                                                                        <span class="small d-block">{{$comment->created_at}}
                                                                                                            @if ($comment->user->name == Auth::user()->name )
                                                                                                            <button type="button" style="color: red;" onclick="openDeleteModal('{{ $comment->id }}')" class="btn danger">Apagar</button>
                                                                                                            @endif</span>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="div-comment">
                                                                                                    <h5 style="margin-top: 8px;">&ensp;{{ $comment->body }}</h5>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>

                                                                                        @endif
                                                                                        @endforeach

                                                                                    </div>
                                                                                    <div class="container">
                                                                                        <div data-property-id="{{ $property->Id }}">
                                                                                            <button type="button" class="btn btn-primary float-end" onclick="openModal('{{$property->Id}}')">Adicionar Feedback</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </section>
                                                </article>
                                            </div>
                                        </div>

                                        @endif
                                        @endforeach
                                    </article>
                                </div>
                                @endforeach


                                <button type="button" class="btn btn-primary" id="saveButton">Guardar Respostas</button>


                            </form>



                        </section>
                    </div>
                </div>
            </div>
            <!-- Add Modal -->
            <div class="modal fade" id="AddFeedbackModal" tabindex="-1" aria-labelledby="AddFeedbackModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <!-- <h5 class="modal-title" id="AddFeedbackModalLabel">Add Feedback</h5> -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- <ul id="save_msgList"></ul> -->
                            <div class="form-group mb-3">
                                <label for="">Feedback</label>
                                <input type="text" required id="feedback" class="feedback form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary " data-bs-dismiss="modal" aria-label="Close">Fechar</button>
                            <button type="button" id="add_feedback" data-id="" class="btn btn-primary " data-bs-dismiss="modal" aria-label="Close">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Delete Modal -->
            <div class="modal fade" id="DeleteModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Apagar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <h4>Tem a certeza de que quer apagar o feedback?</h4>
                        </div>
                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="button" id="delete_feedback" data-id="" class="btn btn-primary" data-bs-dismiss="modal">Sim, Apagar</button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </body>

    <script>
        function openDeleteModal(id) {
            // open Modal  
            var myModal = new bootstrap.Modal(document.getElementById('DeleteModal'))
            myModal.toggle();
            // define the ID
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
                    console.log(response);
                    $('#commentbodysection' + response.comment_id).remove();
                    alert("Feedback apagado com sucesso!")

                }
            });
        });

        function openModal(id) {
            // open Modal  
            var myModal = new bootstrap.Modal(document.getElementById('AddFeedbackModal'))
            myModal.toggle();
            // define the ID
            $('#add_feedback').attr('data-id', id)
        }

        $(document).on('click', '#add_feedback', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            // $(this).text('Adding..');

            var data = {
                'properties_Id': id,
                'body': $('#feedback').val(),
            }
            $('#feedback').val(""),

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

            $.ajax({
                type: "POST",
                url: "pdtssurveystore",
                data: data,
                success: function(response) {
                    console.log(response);
                    if (response.status == 400) {
                        alert('Nenhum conteúdo no feedback. O campo de feedback é obrigatório')
                        // $('#save_msgList').html("");
                        // $('#save_msgList').addClass('alert alert-danger');
                        // $('#save_msgList').append('<li>The feedback field is required.</li>');
                        // $('.add_feedback').text('Save');
                    } else {
                        $('#comments-section-' + id).append(
                            '<div class="commentbodysection' + response.comment.id + '">\
                                <div class="flex-grow-1 flex-shrink-1">\
                                    <div class="d-flex">\
                                    @if ($comment->user->photo)\
                                    <img src="{{ asset($comment->user->photo) }}" alt="{{ $comment->user->name }}" class="img-fluid rounded-circle mr-3" style="width: 65px; height: 65px;">\
                                    @else\
                                    <img src="{{ asset("img/users/default.png") }}" alt="{{ $comment->user->name }}" class="img-fluid rounded-circle mr-3" style="width: 65px; height: 65px;">\
                                    @endif\
                                        <div class="div-username">\
                                            <h5>{{$comment->user->name}}</h5>\
                                            <span class="small d-block">' + "{{ date('Y-m-d H:i:s') }}" + '\
                                                <button type="button" style="color: red;" onclick="openDeleteModal(' + response.comment.id + ')" class="btn danger">Delete</button>\
                                            </span>\
                                        </div>\
                                    </div>\
                                    <div class="div-comment">\
                                        <h5 style="margin-top: 8px;">&ensp;' + response.comment.body + '</h5>\
                                    </div>\
                                </div>\
                            </div>'
                        );
                    }
                }
            });

        });
    </script>
    <script>
        document.getElementById("saveButton").addEventListener("click", function() {
            // create a form to submit the answers
            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", "{{ route('saveAnswers') }}");

            // collect the answers
            var answers = [];
            var inputs = document.querySelectorAll("input[type='radio']:checked");
            inputs.forEach(function(input) {
                answers.push({
                    answer: input.value,
                    propertyId: input.getAttribute("name"),
                    user: input.getAttribute("data-user-id")
                });
            });

            // add the answers to the form as hidden inputs
            answers.forEach(function(answer) {
                var input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", "answers[]");
                input.setAttribute("value", JSON.stringify(answer));
                form.appendChild(input);
            });

            // add a CSRF token to the form
            var csrfInput = document.createElement("input");
            csrfInput.setAttribute("type", "hidden");
            csrfInput.setAttribute("name", "csrf_token");
            csrfInput.setAttribute("value", "{{ csrf_token() }}");
            form.appendChild(csrfInput);

            // submit the form
            document.body.appendChild(form);
            form.submit();
        });
    </script>



</x-app-layout>