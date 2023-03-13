<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Documentação API') }}
        </h2>
    </x-slot>
    <main class="flex-shrink-0">
        <div class="py-9">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4  mx-auto bg-white dark:bg-gray-800 shadow sm:rounded-lg">

                    <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                        <h1 class="text-2xl font-bold mb-6">API de Modelos de Dados de Produtos</h1>
                        <h2 class="text-lg font-bold mb-4">Obter Template de Dados de Produtos</h2>
                        <p class="mb-4">Retorna o modelo de dados do produto com o ID especificado, com grupos de propriedades, propriedades, atributos de propriedades do dicionário de dados e documentos de referência relevantes.</p>
                        <div style="background-color: #F0F4F8;">
                            <pre><code>GET /api/{pdtID}</code></pre>
                        </div>
                        <h2 class="text-lg font-bold mb-4">Obter Todos os Modelos de Dados de Produtos</h2>
                        <p class="mb-4">Retorna todos os templates de dados de produtos.</p>
                        <div style="background-color: #F0F4F8;">
                            <pre><code>GET /api/productDataTemplates</code></pre>
                        </div>
                        <h2 class="text-lg font-bold mb-4">Obter Conteúdo do Dicionário de Dados</h2>
                        <p class="mb-4">Retorna o dicionário de dados para os templates de dados de produtos.</p>
                        <div style="background-color: #F0F4F8;">
                            <pre><code>GET /api/dataDictionary</code></pre>
                        </div>
                        <h2 class="text-lg font-bold mb-4">Obter Documentos de Referência</h2>
                        <p class="mb-4">Retorna os documentos de referência usados nos templates de dados de produtos.</p>
                        <div style="background-color: #F0F4F8;">
                            <pre><code>GET /api/referenceDocuments</code></pre>
                        </div>
                        <h2 class="text-lg font-bold mb-4">Obter Grupos de Propriedades</h2>
                        <p class="mb-4">Retorna os grupos de propriedades para os templates de dados de produtos.</p>
                        <div style="background-color: #F0F4F8;">
                            <pre><code>GET /api/groupsOfProperties</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="ml-2 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
            © 2021 UMinho. All rights reserved. <a href="{{route('privacypolicy')}}"> Política de privacidade</a>
            <p></p>
        </div>
    </main>

</x-app-layout>