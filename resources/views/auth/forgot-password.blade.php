<x-app-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ Lg::t('Esqueceu-se da sua palavra-passe? Sem problema. Indique-nos o seu endereço de e-mail e enviar-lhe-emos uma ligação para definir uma nova.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="Lg::t('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ Lg::t('Enviar ligação para redefinir a palavra-passe') }}
            </x-primary-button>
        </div>
    </form>
</x-app-layout>