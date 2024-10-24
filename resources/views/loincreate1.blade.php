<x-app-layout>
    <div class="container py-9">
        @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
        @endif
        <h1>{{ __('Create LOIN for Project: ') }} {{ $project->projectName }}</h1>
        <form method="POST" action="{{ route('loincreate2', $project->id) }}">
            @csrf
            <input type="hidden" id="projectId" name="projectId" value="{{$project->id}}">
            <!-- Select Milestones -->
            <div class="form-group">
                <label for="milestone">{{ __('Select Milestone') }}</label>
                <select name="milestone" class="form-control" required>
                    <option value="" disabled selected>Select Milestone</option>
                    @foreach($milestones as $milestone)
                    <option value="{{ $milestone->id }}">{{ $milestone->milestone }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Select Actors -->
            <div class="form-group">
                <label for="actor_requiring">{{ __('Select Actor requiring') }}</label>
                <select name="actor_requiring" class="form-control" required>
                    <option value="" disabled selected>Select Actor Requiring</option>
                    @foreach($actors as $actor)
                    <option value="{{ $actor->id }}">{{ $actor->actor }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="actor_providing">{{ __('Select Actor Providing') }}</label>
                <select name="actor_providing" class="form-control" required>
                    <option value="" disabled selected>Select Actor Providing</option>
                    @foreach($actors as $actor)
                    <option value="{{ $actor->id }}">{{ $actor->actor }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Select Purpose -->
            <div class="form-group">
                <label for="purpose">{{ __('Select Purpose') }}</label>
                <select name="purpose" class="form-control" required>
                    <option value="" disabled selected>Select Purpose</option>
                    @foreach($purposes as $purpose)
                    <option value="{{ $purpose->id }}">{{ $purpose->purpose }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Select Objects -->
            <div class="form-group">
                <label for="object">{{ __('Select Object') }}</label>
                <select name="object" class="form-control" required>
                    <option value="" disabled selected>Select Object</option>
                    @foreach($objects as $object)
                    <option value="{{ $object->id }}">{{ $object->object }} ({{ $object->ifcClass }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Select PDT -->
            <div class="form-group">
                <label for="pdt">{{ __('Select relevant Product Data Template (PDT) from platform') }}</label>
                <select name="pdt" class="form-control">
                    <option value="" disabled selected>Select PDT</option>
                    @foreach($pdts as $pdt)
                    @if($pdt['pdtNameEn'] !== 'Master')
                    <option value="{{ $pdt->Id }}">{{ $pdt->pdtNamePt }} ( {{ $pdt->pdtNameEn }} ) V {{ $pdt->editionNumber }}.{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</option>
                    @endif
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">{{ __('Create LOIN with selected attributes') }}</button>
        </form>
        <br>
        <!-- Existing LOIN Records -->
        <h2>{{ __('Níveis de Necessidade de Informação do projeto') }}</h2>
        @if($loins->count() > 0)

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>{{ __('Nome de Projeto') }}</th>
                    <th>{{ __('Nome de objeto') }}</th>
                    <th>{{ __('Proposito') }}</th>
                    <th>{{ __('Actor Fornecedor') }}</th>
                    <th>{{ __('Actor Requerente') }}</th>
                    <th>{{ __('Fase de Projeto') }}</th>
                    <th>{{ __('Acções') }}</th>
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
                    <td>{{ $loin->milestone }}</td>
                    <td>
                        <a href="{{ route('loinView', $loin->id) }}" class="btn btn-primary" style="color: black;">{{ __('Ver/Descarregar/Apagar') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>{{ __('Não foram encontradas entradas para Níveis de Necessidade de Informação.') }}</p>
        @endif
    </div>
</x-app-layout>