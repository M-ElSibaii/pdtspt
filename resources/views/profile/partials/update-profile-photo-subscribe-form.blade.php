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
        <div class="flex flex-col gap-4">
            <div class="">
                <x-input-label for="photo" :value="__('Photo')" />
                <input type="file" name="photo" id="photo" class="block w-full text-sm text-slate-500 file:text-xs 
                file:mr-4 file:py-2 file:px-4
                file:rounded-md file:uppercase
                file:text-sm file:text-gray-700 file:font-semibold
                file:bg-white
                file:dark:bg-gray-800
                file:border file:border-gray-300 file:dark:border-gray-500
                file:tracking-widest file:shadow-sm file:hover:bg-gray-50 file:dark:hover:bg-gray-700 file:focus:outline-none file:focus:ring-2 file:focus:ring-indigo-500 file:focus:ring-offset-2 file:dark:focus:ring-offset-gray-800">
            </div>
            <x-button-primary-pdts
                type="submit"
                title="Actualizar a foto"
            />
        </div>

        @if (session('status') === 'photo-updated')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Actualizada.') }}</p>
        @endif
    </form>
    <form method="post" action="{{ route('profile.deletePhoto') }}">
        @csrf
        @method('DELETE')
        @if ($user->photo)
        <br>
        <x-button-primary-pdts
            type="submit"
            class="btn-danger"
            title="Apagar foto"
        />
        @endif
    </form>
</section>