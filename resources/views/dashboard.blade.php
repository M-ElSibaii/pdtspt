<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Os Modelos de Dados dos Produtos</h1>
            <table class="table-auto min-w-full text-left text-sm font-light">
                <thead class="border-b font-medium dark:border-neutral-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Image</th>
                        <th scope="col" class="px-6 py-4">pdtNamePt</th>
                        <th scope="col" class="px-6 py-4">versionNumber</th>
                        <th scope="col" class="px-6 py-4">dateOfVersion</th>
                        <th scope="col" class="px-6 py-4">revisionNumber</th>
                        <th scope="col" class="px-6 py-4">dateOfRevision</th>
                        <th scope="col" class="px-6 py-4"></th>
                        <th scope="col" class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($latestPDT as $pdt)
                    <tr class="border-b dark:border-neutral-500">
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <img
                                class="w-auto max-w-[100px] max-h-14"
                                {{-- src="{{asset('/img/' . $pdt->pdtNameEn . '.png')}}" --}}
                                src="{{asset('/img/Master.png')}}"
                                alt="" />
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->pdtNamePt }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->versionNumber }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->dateOfVersion }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->revisionNumber }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $pdt->dateOfRevision }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <form class="mb-3" action="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}">
                                <x-button-primary-pdts 
                                    type="submit"
                                    title="Ver e download"/>
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium">
                            <form class="mb-3" action="{{ route('pdtssurvey', ['pdtID' => $pdt->Id])  }}">
                                <x-button-primary-pdts 
                                    type="submit"
                                    title="Revisão e comentário"/>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</x-app-layout>