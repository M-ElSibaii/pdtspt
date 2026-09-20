<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>API de Modelos de Dados de Produtos</h1>
            
            <!-- Main PDT Endpoint -->

            <h2 class="my-4">{{ Lg::pick('Identificadores', 'Identifiers') }}</h2>
            <p class="mb-2">{{ Lg::pick(
                'Cada registo do dicionário tem um identificador único e resolúvel. É esta a forma que aparece em todas as exportações, declarações e no campo uri de cada registo devolvido pela API.',
                'Every record in the dictionary has one resolvable identifier. This is the form that appears in every export, in declarations, and in the uri field of every record the API returns.'
            ) }}</p>
<pre class="border-solid border-2 border-slate-300 p-3 my-2 overflow-auto">https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/{entity}/{code}
https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/{entity}/{code}/v{versionNumber}</pre>
            <p class="mb-2">{{ Lg::pick(
                'A forma sem versão é a canónica e resolve sempre para o estado atual do registo; a forma /v{n} fixa uma versão conhecida. "latest" é aceite no lugar da versão do dicionário e reencaminha para a versão em vigor. O idioma nunca altera o identificador de um registo.',
                'The unversioned form is canonical and always resolves to the record\'s current state; the /v{n} form pins a known version. "latest" is accepted in place of the dictionary version and redirects to the current release. The language never changes a record\'s identifier.'
            ) }}</p>
            <table id="tblprop" class="my-3" cellpadding="0" cellspacing="0">
                <tr>
                    <th style="text-align:left!important;">{{ Lg::pick('Entidade', 'Entity') }}</th>
                    <th style="text-align:left!important;">{{ Lg::pick('Segmento', 'Segment') }}</th>
                    <th style="text-align:left!important;">{{ Lg::pick('Código', 'Code') }}</th>
                    <th style="text-align:left!important;">{{ Lg::pick('Exemplo', 'Example') }}</th>
                </tr>
                @php
                    $sample = \App\Services\UriService::sampleCodes();
                    $rows = [
                        ['prop', Lg::pick('Propriedade', 'Property'), 'Name'],
                        ['dt', Lg::pick('Modelo de dados de produto', 'Product data template'), 'Name'],
                        ['class', Lg::pick('Objeto de construção', 'Construction object'), 'Name'],
                        ['gop', Lg::pick('Grupo de propriedades', 'Group of properties'), '{id}-Name'],
                        ['classprop', Lg::pick('Propriedade em classe', 'Class property'), '{classPropertyId}-Name'],
                        ['doc', Lg::pick('Documento de referência', 'Reference document'), 'Name'],
                        ['unit', Lg::pick('Unidade', 'Unit'), 'Name'],
                        ['pq', Lg::pick('Grandeza física', 'Quantity kind'), 'Name'],
                        ['enum', Lg::pick('Valor enumerado', 'Enumerated value'), '{id}-Name'],
                    ];
                @endphp
                @foreach ($rows as [$entity, $label, $shape])
                <tr>
                    <td>{{ $label }}</td>
                    <td><code>{{ $entity }}</code></td>
                    <td><code>{{ $shape }}</code></td>
                    <td>
                        @if (!empty($sample[$entity]))
                            <a href="{{ Uri::link($entity, $sample[$entity]) }}"><code>{{ Uri::uri($entity, $sample[$entity]) }}</code></a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
            <p class="mb-4 text-sm text-gray-600">{{ Lg::pick(
                'Onde os nomes se repetem legitimamente (grupos de propriedades, propriedades em classe, valores enumerados) o identificador inclui o id do registo. Qualquer identificador pedido com Accept: application/json, ?format=json ou sufixo .json devolve o registo em JSON.',
                'Where names legitimately repeat (groups of properties, class properties, enumerated values) the identifier carries the record id. Any identifier requested with Accept: application/json, ?format=json or a .json suffix returns the record as JSON.'
            ) }}</p>

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
        "IsSubtypeOfRef": { "referenceURI": "https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/dt/{Name}" },
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
        "DimensionRef": { "dt:GUID": "..." },
        "UnitRef": [ { "dt:GUID": "...", "referenceURI": "..." } ],
        "QuantityKindRef": [ { "dt:GUID": "...", "referenceURI": "..." } ],
        "_physicalQuantity": "millimetre | en.EN",
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
        "URI": "https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/doc/{Name}",
        "Language": "en",
        "dt:GUID": "...",
        "dateOfCreation": "..."
    }]
}}</code></pre>
            </div>
           <p class="text-sm text-gray-600 my-2"><strong>Exemplo:</strong> <code>GET /api/1/json</code></p>
           <p class="text-sm text-gray-600 my-2"><strong>Unidades / grandeza física / dimensão:</strong>
              a unidade é referenciada por <code>UnitRef</code> (GUID estável, e <code>referenceURI</code> QUDT quando disponível);
              a grandeza física por <code>QuantityKindRef</code> e a dimensão por <code>DimensionRef</code>.
              O campo <code>_physicalQuantity</code> apresenta o par ISO 23386 "grandeza física | idioma"
              (ex. <code>"millimetre | en.EN"</code>), ou <code>"without"</code> para propriedades sem unidade / de texto.</p>

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
        &lt;dt:IsSubtypeOfRef dt:referenceURI="https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/dt/{Name}"/&gt;
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
        &lt;dt:DataType name="STRING"/&gt;
        &lt;dt:DimensionRef dt:GUID="..."/&gt;
        &lt;dt:UnitRef dt:GUID="..." dt:referenceURI="..."/&gt;
        &lt;dt:QuantityKindRef dt:GUID="..." dt:referenceURI="..."/&gt;
        &lt;dt:IsDependentOnRef dt:referenceURI="..."/&gt;
        &lt;dt:IsSpecializationOfRef dt:referenceURI="..."/&gt;
    &lt;/dt:Property&gt;
    &lt;dt:ReferenceDocument dt:GUID="..." dateOfCreation="..."&gt;
        &lt;dt:Name language="en"&gt;...&lt;/dt:Name&gt;
        &lt;dt:Definition language="en"&gt;...&lt;/dt:Definition&gt;
        &lt;dt:Status&gt;...&lt;/dt:Status&gt;
        &lt;dt:Language&gt;en&lt;/dt:Language&gt;
        &lt;dt:URI&gt;https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/doc/{Name}&lt;/dt:URI&gt;
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

            <!-- Units reference collections (ISO 23387) -->
            <h2 class="my-4 mt-8">Obter Todas as Unidades</h2>
            <p class="mb-4">Retorna todas as unidades (ISO 23387 UnitType), cada uma com o seu URI de identidade resolúvel (<code>@id</code>), grandeza física e dimensão associadas, e a ligação à autoridade externa QUDT (<code>sameAs</code>, quando disponível).</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/units</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> JSON</p>
            <div style="background-color: #F0F4F8; margin: 10px 0;">
                <pre><code>[{
    "@id": "https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/unit/mm",
    "type": "Unit",
    "guid": "...",
    "code": "mm",
    "name": "millimetre",
    "physicalQuantity": { "name": "length", "languageIsoCode": "en.EN", "@id": "https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/pq/length" },
    "dimension": { "canonical": "L", "@id": "https://pdts.pt/dimension/L" },
    "sameAs": "http://qudt.org/vocab/unit/MilliM"
}]</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Detalhe:</strong> siga o <code>@id</code> de cada unidade para <code>GET /api/unit/{code}</code> (representação completa, incl. escala/base/coeficiente).</p>

            <h2 class="my-4 mt-8">Obter Todas as Grandezas Físicas</h2>
            <p class="mb-4">Retorna todas as grandezas físicas (ISO 23387 QuantityKindType), cada uma com o seu URI de identidade (<code>@id</code>) e a dimensão associada.</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/quantityKinds</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> JSON</p>
            <div style="background-color: #F0F4F8; margin: 10px 0;">
                <pre><code>[{
    "@id": "https://pdts.pt/uri/{{ \App\Services\UriService::dictionaryVersion() }}/pq/length",
    "type": "QuantityKind",
    "guid": "...",
    "name": "length",
    "languageIsoCode": "en.EN",
    "dimension": { "canonical": "L", "@id": "https://pdts.pt/dimension/L" },
    "sameAs": null
}]</code></pre>
            </div>

            <h2 class="my-4 mt-8">Obter Todas as Dimensões</h2>
            <p class="mb-4">Retorna todas as dimensões (ISO 23387 DimensionType), cada uma com o seu URI de identidade (<code>@id</code>) e os 7 expoentes SI (ordem ISO 80000).</p>
            <div style="background-color: #F0F4F8;">
                <pre><code>GET /api/dimensions</code></pre>
            </div>
            <p class="text-sm text-gray-600 my-2"><strong>Formato:</strong> JSON</p>
            <div style="background-color: #F0F4F8; margin: 10px 0;">
                <pre><code>[{
    "@id": "https://pdts.pt/dimension/L",
    "type": "Dimension",
    "guid": "...",
    "canonical": "L",
    "exponents": {
      "Length": "1.000", "Mass": "0.000", "Time": "0.000", "ElectricCurrent": "0.000",
      "ThermodynamicTemperature": "0.000", "AmountOfSubstance": "0.000", "LuminousIntensity": "0.000"
    },
    "sameAs": null
}]</code></pre>
            </div>

        </div>
    </div>
</x-app-layout>