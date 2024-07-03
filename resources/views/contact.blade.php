<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9 flex ls:flex-row md:flex-row sm:flex-col gap-6">
            <div class="basis-1/2 text-current">
                <h2>Contactos</h2>
                <p>Para entrar em contacto com a equipa de investigação utilize o formulário a baixo ou contact-nos no nosso email:</p>
                <a href="mailto: pdts.portugal@gmail.com">pdts.portugal@gmail.com</a>
                <img class="max-h-[500px] w-auto" src="{{asset('/img/contact.jpg')}}" alt="contact">
            </div>
            <div class="basis-1/2 flex flex-col">
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

                <h3>Formulário de contacto</h3>
                <div class="">
                    <form method="POST" action="{{ route('contact.store') }}" id="contactUSForm">
                        @csrf

                        <!-- Name -->
                        <div class="mt-2">
                            <x-input-label for="name" :value="__('Nome:')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email Address -->
                        <div class="mt-2">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <!-- Subject -->
                        <div class="mt-2">
                            <x-input-label for="subject" :value="__('Assunto:')" />
                            <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject')" required autofocus />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <!-- Message -->
                        <div class="mt-2">
                            <x-input-label for="message" :value="__('Mensagem:')" />
                            <x-textarea-input id="message" rows=10 class="block mt-1 w-full" type="text" name="message" old_message="{{old('message')}}" required autofocus />
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                        </div>

                        <div class="mt-2">
                            <input type="checkbox" name="human_check" id="human_check" required />
                            <label for="human_check">Não sou um robot</label>
                        </div>

                        <div class="flex items-center justify-end mt-2">
                            <x-primary-button class="ml-3">
                                Enviar mensagem
                            </x-primary-button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</x-app-layout>