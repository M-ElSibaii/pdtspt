<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9" style="overflow: scroll;">
            <h1>Os Modelos de Dados dos Produtos</h1>
            <table class="table-auto min-w-full text-left text-sm font-light">
                <colgroup>
                    <col style="width: 10%">
                    <col style="width: 20%">
                    <col style="width: 20%">
                    <col style="width: 20%">
                    <col style="width: 15%">
                    <col style="width: 15%">
                </colgroup>
                <thead class="border-b font-medium dark:border-neutral-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Imagem</th>
                        <th scope="col" class="px-6 py-4">Nome</th>
                        <th scope="col" class="px-6 py-4">Versões</th>
                        <th scope="col" class="px-6 py-4">Data</th>
                        <th scope="col" class="px-6 py-4"></th>
                        <th scope="col" class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($latestPDT as $pdt)
                    <tr class="border-b dark:border-neutral-500">
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <img class="w-auto max-w-[100px] max-h-14" src="{{asset('/img/' . $pdt->pdtNameEn . '.png')}}" alt="" />
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->pdtNamePt }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <div class="flex items-center">
                                <span class="mr-1">V{{ $pdt->editionNumber }}.{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}
                                    <br> @if ($pdt->status == 'InActive')
                                    <span class="status-tag status-tag-inactive">InActiva</span>
                                    @endif
                                    @if ($pdt->status == 'Active')
                                    <span class="status-tag status-tag-active">Activa</span>
                                    @endif
                                    @if ($pdt->status == 'Preview')
                                    <span class="status-tag status-tag-preview">Preview</span>
                                    @endif</span>
                                @php
                                $hasOtherVersions = $allpdts->where('GUID', $pdt->GUID)->where('Id', '!=', $pdt->Id)->isNotEmpty();
                                @endphp
                                @if ($hasOtherVersions)
                                <div class="relative inline-block text-left">
                                    <x-dropdown aligns="right" width="w-36">
                                        <x-slot name="trigger">
                                            <button class="inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-2 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100">
                                                <svg class="h-4 w-4 fill-current text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            @foreach($allpdts as $otherPdt)
                                            @if($otherPdt->GUID == $pdt->GUID && $otherPdt->Id != $pdt->Id)
                                            <x-dropdown-link :href="route('pdtsdownload', ['pdtID' => $otherPdt->Id])">
                                                V{{ $otherPdt->editionNumber }}.{{ $otherPdt->versionNumber }}.{{ $otherPdt->revisionNumber }}
                                                @if ($otherPdt->status == 'InActive')
                                                <span class="status-tag status-tag-inactive">Inativa</span>
                                                @endif
                                                @if ($otherPdt->status == 'Active')
                                                <span class="status-tag status-tag-active">Ativa</span>
                                                @endif
                                                @if ($otherPdt->status == 'Preview')
                                                <span class="status-tag status-tag-preview">Preview</span>
                                                @endif

                                            </x-dropdown-link>
                                            @endif
                                            @endforeach
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                                @endif
                            </div>
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
    </div>
</x-app-layout>