<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>API de Modelos de Dados de Produtos</h1>

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

</x-app-layout>