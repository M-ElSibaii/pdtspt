<x-app-layout>
    <div class="container sm:max-w-full py-9">
        @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger mt-3">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <h1>{{ __('Criar Projeto') }}</h1>

        <form method="POST" action="{{ route('projectsstore') }}">
            @csrf
            <div class="form-group">
                <label for="projectName"><strong>{{ __('Nome de Projeto') }}</strong></label>
                <input type="text" name="projectName" id="projectName" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description"><strong>{{ __('Descrição do Projeto (opcional)') }}</strong></label>
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-secondary">{{ __('Criar Projeto') }}</button>
        </form>


        <!-- Existing Projects -->
        @if($projects->count() > 0)
        <h2 class="mt-5">{{ __('Projetos do utilizador') }}</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>{{ __('Nome de Projeto') }}</th>
                    <th>{{ __('Descrição') }}</th>
                    <th>{{ __('LOINs') }}</th>
                    <th>{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $project)
                <tr>
                    <td>{{ $project->projectName }}</td>
                    <td>{{ $project->description }}</td>
                    <td>{{ $project->loins_count }}</td>

                    <td>
                        <a href="{{ route('loinViewProject', $project->id) }}" class="btn btn-primary" style="color: black;">{{ __('Ver/Descarregar LOINs') }}</a>
                        <a href="{{ route('loincreate1', $project->id) }}" class="btn btn-primary" style="color: black;">{{ __('Criar LOINs') }}</a>
                        <a href="{{ route('loinattributes', $project->id) }}" class="btn btn-primary" style="color: black;">{{ __('Adicionar/Editar attributos') }}</a>
                        <form method="POST" action="{{ route('projectsdestroy', $project->id) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="color: black;">{{ __('Apagar Projeto') }}</button>
                        </form>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>{{ __('Não foram encontrados projetos.') }}</p>
        @endif
    </div>
</x-app-layout>