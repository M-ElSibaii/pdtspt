<x-app-layout>

    <main class="flex-shrink-0">
        <div class="py-9">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4  mx-auto bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="home_content container">

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
                                    <h3 class="text-white">Formulário de contacto</h3>
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
        </div>


        <div class="ml-2 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
            © 2021 UMinho. All rights reserved. <a href="{{route('privacypolicy')}}"> Política de privacidade</a>
            <p></p>
        </div>
    </main>

</x-app-layout>