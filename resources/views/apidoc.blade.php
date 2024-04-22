<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>API de Modelos de Dados de Produtos</h1>
            <h2 class="my-4">Obter Template de Dados de Produtos</h2>
            <p class="mb-4">Retorna o modelo de dados do produto com o ID especificado, com grupos de propriedades, propriedades, atributos de propriedades do dicionário de dados e documentos de referência relevantes.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/{pdtID}</code></pre>
            </div>
            <h2 class="my-4">Obter Todos os Modelos de Dados de Produtos</h2>
            <p class="mb-4">Retorna todos os templates de dados de produtos.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/productDataTemplates</code></pre>
            </div>
            <h2 class="my-4">Obter todas as propriedades do Dicionário de Dados</h2>
            <p class="mb-4"> Retorna todas as propriedades do dicionário de dados dos modelos de dados do produto.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/dataDictionary</code></pre>
            </div>
            <h2 class="my-4">Obter uma propriedade do Dicionário de dados</h2>
            <p class="mb-4">Retorna uma propriedade e seus atributos do dicionário de dados.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/dataDictionary/{Id}</code></pre>
            </div>
            <h2 class="my-4">Obter Documentos de Referência</h2>
            <p class="mb-4">Retorna os documentos de referência usados nos templates de dados de produtos.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/referenceDocuments</code></pre>
            </div>
            <h2 class="my-4">Obter um Documento de Referência</h2>
            <p class="mb-4">Retorna um documento de referência usado nos templates de dados de produtos.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/referenceDocuments/{GUID}</code></pre>
            </div>
            <h2 class="my-4">Obter Grupos de Propriedades</h2>
            <p class="mb-4">Retorna os grupos de propriedades para os templates de dados de produtos.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/groupsOfProperties</code></pre>
            </div>
            <h2 class="my-4">Obter um Grupo de Propriedades</h2>
            <p class="mb-4">Retorna um grupo de propriedades e seus atributos para um template de dados de produto.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/groupsOfProperties/{Id}</code></pre>
            </div>
            <h2 class="my-4">Obter Todos os objectos de construção</h2>
            <p class="mb-4">retorna todos os objectos de construção.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/constructionObjects</code></pre>
            </div>
        </div>
    </div>

</x-app-layout>