<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Export PDTs.pt domain in JSON format of buildingSMART Data Dictionary</h1>
            <form method="POST" action="{{ route('productdatatemplates.exportJson') }}">
                @csrf
                <br>
                <x-button-primary-pdts type="submit" title="Export in bsdd JSON format" />
            </form>
        </div>
    </div>
</x-app-layout>