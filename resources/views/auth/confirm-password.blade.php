<x-app-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ Lg::t('Esta é uma área segura da aplicação. Confirme a sua palavra-passe antes de continuar.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="Lg::t('Palavra-passe')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ Lg::t('Confirmar') }}
            </x-primary-button>
        </div>
    </form>
</x-app-layout>