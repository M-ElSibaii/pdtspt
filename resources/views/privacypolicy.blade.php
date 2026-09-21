<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9">
            <h1>{{ Lg::pick('Política de privacidade', 'Privacy policy') }}</h1>

            @if (Lg::isEn())
                {{-- This is a courtesy translation. The policy is issued in Portuguese
                     and the Portuguese text is the one that has legal effect. --}}
                <p style="font-size:13px; background:#fef9c3; border-left:4px solid #ca8a04; padding:10px 14px;">
                    This English text is a translation provided for convenience. The policy is
                    issued in Portuguese and, in the event of any discrepancy, the
                    <a href="{{ Lg::toggleUrl('pt') }}" style="text-decoration:underline;">Portuguese version</a>
                    prevails.
                </p>
            @endif

            <h2>{{ Lg::pick('Introdução', 'Introduction') }}</h2>

            <p>@if (Lg::isEn())
The European Union's General Data Protection Regulation (GDPR) — Regulation (EU) 2016/679 of the European Parliament and of the Council of 27 April 2016 — lays down the rules on the protection of natural persons' personal data. It applies directly in the legal order of every Member State and imposes a series of duties, in particular on public legal persons. In Portugal, Law no. 58/2019 of 8 August, which gives effect to the GDPR, is also in force.
@else
O Regulamento Geral sobre Proteção de Dados Pessoais da União Europeia (RGPD) – Regulamento (UE) 2016/679 do Parlamento Europeu e do Conselho de 27 de abril de 2016, estabelece as regras relativas à proteção de dados pessoais de pessoas singulares, sendo aplicável diretamente na ordem jurídica de todos os Estados-Membros, e impondo uma série de deveres que se destinam, designadamente, a pessoas coletivas públicas. Em Portugal, encontra-se ainda em vigor a Lei n.º 58/2019, de 8 de agosto, que assegura a execução do RGPD.
@endif</p>

            <h2>{{ Lg::pick('Âmbito', 'Scope') }}</h2>
            <p>@if (Lg::isEn())
This Privacy Policy applies to the data collected and processed by BuildingSMART Portugal in the course of its activities, in particular through the website and the members' area.
@else
A Política de Privacidade aplica-se aos dados recolhidos e tratados pela BuildingSMART Portugal no exercício das suas atividades, designadamente através do website e area reservada para associados.
@endif</p>

            <h2>{{ Lg::pick('Dados Pessoais', 'Personal data') }}</h2>
            <p>@if (Lg::isEn())
"Personal data" means any information relating to an identified or identifiable natural person. A natural person is identifiable where they can be identified, directly or indirectly, in particular by reference to an identifier such as a name, location data, or factors specific to their physical or physiological identity, and so on.
@else
Consideram-se «dados pessoais» qualquer informação relativa a uma pessoa singular identificada ou identificável. Por seu turno, é considerada identificável a pessoa singular suscetível de ser identificada, direta ou indiretamente, especialmente através de um referenciador como seja o nome, dados de localização, elementos específicos da integridade física, fisiológica, etc.
@endif</p>

            <p>@if (Lg::isEn())
The specific kinds of personal data processed by PDTs.pt relate directly to its activities and are varied: they may include name, civil identification number, tax number, address, email address, telephone and mobile number, professional category or role, the department in which the person works, and others.
@else
As tipologias concretas de dados pessoais objeto de tratamento por parte da PDTs.pt, encontram-se diretamente relacionados com as suas atividades e são muito diversas, aqui podendo contar-se nome, número de identificação civil, NIF, morada, correio eletrónico, número de telefone e de telemóvel, categoria profissional ou cargo desempenhado, serviço onde se desempenha funções, entre outros.
@endif</p>

            <h2>{{ Lg::pick('Outras definições relevantes', 'Other relevant definitions') }}</h2>
            @if (Lg::isEn())
Other concepts are relevant to this Policy and should also be defined. Specifically:
@else
Com relevância para a presente Política, avultam ainda outros conceitos, a cuja definição importa proceder. Assim, concretamente:
@endif

            <p>
                @if (Lg::isEn())
Processing — an operation, or set of operations, performed on sets of personal data, by automated or non-automated means, such as collection, recording, organisation, erasure or alteration;
                Controller — the natural person, legal person, public authority, agency or other body which, alone or jointly with others, determines the purposes and means of processing personal data;
                Consent — a freely given, specific, informed and explicit indication of will by which the data subject accepts, by a statement or a clear affirmative action, that personal data concerning them be processed.
@else
Tratamento – corresponde a uma operação, ou um conjunto de operações efetuadas sobre conjuntos de dados pessoais, por meios automatizados ou não automatizados, como sejam a recolha, o registo, a organização, ou apagamento ou a alteração;
                Responsável pelo Tratamento – corresponde à pessoa singular, pessoa coletiva, autoridade pública, agência ou outro organismo que, individualmente ou em conjunto com outras, determina as finalidades e os meios de tratamento de dados pessoais;
                Consentimento – corresponde a uma manifestação de vontade, livre, específica, informada e explícita, pela qual o titular dos dados aceita, mediante declaração ou ato positivo inequívoco, que os dados pessoais que lhe dizem respeito sejam objeto de tratamento.
@endif
            </p>
            <h2>{{ Lg::pick('Fundamento e Finalidade do tratamento de dados pessoais', 'Basis and purpose of processing personal data') }}</h2>
            <p>@if (Lg::isEn())
PDTs.pt collects and processes personal data in the course of its activities, in particular for the purposes of:
@else
A PDTs.pt procede à recolha e tratamento de dados pessoais para a prossecução das suas atividades, designadamente para efeitos de:
@endif</p>

            <p>
                @if (Lg::isEn())
Complying with legal obligations;
                Responding to enquiries;
                Handling membership applications;
                Online forms and questionnaires, in particular for assessing satisfaction with services;
                Registration by interested parties for events run by PDTs.pt (for example seminars, conferences or similar initiatives);
                Collecting activity indicators and information.
                Processing will only take place where it can rest on one of the lawful bases set out in the law and in the GDPR, namely compliance with a legal or contractual obligation, or the data subject's consent — which must be freely given, specific, informed and unambiguous — to collect and process that data for the specific purpose intended.

                In addition, all data processing operations will strictly observe the applicable legal principles, in particular as regards data flows, fairness, transparency, minimisation, and so on.
@else
Cumprimento de obrigações legais;
                Resposta a contactos;
                Instrução de processos de candidatura a associado;
                Formulários e questionários online, designadamente para avaliação de satisfação de serviços;
                Inscrição, pelos interessados, em ações realizadas pela PDTs.pt (p. ex., seminários, congressos ou iniciativas afins);
                Recolha de indicadores e informação de atividade.
                Por outro lado, o tratamento apenas ocorrerá na medida em que possa estribar-se em algum dos fundamentos de licitude previstos na Lei e no RGPD, nomeadamente, o cumprimento de uma obrigação legal ou contratual ou o consentimento do titular, necessariamente livre, específico, informado e inequívoco para proceder à recolha e tratamento desses dados para o concreto fim a que se destinam.

                A isto acresce que todas as operações de tratamento de dados se farão em estrita obediência aos princípios jurídicos aplicáveis, designadamente no âmbito da sua circulação, lealdade, transparência, minimização, etc.
@endif
            </p>

            <h2>{{ Lg::pick('Partilha e divulgação dos dados', 'Sharing and disclosure of data') }}</h2>
            <p>@if (Lg::isEn())
PDTs.pt shares personal data with third parties only where it has a legal basis to do so, such as the data subject's consent, compliance with legal obligations placed upon it, or the exercise of functions in the public interest.
@else
A PDTs.pt só partilha dados pessoais com terceiros se tiver fundamento legal para o efeito, como seja o consentimento do titular dos dados, o cumprimento de obrigações legais que lhe estão cometidas, ou o exercício de funções de interesse púbico.
@endif</p>

            <p>@if (Lg::isEn())
Outside those situations we do not transmit, sell or exchange your personal data with third parties; it is stored and processed only within the PDTs.pt technical infrastructure.
@else
Fora dessas situações, não transmitimos, vendemos ou trocamos os seus dados pessoais com terceiros, sendo que os mesmos serão apenas armazenados e tratados dentro da infraestrutura tecnológica da PDTs.pt.
@endif</p>

            <h2>{{ Lg::pick('Conservação dos dados', 'Data retention') }}</h2>
            <p>@if (Lg::isEn())
PDTs.pt retains the personal data it collects and processes only for the period laid down in the applicable legislation or, where the law is silent, for the time strictly necessary for the activities of this Association for which the data was collected.
@else
A PDTs.pt apenas conserva os dados pessoais recolhidos e tratados pelo período de tempo previsto na legislação aplicável ou, não havendo previsão legal, pelo hiato temporal estritamente necessário à observância das atividades desta Associação, que presidiram à sua recolha.
@endif</p>

            <h2>{{ Lg::pick('Segurança dos dados pessoais', 'Security of personal data') }}</h2>
            <p>@if (Lg::isEn())
PDTs.pt undertakes to guarantee the security and protection of the personal data it collects, and has adopted a series of technical and organisational measures to that end.
@else
A PDTs.pt assume o compromisso de garantir a segurança e proteção dos dados pessoais que recolhe, tendo, para o efeito, adotado uma série de medidas técnicas e organizativas.
@endif </p>

            <h2>{{ Lg::pick('Política de cookies', 'Cookie policy') }}</h2>
            <p>
                @if (Lg::isEn())
We use cookies to improve your browsing experience, to present personalised advertising or content, and to analyse our traffic. By clicking "Accept All" you agree to the use of cookies.

                We use cookies to help you navigate effectively and to perform certain functions. Detailed information about every cookie is given under each consent category below.

                Cookies categorised as "Necessary" are stored in your browser because they are essential to enable the site's basic functionality.

                We also use third-party cookies that help us analyse how you use this site, store your preferences and deliver the content and advertising relevant to you. These cookies are stored in your browser only with your prior consent.

                You may choose to enable or disable some or all of these cookies, but disabling some of them may affect your browsing experience.

                Our cookies have different functions:
                – Essential cookies – Some cookies are essential for reaching specific areas of our site. They allow you to navigate the site and use its applications, such as reaching restricted areas by logging in. Without these cookies, the services that require them cannot be provided.
                – Analytics cookies – We use these cookies to analyse how users use the site and to monitor its performance. This lets us provide a high-quality experience by tailoring what we offer and by quickly finding and fixing any problems that arise. For example, we use performance cookies to learn which pages are most popular, which way of linking between pages works best, or why some pages are returning error messages. These cookies are used only to compile and analyse statistics, and never collect personal information.
                – Functionality cookies – We use functionality cookies so that we can remember your preferences. In short, functionality cookies store your preferences about using the site, so that you do not have to set the site up again on every visit.

                We use 2 types of cookie:
                – Persistent cookies – These are stored by the browser on the devices you use (PC, mobile and tablet) and are used whenever you visit the site again. They are generally used to steer navigation according to the visitor's interests, letting us provide a more personalised service.
                – Session cookies – These are temporary and stay in your browser's cookies until you leave the site. The information obtained helps identify problems and provide a better browsing experience.

                Having allowed the use of cookies, you can always disable some or all of ours. Every browser lets a visitor accept, refuse or delete cookies through its settings. Note that if you disable cookies, parts of our site may not work correctly.
@else
Utilizamos cookies para melhorar a sua experiência de navegação, apresentar anúncios ou conteúdos personalizados e analisar o nosso tráfego. Ao clicar em “Aceitar Todos”, concorda com a utilização de cookies.

                Utilizamos cookies para ajudá-lo a navegar com eficácia e executar certas funções. Encontrará informações detalhadas sobre todos os cookies em cada categoria de consentimento abaixo.

                Os cookies categorizados como “Necessários” são armazenados no seu navegador, pois são essenciais para ativar as funcionalidades básicas do site.

                Também utilizamos cookies de terceiros que nos ajudam a analisar a forma como utiliza este site, armazenam as suas preferências e fornecem o conteúdo e os anúncios que são relevantes para si. Estes cookies apenas serão armazenados no seu navegador com o seu consentimento prévio.

                Pode escolher ativar ou desativar alguns ou todos estes cookies, mas desativar alguns deles pode afetar a sua experiência de navegação.

                Os nossos cookies têm diferentes funções:
                – Cookies essenciais – Alguns cookies são essenciais para aceder a áreas específicas do nosso site. Permitem a navegação no site e a utilização das suas aplicações, tal como aceder a áreas reservadas do site através de login. Sem estes cookies, os serviços que o exijam não podem ser prestados.
                – Cookies analíticos – Utilizamos estes cookies para analisar a forma como os utilizadores usam o site e monitorizar a performance deste. Isto permite-nos fornecer uma experiência de alta qualidade ao personalizar a nossa oferta e rapidamente identificar e corrigir quaisquer problemas que surjam. Por exemplo, usamos cookies de desempenho para saber quais as páginas mais populares, qual o método de ligação entre páginas que é mais eficaz, ou para determinar a razão de algumas páginas estarem a receber mensagens de erro. Estes cookies são utilizados apenas para efeitos de criação e análise estatística, sem nunca recolher informação de caráter pessoal.
                – Cookies de funcionalidade – Utilizamos cookies de funcionalidade para nos permitir relembrar as preferências do utilizador. Em resumo, os cookies de funcionalidade guardam as preferências do utilizador relativamente à utilização do site, de forma que não seja necessário voltar a configurar o site cada vez que o visita.

                Usamos 2 tipos de cookies:
                – Cookies permanentes -Ficam armazenados ao nível do navegador de internet (browser) nos seus dispositivos de acesso (pc, mobile e tablet) e são utilizados sempre que o utilizador faz uma nova visita ao site. Geralmente são utilizados para direcionar a navegação de acordo com os interesses do visitante, permitindo-nos prestar um serviço mais personalizado.
                – Cookies de sessão – São temporários, permanecem nos cookies do seu navegador de internet (browser) até sair do site. A informação obtida permite identificar problemas e fornecer uma melhor experiência de navegação.

                Depois de autorizar o uso de cookies, o utilizador pode sempre desativar parte ou a totalidade dos nossos cookies. Todos os navegadores de internet (browsers) permitem ao visitante aceitar, recusar ou apagar cookies, através da gestão das definições no respetivo navegador. Recordamos que ao desativar os cookies, partes do nosso site podem não funcionar corretamente.
@endif
            </p>

            <h2>{{ Lg::pick('Direitos dos utilizadores em relação aos dados', 'Users\' rights over their data') }}</h2>
            <p>@if (Lg::isEn())
The data subject has the right to ask PDTs.pt for access to the personal data concerning them, for its rectification or erasure, for restriction of its processing, and for data portability where technically possible. The data subject may object to processing, or withdraw consent previously given, at any time.
@else
O titular dos dados tem o direito de solicitar à PDTs.pt o acesso aos dados pessoais que lhe digam respeito, à sua retificação ou ao seu apagamento, à observância da limitação do tratamento dos seus dados e à portabilidade dos dados quando tecnicamente possível. O titular dos dados pode opor-se ao tratamento ou retirar, em qualquer momento, o consentimento previamente dado.
@endif</p>

            <h2>{{ Lg::pick('Como pode exercer os direitos', 'How to exercise these rights') }}</h2>
            <p>@if (Lg::isEn())
To exercise these rights, the data subject should send a request to the Data Protection Officer at: pdts.portugal@gmail.com
@else
Para exercer os seus direitos, o titular dos dados deve enviar um pedido para o Encarregado da Proteção de Dados para o seguinte contacto: pdts.portugal@gmail.com
@endif</p>

            <h2>{{ Lg::pick('Alterações à Política de Privacidade', 'Changes to this Privacy Policy') }}</h2>
            <p>@if (Lg::isEn())
This Privacy and Personal Data Protection Policy may be amended from time to time without the data subject's prior consent. Any significant change will be announced as publicly as the original version was.
@else
A Política de Privacidade e de Proteção de Dados Pessoais ora definida, pode ser alterada periodicamente sem necessidade de prévio consentimento do titular dos dados. Quaisquer alterações significativas serão comunicadas com o mesmo grau de publicidade que presidiu à divulgação da sua versão inicial.
@endif
                    
            <p style="font-size: 12px;">{{ Lg::pick('Nota: todas as imagens foram descarregadas de Freepik.com', 'Disclaimer: all images were downloaded from Freepik.com') }}</p>
                
        </div>
    </div>
</x-app-layout>