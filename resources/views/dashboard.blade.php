<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Os Modelos de Dados dos Produtos') }}
        </h2>
    </x-slot>
    <main class="flex-shrink-0">
        <div class="py-9">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="home_content container">
                        <div class="row row-cols-1 row-cols-md-5 mt-2">
                            @foreach($latestPDT as $pdt)
                            <div class="col" style="width: 210px;">
                                <div class="card text-center border-dark shadow" style="max-width: 12rem;">
                                    <img style="background-image: url('/img/{{$pdt->pdtNameEn}}.png'); background-size: cover; width:100%; height:190px; background-position:center;" class="card-img-top">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <strong>
                                                {{ $pdt->pdtNamePt }}
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <form class="mb-3" action="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}">

                                            <button class="btn btn-dark btn-sm" type="submit">Ver e download</button>
                                        </form>
                                        <form class="mb-3" action="{{ route('pdtssurvey', ['pdtID' => $pdt->Id])  }}">

                                            <button class="btn btn-dark btn-sm" type="submit">Revisão e comentário</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>