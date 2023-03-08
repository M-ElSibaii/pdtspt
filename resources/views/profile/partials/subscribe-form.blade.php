<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Subscribe') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('By subscribing, you will recieve email notifications only when there is new feedback on a Product Data Template you previously added feedback to.') }}
        </p>
    </header>

    <form method="POST" action="{{ route('profile.updateSubscription') }}">
        @csrf
        <label>
            <input type="radio" name="subscribe" value="1" {{ $user->subscribe ? 'checked' : '' }}>
            Yes
        </label>
        <label>
            <input type="radio" name="subscribe" value="0" {{ $user->subscribe ? '' : 'checked' }}>
            No
        </label>
        <br><br>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
</section>