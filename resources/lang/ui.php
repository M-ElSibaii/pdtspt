<?php

/*
|--------------------------------------------------------------------------
| UI chrome: Portuguese source -> English
|--------------------------------------------------------------------------
|
| Only interface wording lives here — labels, headings, buttons, table
| headers. Record content (names, definitions, descriptions) is never
| translated here: it is read from the language columns the data already
| holds (nameEn/namePt, definitionEn/definitionPt, nameEnSc/namePtSc) via
| App\Support\Lang::f().
|
| The Portuguese string is the key, so anything without an entry renders in
| Portuguese rather than showing a key.
|
*/

return [

    // ---------------------------------------------------------------- nav
    'Home' => 'Home',
    'PDTs' => 'PDTs',
    'Documentação API' => 'API documentation',
    'Participantes' => 'Participants',
    'Publicações' => 'Publications',
    'Contactos' => 'Contact',
    'Admin' => 'Admin',
    'Login' => 'Login',
    'Registo' => 'Register',
    'Logout' => 'Logout',
    'Perfil' => 'Profile',
    'Idioma' => 'Language',
    'Português' => 'Portuguese',
    'Inglês' => 'English',

    // ------------------------------------------------------------ footer
    'Todos os direitos reservados' => 'All rights reserved',
    'Política de privacidade' => 'Privacy policy',
    'Back to top' => 'Back to top',

    // ----------------------------------------------------------- generic
    'Nome' => 'Name',
    'Nome En' => 'Name (EN)',
    'Nome Pt' => 'Name (PT)',
    'Nome En Código' => 'Name (EN, code form)',
    'Nome Pt Código' => 'Name (PT, code form)',
    'Título' => 'Title',
    'Descrição' => 'Description',
    'Descrição En' => 'Description (EN)',
    'Descrição Pt' => 'Description (PT)',
    'Definição' => 'Definition',
    'Estado' => 'Status',
    'Versão' => 'Version',
    'Versões' => 'Versions',
    'Revisão' => 'Review',
    'Revisão (número)' => 'Revision',
    'Imagem' => 'Image',
    'Categoria' => 'Category',
    'Categorias' => 'Categories',
    'Unidade' => 'Unit',
    'Unidades' => 'Units',
    'Propriedade' => 'Property',
    'Propriedades' => 'Properties',
    'Tipo de dados' => 'Data type',
    'Documento de referência' => 'Reference document',
    'Data de criação' => 'Date of creation',
    'Data de ativação' => 'Date of activation',
    'Data da última alteração' => 'Date of last change',
    'Data de revisão' => 'Date of revision',
    'Data da versão' => 'Date of version',
    'Data de Revisão' => 'Date of revision',
    'Data da Versão' => 'Date of version',
    'Anterior' => 'Back',
    'Ver' => 'View',
    'Ver PDT' => 'View PDT',
    'Sem unidade' => 'No unit',
    'Nenhuma versão anterior' => 'No earlier versions',
    'Ativa' => 'Active',
    'Inativa' => 'Inactive',
    'Pré-visualização' => 'Preview',
    'Grupo de propriedades' => 'Group of properties',
    'Grupos de Propriedades' => 'Groups of properties',
    'Modelo de dados' => 'Data template',
    'Modelo de Dados (PDT)' => 'Data template (PDT)',
    'Pesquisar PDTs...' => 'Search PDTs...',

    // ------------------------------------------------------------ dashboard
    'Os Modelos de Dados dos Produtos' => 'Product Data Templates',

    // -------------------------------------------------------------- pdtview
    'Atributos do Modelo de Dados de Produto baseado em EN ISO 23387'
        => 'Product data template attributes, per EN ISO 23387',
    'ObjectType (Tipo de Objeto)' => 'ObjectType',
    'Subtipo de (Object Type)' => 'Subtype of',
    'Lista de versões anteriores' => 'Earlier versions',
    ':count grupos' => ':count groups',
    '(inclui :count herdado(s) de supertipos — IsSubtypeOf)'
        => '(includes :count inherited from supertypes — IsSubtypeOf)',

    // ---------------------------------------------------------- pdtsdownload
    'Modelo de Dados do Produto baseado na EN ISO 23387'
        => 'Product data template, per EN ISO 23387',
    'Código de cores — origem das propriedades (relação IsSubtypeOf):'
        => 'Colour key — where each property comes from (IsSubtypeOf relation):',
    'Próprio (deste modelo)' => 'Own (this template)',
    'Herdado de: :source' => 'Inherited from: :source',
    'Próprio' => 'Own',
    'Origem' => 'Source',
    'Nota: Este modelo de dados não herda propriedades de supertipos.'
        => 'Note: this data template inherits no properties from supertypes.',
    'Erro ao descarregar JSON: ' => 'Could not download the JSON: ',
    'Erro ao descarregar XML: ' => 'Could not download the XML: ',

    // ------------------------------------------------------- datadictionary
    'Atributos de propriedade no dicionário de dados baseado em EN ISO 23386'
        => 'Property attributes in the data dictionary, per EN ISO 23386',
    'Atributos de grupo de propriedades no dicionário de dados baseado em EN ISO 23386'
        => 'Group-of-properties attributes in the data dictionary, per EN ISO 23386',
    'Representação Visual' => 'Visual representation',
    'Representação visual' => 'Visual representation',
    'Lista de propriedades substituídas' => 'Replaced properties',
    'Lista de propriedades de substituição' => 'Replacing properties',
    'Lista de grupo de propriedades substituídas' => 'Replaced groups of properties',
    'Lista de grupo de propriedades de substituição' => 'Replacing groups of properties',
    'Relação com outros dicionários de dados' => 'Relation to other data dictionaries',
    'Língua dos criadores' => 'Creator’s language',
    'País de utilização' => 'Country of use',
    'País de origem' => 'Country of origin',
    'Quantidade física' => 'Physical quantity',
    'Dimensão' => 'Dimension',
    'Propriedade dinâmica' => 'Dynamic property',
    'Parametros da propriedade dinâmica' => 'Parameters of the dynamic property',
    'Nomes dos valores de definição' => 'Names of the defining values',
    'Valores de definição' => 'Defining values',
    'Tolerância' => 'Tolerance',
    'Formato digital' => 'Digital format',
    'Formato de texto' => 'Text format',
    'Lista de valores possíveis na língua n' => 'List of possible values in language n',
    'Valores-limite' => 'Boundary values',
    'Explicação da depreciação' => 'Deprecation explanation',
    'Data de depreciação' => 'Deprecation date',
    'Categoria de Grupo de propriedades' => 'Category of group of properties',
    'Grupo de propriedades-mãe' => 'Parent group of properties',
    'Propriedade presente em:' => 'This property appears in:',
    'Grupo de Propriedades presente em:' => 'This group of properties appears in:',
    'Descrição da propriedade' => 'Property description',
    'Descrição da Grupo de Propriedades' => 'Group-of-properties description',

    // ------------------------------------------------------ classpropertyview
    'Propriedade em Classe' => 'Class property',
    'Contexto' => 'Context',
    'Class de Propriedade ID' => 'Class property id',
    'Descrição na Classe' => 'Description in this class',
    'Descrição na Classe En' => 'Description in this class (EN)',
    'Ver Propriedade no Dicionário de Dados' => 'View this property in the data dictionary',

    // ------------------------------------------------- referencedocumentview
    'Atributos do documento de referência:' => 'Reference document attributes:',
    'Propriedades que utilizam este documento de referência:'
        => 'Properties that cite this reference document:',

    // ---------------------------------------------------------------- survey
    'Análises e comentários do Modelo de Dados do Produto'
        => 'Review and comments on the product data template',
    'O objetivo deste questionário é apoiar o consenso da indústria rumo a PDTs uniformizados a nível nacional'
        => 'This questionnaire supports industry consensus towards nationally uniform PDTs',
    'Questão' => 'Question',
    'Comentários' => 'Comments',
    'Sim' => 'Yes',
    'Não' => 'No',
    'Sem opinião' => 'No opinion',
    'Guardar Respostas' => 'Save answers',

    // ------------------------------------------------- construction object
    'Atributos do objeto de construção (ObjectType) baseado em EN ISO 23387'
        => 'Construction object (ObjectType) attributes, per EN ISO 23387',

    // ------------------------------------------------------------- resolver
    'Identificador não resolvido' => 'Identifier not resolved',
    'Identificador ambíguo' => 'Ambiguous identifier',
];
