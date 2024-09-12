<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{ __('Níveis de Necessidade de Informação para o Projeto: ') }}{{ $projectName }}</h1>
            <div class='py-6'>
                <div class="flex flex-row gap-2 py-4">
                    <a href="{{ route('exportProjectLoinsJson', $projectName) }}" class="btn btn-secondary" style="color: black;">
                        <i class="fa fa-download"></i>&nbsp; Download todos os LOINs (JSON)
                    </a>
                    <a href="{{ route('exportProjectLoinsExcel', $projectName) }}" class="btn btn-secondary" style="color: black;">
                        <i class="fa fa-download"></i>&nbsp; Download todos os LOINs (CSV/XLS)
                    </a>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="background-color: #f0f0f0;">{{ __('Nome de Projeto') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Nome de objeto') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Proposito') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Actor Fornecedor') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Actor Requerente') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Fase de Projeto') }}</th>
                            <th style="background-color: #f0f0f0;">{{ __('Acções') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loins as $loin)
                        <tr>
                            <td>{{ $loin->projectName }}</td>
                            <td>{{ $loin->objectName }}</td>
                            <td>{{ $loin->purpose }}</td>
                            <td>{{ $loin->actorProviding }}</td>
                            <td>{{ $loin->actorRequesting }}</td>
                            <td>{{ $loin->projectPhase }}</td>
                            <td>
                                <a href="{{ route('loinView', $loin->id) }}" class="btn btn-secondary">{{ __('Ver/Download/Apagar') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>