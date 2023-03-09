<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Contact-nos') }}
        </h2>
    </x-slot>

    <body class="antialiased">
        <div class="relative flex items-top justify-center bg-gray-100 dark:bg-gray-900 sm:items-center py-4 sm:pt-0">
            <div class="container">
                <div class="row mt-0 mb-5">
                    <strong>
                        <a>Para entrar em contacto com a equipa de investigação utilize o formulário a baixo ou contact-nos no nosso email: <a href="mailto: pdts.portugal@gmail.com">pdts.portugal@gmail.com</a></a>
                    </strong>
                    <div class="col-15 offset-0 mt-5">
                        <div class="card">
                            @if (session('success'))
                            <div class="alert flex flex-row items-center bg-green-200 p-4 rounded border-b-2 border-green-300 py-4 mb-1">
                                <div class="alert-icon flex items-center bg-green-100 border-2 border-green-500 justify-center h-10 w-10 flex-shrink-0 rounded-full">
                                    <span class="text-green-500">
                                        <svg fill="currentColor" viewBox="0 0 20 20" class="h-6 w-6">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="alert-content ml-4">
                                    <div class="alert-title font-semibold text-lg text-green-800">
                                        {{ __('success') }}

                                    </div>
                                    <div class="alert-description text-sm text-green-600">
                                        {{ session('success') }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="card-header bg-dark">
                                <h3 class="text-white">Contact Form</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="form-group" action="{{ route('contact.store') }}" id="contactUSForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <strong>Nome:</strong>
                                                <input type="text" name="name" class="form-control" placeholder="Nome" value="{{ old('name') }}">
                                                @if ($errors->has('name'))
                                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <strong>Email:</strong>
                                                <input type="text" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}">
                                                @if ($errors->has('email'))
                                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <strong>Telemóvel:</strong>
                                                <input type="text" name="phone" class="form-control" placeholder="Telemóvel" value="{{ old('phone') }}">
                                                @if ($errors->has('phone'))
                                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <strong>Assunto:</strong>
                                                <input type="text" name="subject" class="form-control" placeholder="Assunto" value="{{ old('subject') }}">
                                                @if ($errors->has('subject'))
                                                <span class="text-danger">{{ $errors->first('subject') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <strong>Mensagem:</strong>
                                                <textarea name="message" rows="3" class="form-control">{{ old('message') }}</textarea>
                                                @if ($errors->has('message'))
                                                <span class="text-danger">{{ $errors->first('message') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group text-center py-3">
                                        <button class="btn btn-success btn-submit">Submeter</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ml-2 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
            © 2021 UMinho. All rights reserved.
        </div>

    </body>
</x-guest-layout>