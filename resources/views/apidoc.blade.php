<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>API de Modelos de Dados de Produtos</h1>
            
            <!-- Main PDT Endpoint -->
            <h2 class="my-4">Obter Modelo de Dados de Produto (Completo)</h2>
            <p class="mb-4">Retorna o modelo de dados do produto com o ID especificado (estrutura ISO 23387). Inclui TODAS as informações: grupos de propriedades, propriedades com atributos completos do dicionário de dados (EN ISO 23386), documentos de referência, e objeto de construção.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/{pdtID}</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> JSON </p>
            <p class="text-sm text-gray-600 my-2"><strong>Inclui:</strong> Todas as colunas das tabelas productdatatemplates, groupofproperties, propertiesdatadictionaries, referencedocuments, constructionobjects</p>
            <p class="text-sm text-gray-600 my-2"><strong>Exemplo:</strong> <code>GET /api/1</code></p>

            <!-- JSON Export Endpoint -->
            <h2 class="my-4 mt-8">Exportar Modelo de Dados de Produto como JSON (estrutura EN ISO 23387)</h2>
            <p class="mb-4">Retorna o modelo de dados do produto em formato JSON completamente compatível com EN ISO 23387.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/{pdtID}/json</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> JSON (estrutura EN ISO 23387)</p>
            <p class="text-sm text-gray-600 my-2"><strong>Content-Type:</strong> application/json</p>
            <p class="text-sm text-gray-600 my-2"><strong>Estrutura:</strong></p>
            <p class="text-sm text-gray-600 my-2">Os GUIDs são emitidos no formato UUID com hífens (8-4-4-4-12) para validarem contra o padrão do XSD ed-2. O elemento <code>Library</code> só admite o atributo <code>dt:GUID</code> e os elementos <code>Name</code> (sem <code>dateOfCreation</code>, <code>URI</code> ou <code>Definition</code> ao nível da Library).</p>
            <div style="background-color: #F0F4F8; margin: 10px 0;">
                <pre><code>{"Library": {
    "dt:GUID": "8d2f...-...-...-...-...",
    "Name": [
      { "language": "pt", "value": "..." },
      { "language": "en", "value": "..." }
    ],
    "DataTemplates": [{
        "Name": [...],
        "Definition": [...],
        "ReferenceDocumentRef": { "dt:GUID": "..." },
        "MajorVersion": 1,
        "MinorVersion": 0,
        "Status": "Active",
        "IsSubtypeOfRef": { "referenceURI": "https://pdts.pt/pdtview/{id}-{slug}" },
        "HasPartRef": [ { "referenceURI": "..." } ],
        "HasObjectTypeRef": { "dt:GUID": "..." },
        "HasPropertyRef": [ { "dt:GUID": "...", "referenceURI": "..." } ],
        "HasGroupOfPropertiesRef": [ { "dt:GUID": "...", "referenceURI": "..." } ],
        "dt:GUID": "...",
        "dateOfCreation": "2026-03-31T12:00:00Z"
    }],
    "ObjectType": {
        "Name": [...],
        "Definition": [...],
        "dt:GUID": "...",
        "dateOfCreation": "..."
    },
    "GroupOfProperties": [{
        "Name": [...],
        "Definition": [...],
        "IsSubtypeOfRef": { "referenceURI": "..." },
        "HasPartRef": [ { "referenceURI": "..." } ],
        "HasPropertyRef": [ { "dt:GUID": "...", "referenceURI": "..." } ],
        "dt:GUID": "...",
        "dateOfCreation": "..."
    }],
    "Properties": [{
        "Name": [...],
        "Definition": [...],
        "LanguageOfCreator": "pt-PT",
        "CountryOfOrigin": "PT",
        "MajorVersion": 1,
        "MinorVersion": 1,
        "Status": "Active",
        "DataType": {},
        "IsDependentOnRef": [ { "referenceURI": "..." } ],
        "_dependencyDetails": [ {
          "dependencyKind": "...", "expression": "...",
          "targets": [ { "referenceURI": "...", "isPreferred": true, "position": 0 } ]
        } ],
        "IsSpecializationOfRef": { "referenceURI": "..." },
        "dt:GUID": "...",
        "dateOfCreation": "..."
    }],
 
    "ReferenceDocuments": [{
        "Name": [...],
        "Definition": [...],
        "Status": "...",
        "URI": "https://pdts.pt/referencedocumentview/{guid}",
        "Language": "en",
        "dt:GUID": "...",
        "dateOfCreation": "..."
    }]
}}</code></pre>
            </div>
           <p class="text-sm text-gray-600 my-2"><strong>Exemplo:</strong> <code>GET /api/1/json</code></p>

            <!-- XML Export Endpoint -->
            <h2 class="my-4 mt-8">Exportar Modelo de Dados de Produto como XML (estrutura EN ISO 23387)</h2>
            <p class="mb-4">Retorna o modelo de dados do produto em formato XML completamente compatível com EN ISO 23387 XSD.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/{pdtID}/xml</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> XML </p>
            <p class="text-sm text-gray-600 my-2"><strong>Content-Type:</strong> application/xml</p>
            <p class="text-sm text-gray-600 my-2"><strong>Estrutura:</strong></p>
            <div style="background-color: #F0F4F8; margin: 10px 0;">
                <pre><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;dt:Library xmlns:dt="https://standards.iso.org/iso/23387/ed-2/en/"
            dt:GUID="8d2f...-...-...-...-..."&gt;
    &lt;dt:Name language="pt"&gt;...&lt;/dt:Name&gt;
    &lt;dt:Name language="en"&gt;...&lt;/dt:Name&gt;
    &lt;dt:DataTemplate dt:GUID="..." dateOfCreation="2026-03-31T12:00:00Z"&gt;
        &lt;dt:Name language="pt"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Name language="en"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="pt"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:Definition language="en"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:ReferenceDocumentRef dt:GUID="..."/&gt;
        &lt;dt:MajorVersion&gt;1&lt;/dt:MajorVersion&gt;
        &lt;dt:MinorVersion&gt;0&lt;/dt:MinorVersion&gt;
        &lt;dt:Status&gt;Active&lt;/dt:Status&gt;
        &lt;dt:IsSubtypeOfRef dt:referenceURI="https://pdts.pt/pdtview/{id}-{slug}"/&gt;
        &lt;dt:HasPartRef dt:referenceURI="..."/&gt;
        &lt;dt:HasObjectTypeRef dt:GUID="..."/&gt;
        &lt;dt:HasPropertyRef dt:GUID="..." dt:referenceURI="..."/&gt;
        &lt;dt:HasGroupOfPropertiesRef dt:GUID="..." dt:referenceURI="..."/&gt;
    &lt;/dt:DataTemplate&gt;
    &lt;dt:ObjectType dt:GUID="..." dateOfCreation="..."&gt;
        &lt;dt:Name language="en"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="en"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:IsSubtypeOfRef dt:referenceURI="..."/&gt;
    &lt;/dt:ObjectType&gt;
    &lt;dt:GroupOfProperties dt:GUID="..." dateOfCreation="..."&gt;
        &lt;dt:Name language="pt"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="pt"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:IsSubtypeOfRef dt:referenceURI="..."/&gt;
        &lt;dt:HasPropertyRef dt:GUID="..." dt:referenceURI="..."/&gt;
    &lt;/dt:GroupOfProperties&gt;
    &lt;dt:Property dt:GUID="..." dateOfCreation="..."&gt;
        &lt;dt:Name language="pt"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="pt"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:LanguageOfCreator&gt;pt-PT&lt;/dt:LanguageOfCreator&gt;
        &lt;dt:CountryOfOrigin&gt;PT&lt;/dt:CountryOfOrigin&gt;
        &lt;dt:MajorVersion&gt;1&lt;/dt:MajorVersion&gt;
        &lt;dt:MinorVersion&gt;1&lt;/dt:MinorVersion&gt;
        &lt;dt:Status&gt;Active&lt;/dt:Status&gt;
        &lt;dt:ReferenceDocumentRef dt:GUID="..."/&gt;
        &lt;dt:DataType&gt;
        &lt;/dt:DataType&gt;
        &lt;dt:IsDependentOnRef dt:referenceURI="..."/&gt;
        &lt;dt:IsSpecializationOfRef dt:referenceURI="..."/&gt;
    &lt;/dt:Property&gt;
    &lt;dt:ReferenceDocument dt:GUID="..." dateOfCreation="..."&gt;
        &lt;dt:Name language="en"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="en"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:Status&gt;...&lt;/dt:Status&gt;
        &lt;dt:Language&gt;en&lt;/dt:Language&gt;
        &lt;dt:URI&gt;https://pdts.pt/referencedocumentview/{guid}&lt;/dt:URI&gt;
    &lt;/dt:ReferenceDocument&gt;
&lt;/dt:Library&gt;</code></pre>
            </div>
           <p class="text-sm text-gray-600 my-2"><strong>Exemplo:</strong> <code>GET /api/1/xml</code></p>
          

            <!-- Additional Endpoints -->
            <h2 class="my-4 mt-8">Obter Todos os Modelos de Dados de Produtos</h2>
            <p class="mb-4">Retorna todos os templates de dados de produtos.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/productDataTemplates</code></pre>
            </div>

            <h2 class="my-4">Obter todas as propriedades do Dicionário de Dados</h2>
            <p class="mb-4">Retorna todas as propriedades do dicionário de dados dos modelos de dados do produto.</p>
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
            <p class="mb-4">Retorna todos os objectos de construção.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/constructionObjects</code></pre>
            </div>

        </div>
    </div>
</x-app-layout>