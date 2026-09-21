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

    // ------------------------------------------------------- PDT categories
    // The stored category vocabulary (productdatatemplates.category), which is
    // Portuguese and has no English column. Only the LABEL is translated — the
    // stored value stays the filter key, so filtering is unaffected.
    'Construção' => 'Construction',
    'Material de Construção' => 'Construction material',
    'Obras Geotécnicas' => 'Geotechnical works',
    'Escavação e Estabilização' => 'Excavation and stabilisation',
    'Fundação e Estacas' => 'Foundations and piles',
    'Betão' => 'Concrete',
    'Estruturas de Madeira' => 'Timber structures',
    'Alvenaria e Tijolo' => 'Masonry and brick',
    'Paredes' => 'Walls',
    'Telhados' => 'Roofs',
    'Revestimento' => 'Cladding',
    'Isolamento' => 'Insulation',
    'Janelas' => 'Windows',
    'Portas' => 'Doors',
    'Pisos' => 'Floors',
    'Sanitário' => 'Sanitary',
    'Ferrovias' => 'Railways',
    'Vias Rodoviárias' => 'Roads',
    'Obras e Paisagismo' => 'Works and landscaping',
    'Sistemas de Segurança e Proteção' => 'Safety and protection systems',

    // ----------------------------------------------- account / auth pages
    'Palavra-passe' => 'Password',
    'Confirmar Palavra-passe' => 'Confirm password',
    'Palavra-passe atual' => 'Current password',
    'Nova palavra-passe' => 'New password',
    'Redefinir palavra-passe' => 'Reset password',
    'Confirmar' => 'Confirm',
    'Entrar' => 'Log in',
    'Sair' => 'Log out',
    'Guardar' => 'Save',
    'Guardado.' => 'Saved.',
    'Actualizada.' => 'Updated.',
    'Cancelar' => 'Cancel',
    'Foto' => 'Photo',
    'Profissão' => 'Profession',
    'Instituto' => 'Institution',
    'Lembrar-me' => 'Remember me',
    'Esqueceu a sua palavra-passe?' => 'Forgot your password?',
    'Já registado?' => 'Already registered?',
    'Registar' => 'Register',
    'Verifique o seu e-mail' => 'Verify your email',
    'Reenviar e-mail de verificação' => 'Resend verification email',
    'Informação de Perfil' => 'Profile information',
    'Apagar conta' => 'Delete account',
    'Subscrever' => 'Subscribe',
    'Actualizar palavra-passe' => 'Update password',
    'Esta é uma área segura da aplicação. Confirme a sua palavra-passe antes de continuar.' => 'This is a secure area of the application. Please confirm your password before continuing.',
    'Esqueceu-se da sua palavra-passe? Sem problema. Indique-nos o seu endereço de e-mail e enviar-lhe-emos uma ligação para definir uma nova.' => 'Forgot your password? No problem. Tell us your email address and we will send you a link to set a new one.',
    'Enviar ligação para redefinir a palavra-passe' => 'Email password reset link',
    'Ao registar-se nesta plataforma, está a concordar com a nossa política de privacidade.' => 'By registering on this platform you agree to our privacy policy.',
    'Obrigado por se inscrever! Antes de começar, poderia verificar o seu endereço electrónico clicando no link que lhe acabámos de enviar por correio electrónico? Se não recebeu o e-mail, enviar-lhe-emos de bom grado outro.' => 'Thanks for signing up! Before you start, could you verify your email address by clicking the link we have just sent you? If you did not receive it, we will gladly send another.',
    'Uma nova ligação de verificação foi enviada para o endereço de correio electrónico que nos forneceu durante o registo.' => 'A new verification link has been sent to the email address you gave when registering.',
    'Uma nova ligação de verificação foi enviada para o seu endereço de correio electrónico.' => 'A new verification link has been sent to your email address.',
    'O seu endereço de correio electrónico não é verificado.' => 'Your email address is not verified.',
    'Clique aqui para reenviar o e-mail de verificação.' => 'Click here to resend the verification email.',
    'Certifique-se de que a sua conta está a utilizar uma palavra-passe longa e aleatória para se manter segura.' => 'Make sure your account uses a long, random password to stay secure.',
    'Assim que a sua conta for apagada, o seu feedback sobre os PDTs permanecerá como feedback anónimo. Se desejar apagar o seu feedback, faça-o manualmente na página do inquérito PDT antes de apagar a sua conta.' => 'Once your account is deleted, your feedback on the PDTs remains as anonymous feedback. If you want your feedback removed, do so on the PDT survey page before deleting your account.',
    'Tem a certeza de que quer eliminar a sua conta?' => 'Are you sure you want to delete your account?',
    'Assim que a sua conta for apagada, todos os seus comentários sobre os PDTs tornar-se-ão anónimos. Por favor, introduza a sua palavra-passe para confirmar que gostaria de apagar permanentemente a sua conta.' => 'Once your account is deleted, all your comments on the PDTs become anonymous. Please enter your password to confirm you want to delete your account permanently.',
    'Ao subscrever, só receberá notificações por e-mail quando houver um novo feedback sobre um Modelo de Dados de Produto ao qual tenha previamente adicionado feedback.' => 'If you subscribe, you will only be emailed when there is new feedback on a Product Data Template you have given feedback on before.',

    'Actualize a informação do perfil e endereço de correio electrónico da sua conta.'
        => 'Update the profile information and email address on your account.',
    'Actualize a fotografia da sua conta.' => 'Update your account photo.',

    // ------------------------------------------------------------- resolver
    'Identificador não resolvido' => 'Identifier not resolved',
    'Identificador ambíguo' => 'Ambiguous identifier',
];
