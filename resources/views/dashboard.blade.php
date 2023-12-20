<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" style="
        overflow: scroll;
    ">
            <h1>Os Modelos de Dados dos Produtos</h1>
            <table class="table-auto min-w-full text-left text-sm font-light">
                <thead class="border-b font-medium dark:border-neutral-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Imagem</th>
                        <th scope="col" class="px-6 py-4">Nome</th>
                        <th scope="col" class="px-6 py-4">Versão</th>
                        <th scope="col" class="px-6 py-4">Data</th>
                        <th scope="col" class="px-6 py-4"></th>
                        <th scope="col" class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($latestPDT as $pdt)
                    <tr class="border-b dark:border-neutral-500">
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <img class="w-auto max-w-[100px] max-h-14" src="{{asset('/img/' . $pdt->pdtNameEn . '.png')}}" {{-- src="{{asset('/img/Master.png')}}" --}} alt="" />
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->pdtNamePt }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}
                            @if ($pdt->status == 'Inactive')
                            <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">InActiva</span>
                            @endif
                            @if ($pdt->status == 'Active')
                            <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Activa</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->dateOfVersion }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium my-auto ">
                            <form class="mb-3" action="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}">
                                <x-button-primary-pdts type="submit" title="Ver" />
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium my-auto">
                            <form class="mb-3" action="{{ route('pdtssurvey', ['pdtID' => $pdt->Id])  }}">
                                <x-secondary-button type="submit">
                                    {{ __('Revisão') }}
                                </x-secondary-button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </main>
</x-app-layout>