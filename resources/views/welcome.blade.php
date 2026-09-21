<x-app-layout>
    
    <x-carousel />

    <div class="container sm:max-w-full sm:px-2.5 py-5">
        <div class="grid lg:grid-cols-3 md:grid-cols-3 sm:grid-cols-1 gap-4 py-5">
            <div class="flex flex-col content-center">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/downloadthumb.png')}}" alt="PDTs Downlaod">
                <h2>{{ Lg::pick('Descarregar PDTs', 'Download PDTs') }}</h2>
                <p class="mb-4">{{ Lg::pick('Pode descarregar qualquer um dos PDTs nos formatos: CSV, XML, e JSON', 'Any PDT can be downloaded as CSV, XML or JSON') }}</p>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="{{ Lg::pick('Ver detalhes', 'See details') }}"/>
            </div><!-- /.col-lg-4 -->
            <div class="flex flex-col content-center">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/surveythumb.png')}}" alt="PDTs Downlaod">
                <h2>{{ Lg::pick('Análise de PDTs', 'PDT review') }}</h2>
                <p class="mb-4">{{ Lg::pick('Dê o seu feedback sobre as propriedades e responda ao inquérito para nos ajudar a melhorá-lo', 'Give your feedback on the properties and answer the survey to help us improve them') }}</p>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="{{ Lg::pick('Ver detalhes', 'See details') }}"/>
            </div><!-- /.col-lg-4 -->
            <div class="flex flex-col content-center">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/apithumb.png')}}" alt="PDTs Downlaod">
                <h2>{{ Lg::pick('Ligar aos PDTs', 'Connect to the PDTs') }}</h2>
                <p  class="mb-4">{{ Lg::pick('Utilize o nosso API para se ligar à sua aplicação web ou plugin', 'Use our API to connect from your web application or plugin') }}</p>
                <x-button-primary-pdts 
                    link="{{route('apidoc')}}"
                    title="{{ Lg::pick('Ver detalhes', 'See details') }}"/>
            </div><!-- /.col-lg-4 -->
        </div><!-- /.row -->
    </div>

    <div class="container sm:max-w-full sm:px-2.5 py-5">
        <div class="card pb-5 pt-5 pl-5 lg:md:pr-5 sm:pr-0 border-0" style="background-color: rgb(249,249,249);">
            <div class="flex lg:flex-row flex-col">
                <div class="basis-2/3">
                    <div class="pb-2" style="width:100%;">
                        <h1>{{ Lg::pick('O que são os PDTs?', 'What are PDTs?') }}</h1>
                        <br>
                        <h3>{{ Lg::pick('Algo que irá mudar a forma como a indústria funciona', 'Something that will change how the industry works') }}</h3>
                    </div>
                    <p>
                        @if (Lg::isEn())
Standardisation is one of the pillars of BIM implementation and a key to better collaboration between the industry's actors. One of the main challenges in standardising data for construction products is defining the relevant "non-graphical" information and its nomenclature. That information can be defined in a standardised way through Product Data Templates, or PDTs.
                        <br>
                        <br>
                        A PDT can be described simply as a data structure that sets out, in advance, the information everyone involved with a given product will need across its whole life cycle. Building a PDT means gathering data from sources such as harmonised standards, IFC property sets, environmental product regulations, declarations of performance, COBie and other relevant sources.
                        <br>
                        <br>
                        Once a user fills a PDT in, it becomes a Product Data Sheet (PDS). A PDS can be used by whoever created it — a manufacturer, for instance — on their website, in BIM objects and in online object libraries. Its digital format lets users automate their data operations and fold them into BIM processes without friction.
@else
A normalização é um dos pilares dos processos de implementação do BIM e é uma chave para melhorar a colaboração entre os atores da indústria. Um dos principais desafios no processo de normalização de dados para produtos de construção é a definição de informação relevante "não gráfica" e a sua nomenclatura. A definição destas informações de uma forma normalizada pode ser realizada através da utilização de Modelos de Dados de Produtos, também conhecidos como PDT (Product Data Templates).
                        <br>
                        <br>
                        Um PDT pode simplificamente ser descrito como uma estrutura de dados que visa antecipar a informação necessária por todos os intervenientes envolvidos num determinado produto em todo o seu ciclo de vida. A criação de uma PDT envolve a recolha de dados de fontes como normas harmonizadas, conjuntos de propriedades IFC, Regulamento Ambiental de Produtos, Declaração de Desempenho, COBIE, e outras fontes relevantes.
                        <br>
                        <br>
                        Uma vez que um PDT é preenchido por um utilizador, torna-se uma Ficha de Dados do Produto (PDS). Uma PDS pode ser utilizada pelo seu criador, como um fabricante, no seu website, objectos BIM, e bibliotecas de objectos online. O formato digital do PDS permite aos seus utilizadores automatizar as suas operações de dados e integrá-los sem problemas nos processos BIM.
@endif
                    </p>
                </div>
                <div class="basis-1/3 m-auto mb-0 content-end inline-block align-baseline">
                    <img 
                        class="h-auto max-w-full max-h-[500px]" 
                        src="{{asset('/img/initiative.jpg')}}" 
                        alt="PDT">
                </div>
            </div>
        </div>
    </div>

    <!-- START THE FEATURETTES -->
    <div class="container sm:max-w-full sm:px-2.5 py-5">
        <div class="py-5">
            <div class="text-center">
                <h1>{{ Lg::pick('A iniciativa portuguesa', 'The Portuguese initiative') }}</h1>
                <br>
                <p>
                    @if (Lg::isEn())
Although there are international initiatives defining PDTs, the process is still maturing, particularly given the recent arrival of international standards such as ISO 23386 and ISO 23387, which set out how PDTs are created and linked through data dictionaries.
                    <br><br>Within the CT197 committee and the SECCLASS and REV@Construction research projects, the team led by the University of Minho is starting joint work to create PDTs nationally and describe them in detail, with the aim of mobilising the industry at large.
                    <br>
@else
Embora existam algumas iniciativas a nível internacional para a definição de PDT, este é ainda um processo em amadurecimento, particularmente em face do recente aparecimento de normas internacionais como a ISO 23386 e a ISO 23387 que visam a normalização do processo de criação de PDT’s e da sua ligação através de dicionários de dados.
                    <br><br>No contexto da comissão CT197 e dos projetos de investigação SECCLASS e REV@Construction, a equipa liderada pela Universidade do Minho está a iniciar esforços comuns para a criação de PDT a nível nacional e a descrevê-los em pormenor com o objetivo de mobilizar a indústria em geral. 
                    <br>
@endif
                </p>
                <br>
                <div class="flex flex-row gap-4  content-center">
                    <a class="content-center" href="http://www.ct197.pt/" target="_blank" rel="noopener noreferrer">
                        <img 
                            class="h-auto w-auto max-h-[100px] max-w-[180px] sm:max-h-[50px] sm:max-w-[140px]" 
                            src="img/ct197.png" 
                            alt="" >
                    </a>
                    <a class="content-center" href="https://secclass.pt/" target="_blank" rel="noopener noreferrer">
                        <img 
                            class="h-auto w-auto max-h-[100px] max-w-[180px] sm:max-h-[50px] sm:max-w-[140px]" 
                            src="img/SECClasS-Logo-Website.png" 
                            alt="" >
                    </a>
                    <a class="content-center" href="https://revconstruction.pt/" target="_blank" rel="noopener noreferrer">
                        <img 
                            class="h-auto w-auto max-h-[100px] max-w-[180px] sm:max-h-[50px] sm:max-w-[140px]" 
                            src="img/rev-construction-v.png" 
                            alt="" >
                    </a>
                </div>
                <br>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-right">
                        <x-button-primary-pdts 
                            link="{{route('dashboard')}}"
                            title="{{ Lg::pick('Junte-se a nós', 'Join us') }}"/>
                    </div>
                    <div class="text-left">
                        <a href="{{route('participantes')}}">
                            <x-secondary-button >   
                                {{ Lg::t('Participantes') }}
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container sm:max-w-full sm:px-2.5 py-5">
        <div class="py-5">
            <div>
                <h1>{{ Lg::pick('As fontes de informação', 'The information sources') }}</h1>
                <br>
                <div class="flex lg:flex-row md:flex-col sm:flex-col gap-4">
                    <div class="basis-1/3">
                        <img class="w-full h-auto max-h-[350px]" src="{{asset('/img/pdts-figure.svg')}}" alt="PDT">
                    </div>
                    <div class="basis-2/3 content-center">
                        <div class="flex flex-col">

                            <p class="text-left">
                                @if (Lg::isEn())
Several data resources will be taken into account, among other factors: IFC interoperability requirements, the Construction Products Regulation (CPR), Environmental Product Declarations (EPD) and other standardisation initiatives. This work involves consulting stakeholders across the value chain directly, so that the PDTs created carry broad consensus in the AEC industry.
@else
Serão tidos em conta vários recursos de dados, entre outros fatores, tais como os requisitos de interoperabilidade IFC, Regulamentos de Produtos de Construção (CPR), Declarações Ambientais de Produtos (EPD) e outras iniciativas de normalização. Estes esforços envolverão a consulta direta a vários intervenientes na cadeia de valor para assegurar que os PDT criados reúnam consenso generalizado na indústria AEC.
@endif
                            </p>
                            <div class="grid lg:grid-cols-5 md:grid-cols-3 sm:grid-cols-2 gap-4">
                                <x-card-image 
                                    card_image="{{asset('/img/IFC_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://technical.buildingsmart.org/standards/ifc/ifc-schema-specifications/"
                                    card_link_title="{{ Lg::pick('Ver mais', 'See more') }}"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/EPD.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://www.environdec.com/home"
                                    card_link_title="{{ Lg::pick('Ver mais', 'See more') }}"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/CPR_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://single-market-economy.ec.europa.eu/sectors/construction/construction-products-regulation-cpr_en"
                                    card_link_title="{{ Lg::pick('Ver mais', 'See more') }}"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/bSDD_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://www.buildingsmart.org/users/services/buildingsmart-data-dictionary/"
                                    card_link_title="{{ Lg::pick('Ver mais', 'See more') }}"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/COBie_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://www.nibs.org/nbims/v3/cobie"
                                    card_link_title="{{ Lg::pick('Ver mais', 'See more') }}"
                                    />
                                
                                
                            </div>
                        </div>
                    </div>
                </div>         
            </div>
        </div>
    </div>
    <div class="py-5" style="background-color: white;">
        <div class="container sm:max-w-full sm:px-2.5 py-5">
            <div class="flex lg:flex-row flex-col">
                <div class="basis-1/2">
                    <h1>{{ Lg::pick('Como pode ajudar?', 'How can you help?') }}</h1>
                    <h2>{{ Lg::pick('junte-se a nós', 'join us') }}</h2>
                    <br>
                    <p>
                        @if (Lg::isEn())
Every actor in the construction industry has a view on what data a construction product or system should carry. So everyone working in the industry can contribute to specifying the properties of construction products.<br><br>
                        This initiative is your opportunity to be heard and to have your view taken into account. Once registered on this platform you have access to Product Data Templates for a range of construction products. All you have to do is answer the questionnaire and share your feedback, to help create Product Data Templates for construction products in the Portuguese industry.<br><br>
                        All you have to do is answer the questionnaire and share your feedback, to help create Product Data Templates for construction products in the Portuguese industry.
@else
Cada actor da indústria da construção tem uma perspectiva sobre o tipo de dados que devem estar presentes num produto/sistema de construção. Assim, cada pessoa envolvida na indústria da construção pode contribuir para a especificação das propriedades dos produtos de construção.<br><br>
                        Esta iniciativa dá-lhe a oportunidade de ser ouvido/a, e de a sua opinião ser tida em conta. Uma vez registado/a nesta plataforma, terá acesso a uma variedade de Modelos de Dados de Produtos de diferentes produtos de construção. Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.<br><br>
                        Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.
@endif
                    </p>
                </div>


                <div class="basis-1/2">
                    <div class="flex flex-col">
                        <div class="content-center">
                            <x-button-primary-pdts 
                                link="{{route('dashboard')}}"
                                title="{{ Lg::pick('Junte-se a nós', 'Join us') }}"/>    
                        </div>
                        <div class="content-end">
                            <img class="w-auto max-h-[300px]" src="{{asset('/img/surveyphoto.jpg')}}" alt="Survey">
                        </div>
                    </div>
                </div>
            </div>

            <!-- /END THE FEATURETTES -->

            <!-- /.container -->

        </div>
    </div>



</x-app-layout>