<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Product Data Template survey and comments') }}
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
                                                        <h4>{{ $property->namePt }} {{ $property->Id }}</h4>
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
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Yes </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no">
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> No </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion" checked>
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> No Opinion </label>
                                                        </div>
                                                        @elseif ($answers->where('properties_Id',$property->Id)->sortByDesc('created_at')->first()->answer == 'no')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes">
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Yes </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no" checked>
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> No </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion">
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> No Opinion </label>
                                                        </div>
                                                        @elseif ($answers->where('properties_Id',$property->Id)->sortByDesc('created_at')->first()->answer == 'yes')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerYes-{{$property->Id}}" value="yes" checked>
                                                            <label class="form-check-label" for="answerYes-{{$property->Id}}"> Yes </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNo-{{$property->Id}}" value="no">
                                                            <label class="form-check-label" for="answerNo-{{$property->Id}}"> No </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="{{$property->Id}}" id="answerNoOpinion-{{$property->Id}}" value="no_opinion">
                                                            <label class="form-check-label" for="answerNoOpinion-{{$property->Id}}"> No Opinion </label>
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
                                                                                        <h4> Property feedback section </h4>
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
                                                                                                            <button style="color: red;" data-target="#DeleteModal{{ $comment->id }}" class="btn danger deletebtn{{ $comment->id }}">Delete</button>
                                                                                                            @endif</span>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="div-comment">
                                                                                                    <h5 style="margin-top: 8px;">&ensp;{{ $comment->body }}</h5>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <!-- Delete Modal -->
                                                                                        <div class="modal fade" id="DeleteModal{{ $comment->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                            <div class="modal-dialog">
                                                                                                <div class="modal-content">
                                                                                                    @csrf
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title" id="exampleModalLabel">Delete</h5>
                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        <h4>Are you sure you want to delete feedback?</h4>
                                                                                                        <input type="hidden" id="deleting_id{{ $comment->id }}" value="{{ $comment->id }}">
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                                        <button type="button" class="btn btn-primary delete_feedback{{ $comment->id }}">Yes, delete</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <script>
                                                                                            $(document).on('click', '.deletebtn{{ $comment->id }}', function() {
                                                                                                var comment_id = $(this).val();
                                                                                                $('#DeleteModal{{ $comment->id }}').modal('show');
                                                                                                $('#deleting_id{{ $comment->id }}').val(comment_id);
                                                                                            });

                                                                                            $(document).on('click', '.deletebtn{{ $comment->id }}', function(e) {
                                                                                                e.preventDefault();


                                                                                                var id = $('#deleting_id{{ $comment->id }}').val();

                                                                                                $.ajaxSetup({
                                                                                                    headers: {
                                                                                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                                                                    }
                                                                                                });

                                                                                                $.ajax({
                                                                                                    type: "DELETE",
                                                                                                    url: "/deletefeedback/" + id,
                                                                                                    dataType: "json",
                                                                                                    success: function(response) {
                                                                                                        // console.log(response);

                                                                                                        $('#success_message').html("");
                                                                                                        $('#success_message').addClass('alert alert-success');
                                                                                                        $('#success_message').text(response.message);
                                                                                                        $('.delete_feedback{{ $comment->id }}').text('Yes Delete');
                                                                                                        $('#DeleteModal{{ $comment->id }}').modal('hide');
                                                                                                        fetchfeedback();

                                                                                                    }
                                                                                                });
                                                                                            });
                                                                                        </script>
                                                                                        @endif
                                                                                        @endforeach

                                                                                        <div class="container">
                                                                                            <div data-property-id="{{ $property->Id }}">
                                                                                                <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#AddFeedbackModal{{ $property->Id }}">Add Feedback</button>
                                                                                            </div>
                                                                                        </div>



                                                                                        <!-- Add Modal -->
                                                                                        <div class="modal fade" id="AddFeedbackModal{{ $property->Id }}" tabindex="-1" aria-labelledby="AddFeedbackModalLabel{{ $property->Id }}" aria-hidden="true">
                                                                                            <div class="modal-dialog">
                                                                                                <div class="modal-content">
                                                                                                    @csrf
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title" id="AddFeedbackModalLabel{{ $property->Id }}">Add Feedback</h5>
                                                                                                        <button type="button" class="btn-close{{ $property->Id }}" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">

                                                                                                        <ul id="save_msgList{{ $property->Id }}"></ul>

                                                                                                        <div class="form-group mb-3">
                                                                                                            <strong>{{ $property->nameEn }} {{ $property->Id }}</strong>
                                                                                                            <input type="hidden" required class="property{{ $property->Id }} form-control" value="{{ $property->Id }}">
                                                                                                        </div>
                                                                                                        <div class="form-group mb-3">
                                                                                                            <label for="">Feedback</label>
                                                                                                            <input type="text" required class="feedback{{ $property->Id }} form-control">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-secondary {{ $property->Id }}" data-bs-dismiss="modal">Close</button>
                                                                                                        <button type="button" class="btn btn-primary add_feedback{{ $property->Id }}">Save {{ $property->Id }}</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
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
                                        <script>
                                            $(document).on('click', '.add_feedback{{ $property->Id }}', function(e) {
                                                e.preventDefault();


                                                $(this).text('Adding..');

                                                var data = {
                                                    'properties_Id': $('.property{{ $property->Id }}').val(),
                                                    'body': $('.feedback{{ $property->Id }}').val(),
                                                }


                                                $.ajaxSetup({
                                                    headers: {
                                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                    }
                                                });

                                                $.ajax({
                                                    type: "POST",
                                                    url: "pdtssurveystore",
                                                    data: data,
                                                    dataType: "json",
                                                    success: function(response) {
                                                        console.log(response);
                                                        if (response.status == 400) {
                                                            $('#save_msgList{{ $property->Id }}').html("");
                                                            $('#save_msgList{{ $property->Id }}').addClass('alert alert-danger');
                                                            $('#save_msgList{{ $property->Id }}').append('<li>The feedback field is required.</li>');
                                                            $('.add_feedback{{ $property->Id }}').text('Save');
                                                        } else {
                                                            $('#save_msgList{{ $property->Id }}').html("");
                                                            $('#success_message').addClass('alert alert-success');
                                                            $('#success_message').text(response.message);
                                                            $('#AddFeedbackModal{{ $property->Id }}').find('input').val('');
                                                            $('.add_feedback{{ $property->Id }}').text('Save');
                                                            $('#AddFeedbackModal{{ $property->Id }}').modal('hide');
                                                            fetchfeedback();
                                                        }
                                                    }
                                                });

                                            });
                                        </script>
                                        <script>
                                            $(document).ready(function() {

                                                fetchfeedback();

                                                function fetchfeedback() {
                                                    $.ajax({
                                                        type: "GET",
                                                        url: "/fetchfeedback",
                                                        dataType: "json",
                                                        success: function(response) {
                                                            console.log(response.comments);
                                                            $('commentbodysection{{ $property->Id }}').html("");
                                                            $.each(response.comments, function(key, item) {
                                                                $('commentbodysection{{ $property->Id }}').append(' <div class="flex-grow-1 flex-shrink-1">\
                                                                                                        <div class="d-flex">\
                                                                                                            <img class="rounded-circle shadow-1-strong me-3" src="{{ asset ("img/default.png")}}" alt="userPhoto" width="65" height="65" />\
                                                                                                            <div class="div-username">\
                                                                                                                <h5>{{$comment->user->name}}</h5>\
                                                                                                                <span class="small d-block">{{$comment->created_at}}@if ($comment->user->name == Auth::user()->name )\
                                                                                                                    <button style="color: red" value="' + item.id + '" class="btn danger deletebtn">Delete</button>\
                                                                                                                    @endif</span>\
                                                                                                            </div>\
                                                                                                        </div>\
                                                                                                        <div class="div-comment">\
                                                                                                            <h5 style="margin-top: 8px;">&ensp;' + item.body + '</h5>\
                                                                                                        </div>\
                                                                                                    </div>');
                                                            });
                                                        }
                                                    });
                                                }
                                            });
                                        </script>
                                        @endif
                                        @endforeach
                                    </article>
                                </div>
                                @endforeach


                                <button class="btn btn-primary" id="saveButton">Save Answers</button>




                                <script>
                                    //     document.getElementById("saveButton").addEventListener("click", function() {
                                    //         // create a form to submit the answers
                                    //         var form = document.createElement("form");
                                    //         form.setAttribute("method", "post");
                                    //         form.setAttribute("action", "{{ route('saveAnswers') }}");

                                    //         // collect the answers
                                    //         var answers = [];
                                    //         var inputs = document.querySelectorAll("input[type='radio']:checked");
                                    //         inputs.forEach(function(input) {
                                    //             answers.push({
                                    //                 answer: input.value,
                                    //                 propertyId: input.getAttribute("name"),
                                    //                 user: input.getAttribute("data-user-id")
                                    //             });
                                    //         });

                                    //         // add the answers to the form as hidden inputs
                                    //         answers.forEach(function(answer) {
                                    //             var input = document.createElement("input");
                                    //             input.setAttribute("type", "hidden");
                                    //             input.setAttribute("name", "answers[]");
                                    //             input.setAttribute("value", JSON.stringify(answer));
                                    //             form.appendChild(input);
                                    //         });

                                    //         // add a CSRF token to the form
                                    //         var csrfInput = document.createElement("input");
                                    //         csrfInput.setAttribute("type", "hidden");
                                    //         csrfInput.setAttribute("name", "csrf_token");
                                    //         csrfInput.setAttribute("value", "{{ csrf_token() }}");
                                    //         form.appendChild(csrfInput);

                                    //         // submit the form
                                    //         document.body.appendChild(form);
                                    //         form.submit();
                                    //     });
                                </script>

                            </form>



                        </section>
                    </div>
                </div>
            </div>
        </main>
    </body>

</x-app-layout>