<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Foto') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Actualize a fotografia da sua conta.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.updatePhoto') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @if (Auth::user()->photo)
        <img class="rounded-circle shadow-1-strong me-3" src="{{ asset(Auth::user()->photo) }}" alt="{{ Auth::user()->name }}" style="width: 150px; height: 150px; border-radius: 50%;">
        @else
        <img class="rounded-circle shadow-1-strong me-3" src="{{ asset('/img/users/default.png') }}" alt="{{ Auth::user()->name }}" style="width: 150px; height: 150px; border-radius: 50%;">
        @endif
        <div class="form-group">
            <x-input-label for="photo" :value="__('Photo')" />
            <input type="file" name="photo" id="photo" class="form-control-file">
        </div>
        <button type="submit" class="btn btn-success">Actualizar a foto</button>

        @if (session('status') === 'photo-updated')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Actualizada.') }}</p>
        @endif
    </form>
    <form method="post" action="{{ route('profile.deletePhoto') }}">
        @csrf
        @method('DELETE')
        @if ($user->photo)
        <br>
        <button type="submit" class="btn btn-danger">Apagar foto</button>
        @endif
    </form>
</section>