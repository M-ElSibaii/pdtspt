<!-- resources/views/groupofproperties/create1.blade.php -->
<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                <h1>{{ __('Select PDT') }}</h1>

                <form method="POST" action="{{ route('groupofproperties.creategop') }}">
                    @csrf
                    <div class="form-group">
                        <label for="pdtId">{{ __('Select PDT') }}</label>
                        <select class="form-control" id="pdtId" name="pdtId" required>
                            <!-- Populate dropdown with PDTs -->
                            @foreach ($pdts as $pdt)
                            <option value="{{ $pdt->Id }}">{{ $pdt->pdtNameEn }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button type="submit">
                        {{ __('Next') }}
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>