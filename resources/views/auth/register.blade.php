<x-app-layout>
    <div class="h-screen" style="background-color: white;">
        <div class="h-full container">
            <div
                class="g-6 flex h-full flex-wrap items-center justify-center lg:justify-between">
                <div
                    class="shrink-1 mb-12 grow-0 basis-auto md:mb-0 md:w-9/12 md:shrink-0 lg:w-6/12 xl:w-6/12">
                    <img
                        src="https://tecdn.b-cdn.net/img/Photos/new-templates/bootstrap-login-form/draw2.webp"
                        class="w-full"
                        alt="Sample image" />
                </div>
                <div class="mb-12 md:mb-0 md:w-8/12 lg:w-5/12 xl:w-5/12">
                    <h1>Registo</h1>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Nome')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <br>
                        <!-- Profession-->
                        <div>
                            <x-input-label for="profession" :value="__('Profissão')" />
                            <x-text-input id="profession" class="block mt-1 w-full" type="text" name="profession" :value="old('profession')" autofocus />
                            <x-input-error :messages="$errors->get('profession')" class="mt-2" />
                        </div>
                        <br>
                        <!-- Institute -->
                        <div>
                            <x-input-label for="institute" :value="__('Instituto')" />
                            <x-text-input id="institute" class="block mt-1 w-full" type="text" name="institute" :value="old('institute')" autofocus />
                            <x-input-error :messages="$errors->get('institute')" class="mt-2" />
                        </div>

                        <!-- Email Address -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Palavra-passe')" />

                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" :value="__('Confirmar Palavra-passe')" />

                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />

                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('privacypolicy') }}">
                                {{ __('Ao registar-se nesta plataforma, está a concordar com a nossa política de privacidade.') }}
                            </a>
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                                {{ __('Já registado?') }}
                            </a>

                            <x-primary-button class="ml-4">
                                {{ __('Registar') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <br>
        <br>
        <div class="ml-4 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
            © 2021 UMinho. All rights reserved. <a href="{{route('privacypolicy')}}"> Política de privacidade</a>
            <p></p>
        </div>

    </main>
</x-app-layout>