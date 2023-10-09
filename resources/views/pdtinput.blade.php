<x-app-layout>
    <div style="background-color: white;">
        @if(session('successpdt'))
        <div class="alert alert-success mt-3">
            {{ session('successpdt') }}
        </div>
        @endif

        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ __('Create PDTs') }}</h1>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="mb-4">
                                <form class="mb-3" action="{{ route('productdatatemplates.create') }}">
                                    <x-secondary-button type="submit">
                                        {{ __('Create Data Template') }}
                                    </x-secondary-button>
                                </form>
                            </div>
                            <div class="mb-4">
                                <form class="mb-3" action="{{ route('groupofproperties.create1') }}">
                                    <x-secondary-button type="submit">
                                        {{ __('Create Group of Properties') }}
                                    </x-secondary-button>
                                </form>
                            </div>
                            <div class="mb-4">
                                <form class="mb-3" action="{{ route('properties.create') }}">
                                    <x-secondary-button type="submit">
                                        {{ __('Create Properties') }}
                                    </x-secondary-button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>