<x-app-layout>
    <div class="container sm:max-w-full py-9">
        <h1>{{ __('Criar Nível de Necessidade de Informação') }}</h1>

        <form method="POST" action="{{ route('loin1') }}">
            @csrf

            <div class="loin-container">
                <h2>{{ __('Attributos de Nível de Necessidade de Informação') }}</h2>
                <br>
                <div class="form-group">
                    <label for="nomeProjeto"><strong>{{ __('Nome de Projeto') }}</strong></label>
                    <input type="text" name="nomeProjeto" id="nomeProjeto" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="nomeObjeto"><strong>{{ __('Objeto') }}</strong></label>
                    <input type="text" name="nomeObjeto" id="nomeObjeto" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="nome"><strong>{{ __('Nome') }}</strong></label>
                    <select name="nome" id="nome" class="form-control">
                        <option value="Requerido" selected>Requerido</option>
                        <option value="Não requerido">Não requerido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sistemaClassificacao"><strong>{{ __('Sistema de classificação') }}</strong></label>
                    <input type="text" name="sistemaClassificacao" id="sistemaClassificacao" class="form-control" placeholder="{{ __('SECCLASS') }}">
                </div>

                <div class="form-group">
                    <label for="tabelaClassificacao"><strong>{{ __('Tabela de classificação') }}</strong></label>
                    <input type="text" name="tabelaClassificacao" id="tabelaClassificacao" class="form-control" placeholder="{{ __('Produtos') }}">
                </div>

                <div class="form-group">
                    <label for="codigoClassificacao"><strong>{{ __('Código de classificação') }}</strong></label>
                    <select name="codigoClassificacao" id="codigoClassificacao" class="form-control">
                        <option value="Requerido" selected>Requerido</option>
                        <option value="Não requerido">Não requerido</option>
                    </select>
                </div>


                <div class="form-group">
                    <label for="atorRequerente"><strong>{{ __('Ator Requerente') }}</strong></label>
                    <input type="text" name="atorRequerente" id="atorRequerente" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="atorFornecedor"><strong>{{ __('Ator Fornecedor') }}</strong></label>
                    <input type="text" name="atorFornecedor" id="atorFornecedor" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="ifcElement"><strong>{{ __('IFC Element Type') }}</strong></label>
                    <select name="ifcElement" id="ifcElement" class="form-control">
                        {{-- Default option --}}
                        <option value="IfcElement" selected>{{ __('IfcElement') }}</option>
                        @foreach($ifcClasses as $ifcClass)
                        @if($ifcClass['name'] !== 'IfcElement')
                        <option value="{{ $ifcClass['id'] }}">
                            {{ $ifcClass['name'] }}
                        </option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="objectPDTId"><strong>{{ __('Modelo de Dados do Produto em PDTs.pt') }}</strong></label>
                    <select name="objectPDTId" id="objectPDTId" class="form-control">
                        <option value="" selected>{{ __('Selecionar um PDT') }}</option>
                        @foreach($pdts as $pdt)
                        @if($pdt['pdtNameEn'] !== 'Master')
                        <option value="{{ $pdt->Id }}">{{ $pdt->pdtNameEn }} V {{ $pdt->editionNumber }}.{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">{{ __('Avançar para a página de criação do Level of Information Need') }}</button>
            </div>


        </form>

        <!-- Existing LOIN Records -->
        @if($allUserLoins->count() > 0)
        <h2>{{ __('Níveis de Necessidade de Informação do utilizador') }}</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>{{ __('Nome de Projeto') }}</th>
                    <th>{{ __('Numero de Níveis de Necessidade de Informação') }}</th>
                    <th>{{ __('Acções') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allUserLoins as $loin)
                <tr>
                    <td>{{ $loin->projectName }}</td>
                    <td>{{ $loin->loin_count }}</td>
                    <td>
                        <a href="{{ route('loinViewProject', $loin->projectName) }}" class="btn btn-primary">{{ __('Ver/Download') }}</a>
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