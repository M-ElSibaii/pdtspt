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
                                                                                    <div id="comments-section-{{ $property->Id }}">
                                                                                        <div class="row d-flex justify-content-center">
                                                                                            <h4> Property feedback section </h4>
                                                                                        </div>
                                                                                        @foreach ($comments as $comment)
                                                                                        @if ($comment->properties_Id == $property->Id && $comment->parent_id == null)
                                                                                        <div class="d-flex flex-start mt-1">
                                                                                            <div class="flex-grow-1 flex-shrink-1">
                                                                                                <!-- add if statement, if user photo exist add photo, else add default -->
                                                                                                <div class="d-flex">
                                                                                                    <img class="rounded-circle shadow-1-strong me-3" src="{{ asset ('img/default.png')}}" alt="userPhoto" width="65" height="65" />
                                                                                                    <div class="div-username">
                                                                                                        <h5>{{$comment->user->name}}</h5>
                                                                                                        <span class="small d-block">{{$comment->created_at}}</span>
                                                                                                    </div>
                                                                                                    <!--<a href="#!" id="reply-button{{$comment->id}}"><i class="fas fa-reply fa-xs"></i><span>reply</span></a>-->
                                                                                                </div>
                                                                                                <div class="div-comment">

                                                                                                    <h5 style="margin-top: 8px;">&ensp;{{ $comment->body }}</h5>

                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <!-- <a href="#!" class="like-button" data-comment-id="{{ $comment->id }}">
                                                                                                        <i class="fas fa-thumbs-up"></i>
                                                                                                        <span class="like-count">{{ $comment->likes->count() }}</span>
                                                                                                    </a> -->


                                                                                        <!--   @foreach ($comments as $reply)
                                                                                                @if ($reply->properties_Id == $property->Id && $reply->parent_id == $comment->id)
                                                                                                <div class="d-flex flex-start offset-1 mt-4">
                                                                                                    <a class="me-3" href="#">
                                                                                                        <img class="rounded-circle shadow-1-strong" src="https://mdbcdn.b-cdn.net/img/Photos/Avatars/img%20(11).webp" alt="avatar" width="65" height="65" />
                                                                                                </a>
                                                                                                <div class="flex-grow-1 flex-shrink-1">
                                                                                                    <div>
                                                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                                                            <p class="mb-1"> <strong>{{$reply->user->name}}</strong> <span class="small"> - {{$reply->created_at}}</span>
                                                                                                                <a href="#!" id="reply-button{{$reply->id}}"><i class="fas fa-reply fa-xs"></i><span>reply</span></a>
                                                                                                            </p>
                                                                                                        </div>
                                                                                                        <div class="div-comment">
                                                                                                            <h5>
                                                                                                                &ensp; {{ $reply->body}}
                                                                                                            </h5>
                                                                                                        </div>
                                                                                                        <a href="#!" class="like-button" data-comment-id="{{ $reply->id }}">
                                                                                                            <i class="fas fa-thumbs-up"></i>
                                                                                                            <span class="like-count">{{ $reply->likes->count() }}</span>
                                                                                                        </a>

                                                                                                    </div>
                                                                                                </div>
                                                                                            
                                                                                            @foreach ($comments as $subreply)
                                                                                            @if ($subreply->properties_Id == $property->Id && $subreply->parent_id == $reply->id)
                                                                                            <div class="d-flex flex-start offset-2 mt-4">
                                                                                                <a class="me-3" href="#">
                                                                                                    <img class="rounded-circle shadow-1-strong" src="https://mdbcdn.b-cdn.net/img/Photos/Avatars/img%20(11).webp" alt="avatar" width="65" height="65" />
                                                                                                </a>
                                                                                                <div class="flex-grow-1 flex-shrink-1">
                                                                                                    <div>
                                                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                                                            <p class="mb-1"><strong> {{$subreply->user->name}} </strong><span class="small"> - {{$subreply->created_at}}</span>
                                                                                                            </p>
                                                                                                        </div>
                                                                                                        <div class="div-comment">
                                                                                                            <h5>
                                                                                                                &ensp; {{ $subreply->body}}
                                                                                                            </h5>
                                                                                                        </div>@csrf
                                                                                                        <a href="#!" class="like-button" data-comment-id="{{ $reply->id }}">
                                                                                                            <i class="fas fa-thumbs-up"></i>
                                                                                                            <span class="like-count">{{ $subreply->likes->count() }}</span>
                                                                                                        </a>

                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endif
                                                                                            @endforeach
                                                                                            <div class="container" id="reply-form{{$reply->id}}" style="display: none;">
                                                                                                <form action=" {{ route ('replyStore', $reply->id, $property->Id)}} " method="post" class="reply-form" data-property-id="{{ $property->Id }}">
                                                                                                    @csrf
                                                                                                    <div class="form-group">
                                                                                                        <textarea name="body" class="form-control"></textarea>
                                                                                                    </div>
                                                                                                    <input type="hidden" name="comment_id" value="{{ $reply->id }}">
                                                                                                    <input type="hidden" name="properties_Id" value="{{ $property->Id }}">
                                                                                                    <button type="submit" class="btn btn-primary">Reply</button>
                                                                                                </form>

                                                                                                <script>
                                                                                                    $(document).ready(function() {
                                                                                                        $('#reply-button{{$reply->id}}').click(function() {
                                                                                                            $('#reply-form{{$reply->id}}').toggle();
                                                                                                        });
                                                                                                    });
                                                                                                </script>
                                                                                            </div>
                                                                                            @endif
                                                                                            @endforeach
                                                                                            <div class="container" id="reply-form{{$comment->id}}" style="display: none;">
                                                                                                <form action="{{ route ('replyStore', $comment->id, $property->Id)}}" method="post" class="reply-form" data-property-id="{{ $property->Id }}">
                                                                                                    @csrf
                                                                                                    <div class="form-group">
                                                                                                        <textarea name="body" class="form-control"></textarea>
                                                                                                    </div>
                                                                                                    <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                                                                                                    <input type="hidden" name="properties_Id" value="{{ $property->Id }}">
                                                                                                    <button type="submit" class="btn btn-primary">Reply</button>
                                                                                                </form>

                                                                                                <script>
                                                                                                    $(document).ready(function() {
                                                                                                        $('#reply-button{{$comment->id}}').click(function() {
                                                                                                            $('#reply-form{{$comment->id}}').toggle();
                                                                                                        });
                                                                                                    });
                                                                                                </script>


                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                                -->
                                                                                        @endif
                                                                                        @endforeach
                                                                                        <div class="container">
                                                                                            <form action="{{ route ('store', $property->Id)}}" method="post" class="comment-form" data-property-id="{{ $property->Id }}">
                                                                                                <div class="comment-form" data-property-id="{{ $property->Id }}">
                                                                                                    @csrf
                                                                                                    <div class="form-group">
                                                                                                        <textarea name="body" class="form-control"></textarea>
                                                                                                    </div>
                                                                                                    <input type="hidden" name="properties_Id" value="{{ $property->Id }}">
                                                                                                    <button type="submit" class="btn btn-primary" id="submit">Add feedback</button>
                                                                                                </div>

                                                                                            </form>
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

                                <button class="btn btn-primary" id="saveButton">Save Answers</button>

                                <script>
                                    $(document).on('submit', '.comment-form', function(e) {
                                        e.preventDefault();

                                        var $form = $(this);
                                        var formData = $form.serialize();

                                        $.ajax({
                                            type: 'POST',
                                            url: "{{route('store')}}",
                                            data: formData,
                                            success: function(data) {
                                                // Refresh comments section for the property
                                                var propertyId = $form.data('property-id');
                                                var $commentsSection = $('#comments-section-' + propertyId);
                                                $commentsSection.load(window.location.href + ' #comments-section-' + propertyId + ' > *');
                                            },
                                            error: function(xhr, textStatus, errorThrown) {
                                                console.log(xhr.responseText);
                                            }
                                        });
                                    });
                                </script>
                                <script>
                                    $(document).on('submit', '.reply-form', function(e) {
                                        e.preventDefault();

                                        var $form = $(this);
                                        var formData = $form.serialize();

                                        $.ajax({
                                            type: 'POST',
                                            url: $form.attr('action'),
                                            data: formData,
                                            success: function(data) {
                                                // Refresh comments section for the property
                                                var propertyId = $form.data('property-id');
                                                var commentId = $form.data('comment-id');
                                                var $commentsSection = $('#comments-section-' + propertyId);
                                                $commentsSection.load(window.location.href + ' #comments-section-' + propertyId + ' > *');
                                            },
                                            error: function(xhr, textStatus, errorThrown) {
                                                console.log(xhr.responseText);
                                            }
                                        });
                                    });
                                </script>
                                <!-- <script>
                                    $(document).ready(function() {
                                        $('.like-button ').click(function() {
                                            var commentId = $(this).data('comment-id');
                                            var isLiked = $(this).data('is-liked');
                                            // var likeCount = $(this).data('like-count');
                                            var likeButton = $(this);
                                            $.ajax({
                                                type: 'POST',
                                                url: ("{{ route('like-comment') }}"),
                                                data: {
                                                    '_token': '{{ csrf_token() }}',
                                                    'comment_id': commentId,
                                                    'is_liked': isLiked
                                                },
                                                success: function(data) {
                                                    likeButton.data('is-liked', !isLiked);
                                                    likeButton.data('like-count', data.like_count);
                                                    likeButton.text(data.button_text);
                                                }
                                            });
                                        });
                                    });
                                </script>
                                -->
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

                            </form>



                        </section>
                    </div>
                </div>
            </div>
        </main>
    </body>

</x-app-layout>