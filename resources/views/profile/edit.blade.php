<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ __('Perfil') }}</h1>
            @if (session('subscribestatus'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('subscribestatus') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 2000);
            </script>
            @endif
            @if (session('photodeletestatus'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('photodeletestatus') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 2000);
            </script>
            @endif
            @if (session('photostatus'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('photostatus') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 2000);
            </script>
            @endif
            @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 2000);
            </script>
            @endif
            <div class="py-12 grid lg:grid-cols-2 md:grid-cols-2 sm:grid-cols-1 gap-4">
                <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
                <div class="mx-auto sm:px-6 lg:px-8 space-y-6">     
                    @include('profile.partials.update-profile-photo-subscribe-form')
                </div>  
                <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                    @include('profile.partials.update-password-form')
                </div>
                <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                    @include('profile.partials.subscribe-form')
                </div>

                <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                    @include('profile.partials.delete-user-form')
                </div>

            </div>
        </div>
    </div>
</x-app-layout>