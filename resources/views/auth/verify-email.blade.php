<x-app-layout>
    <div class="h-screen" style="background-color: white;">
        <div class="container py-9">
            <h1>{{ Lg::t('Verifique o seu e-mail') }}</h1>

            <div class="mb-4 text-sm text-gray-600">
                {{ Lg::t('Obrigado por se inscrever! Antes de começar, poderia verificar o seu endereço electrónico clicando no link que lhe acabámos de enviar por correio electrónico? Se não recebeu o e-mail, enviar-lhe-emos de bom grado outro.') }}
            </div>

            @if (session('status') == 'verification-link-sent')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ Lg::t('Uma nova ligação de verificação foi enviada para o endereço de correio electrónico que nos forneceu durante o registo.') }}
            </div>
            @endif

            <div class="mt-4 flex items-center justify-between">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <div>
                        <x-primary-button>
                            {{ Lg::t('Reenviar e-mail de verificação') }}
                        </x-primary-button>
                    </div>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ Lg::t('Sair') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>