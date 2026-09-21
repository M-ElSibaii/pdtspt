<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ Lg::t('Subscrever') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ Lg::t('Ao subscrever, só receberá notificações por e-mail quando houver um novo feedback sobre um Modelo de Dados de Produto ao qual tenha previamente adicionado feedback.') }}
        </p>
    </header>

    <form method="POST" action="{{ route('profile.updateSubscription') }}">
        @csrf
        <label>
            <input type="radio" name="subscribe" value="1" {{ $user->subscribe ? 'checked' : '' }}>
            Sim
        </label>
        <label>
            <input type="radio" name="subscribe" value="0" {{ $user->subscribe ? '' : 'checked' }}>
            Não
        </label>
        <br><br>
        <x-primary-button>{{ Lg::t('Guardar') }}</x-primary-button>
    </form>
</section>