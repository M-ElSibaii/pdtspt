<x-app-layout>
    
    <x-carousel />

    <div class="container py-5">
        <div class="row py-5">
            <div class="col-lg-4">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/downloadthumb.png')}}" alt="PDTs Downlaod">
                <h2>PDTs Downlaod</h2>
                <p class="mb-4">Pode descarregar qualquer um dos PDTs nos formatos:
                    CSV, XML, e JSON</p>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="Ver detalhes"/>
            </div><!-- /.col-lg-4 -->
            <div class="col-lg-4">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/surveythumb.png')}}" alt="PDTs Downlaod">
                <h2>Análise de PDTs</h2>
                <p class="mb-4">Dê o seu feedback sobre as propriedades e responda ao inquérito para nos ajudar a melhorá-lo</p>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="Ver detalhes"/>
            </div><!-- /.col-lg-4 -->
            <div class="col-lg-4">
                <img class="bd-placeholder-img rounded-circle mb-5" width="100" height="100" src="{{asset('/img/apithumb.png')}}" alt="PDTs Downlaod">
                <h2>Ligar aos PDTs</h2>
                <p  class="mb-4">Utilize o nosso API para se ligar à sua aplicação web ou plugin</p>
                <x-button-primary-pdts 
                    link="{{route('apidoc')}}"
                    title="Ver detalhes"/>
            </div><!-- /.col-lg-4 -->
        </div><!-- /.row -->
    </div>

    <div class="container py-5">
        <div class="card p-5 border-0" style="background-color: rgb(249,249,249);">
            <div class="flex flex-row">
                <div class="basis-2/3">
                    <div class="pb-2" style="width:100%;">
                        <h1>O que são os PDTS? </h1>
                        <br>
                        <h3>Algo que irá mudar a forma como a indústria funciona</h3>
                    </div>
                    <p>
                        A normalização é um dos pilares dos processos de implementação do BIM e é uma chave para melhorar a colaboração entre os atores da indústria. Um dos principais desafios no processo de normalização de dados para produtos de construção é a definição de informação relevante "não gráfica" e a sua nomenclatura. A definição destas informações de uma forma normalizada pode ser realizada através da utilização de Modelos de Dados de Produtos, também conhecidos como PDT (Product Data Templates).
                        <br>
                        <br>
                        Um PDT pode simplificamente ser descrito como uma estrutura de dados que visa antecipar a informação necessária por todos os intervenientes envolvidos num determinado produto em todo o seu ciclo de vida. A criação de uma PDT envolve a recolha de dados de fontes como normas harmonizadas, conjuntos de propriedades IFC, Regulamento Ambiental de Produtos, Declaração de Desempenho, COBIE, e outras fontes relevantes.
                        <br>
                        <br>
                        Uma vez que um PDT é preenchido por um utilizador, torna-se uma Ficha de Dados do Produto (PDS). Uma PDS pode ser utilizada pelo seu criador, como um fabricante, no seu website, objectos BIM, e bibliotecas de objectos online. O formato digital do PDS permite aos seus utilizadores automatizar as suas operações de dados e integrá-los sem problemas nos processos BIM.
                    </p>
                </div>
                <div class="basis-1/3 content-end">
                    <img 
                        class="h-auto max-w-full max-h-[500px]" 
                        src="{{asset('/img/initiative.jpg')}}" 
                        alt="PDT">
                </div>
            </div>
        </div>
    </div>

    <!-- START THE FEATURETTES -->
    <div class="container py-5">
        <div class="py-5">
            <div class="text-center">
                <h1>A iniciativa portuguesa</h1>
                <br>
                <p>
                    Embora existam algumas iniciativas a nível internacional para a definição de PDT, este é ainda um processo em amadurecimento, particularmente em face do recente aparecimento de normas internacionais como a ISO 23386 e a ISO 23387 que visam a normalização do processo de criação de PDT’s e da sua ligação através de dicionários de dados.
                    <br><br>No contexto da comissão CT197 e dos projetos de investigação SECCLASS e REV@Construction, a equipa liderada pela Universidade do Minho está a iniciar esforços comuns para a criação de PDT a nível nacional e a descrevê-los em pormenor com o objetivo de mobilizar a indústria em geral. 
                    <br>
                </p>
                <br>
                <div class="image-container grid-cols-3 content-center">
                    <a href="http://www.ct197.pt/" target="_blank" rel="noopener noreferrer">
                        <img class="inline-block align-middle" src="img/ct197.png" alt="" style="height: 70px; ">
                    </a>
                    <a href="https://secclass.pt/" target="_blank" rel="noopener noreferrer">
                        <img class="inline-block align-middle" src="img/SECClasS-Logo-Website.png" alt="" style="height: 60px; ">
                    </a>
                    <a href="https://revconstruction.pt/" target="_blank" rel="noopener noreferrer">
                        <img class="inline-block align-middle" src="img/rev-construction-v.png" alt="" style="height: 60px; ">
                    </a>
                </div>
                <br>
                <div class="grid-cols-2">
                    <x-button-primary-pdts 
                        link="{{route('dashboard')}}"
                        title="Junte-se a nós"/>
                    <x-button-primary-pdts 
                        link="{{route('participantes')}}"
                        title="Participantes"/>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="py-5">
            <div>
                <h1>As fontes de informação </h1>
                <br>
                <div class="flex flex-row gap-4">
                    <div class="basis-1/3">
                        <img class="w-full h-auto max-h-[350px]" src="{{asset('/img/pdts-figure.svg')}}" alt="PDT">
                    </div>
                    <div class="basis-2/3 content-center">
                        <div class="flex flex-col">

                            <p class="text-left">
                                Serão tidos em conta vários recursos de dados, entre outros fatores, tais como os requisitos de interoperabilidade IFC, Regulamentos de Produtos de Construção (CPR), Declarações Ambientais de Produtos (EPD) e outras iniciativas de normalização. Estes esforços envolverão a consulta direta a vários intervenientes na cadeia de valor para assegurar que os PDT criados reúnam consenso generalizado na indústria AEC.
                            </p>
                            <div class="flex flex-row gap-4">
                                <x-card-image 
                                    card_image="{{asset('/img/IFC_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://buildingsmart.pt/"
                                    card_link_title="Ver mais"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/EPD.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://www.environdec.com/home"
                                    card_link_title="Ver mais"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/CPR_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://single-market-economy.ec.europa.eu/sectors/construction/construction-products-regulation-cpr_en"
                                    card_link_title="Ver mais"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/bSDD_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://single-market-economy.ec.europa.eu/sectors/construction/construction-products-regulation-cpr_en"
                                    card_link_title="Ver mais"
                                    />
                                <x-card-image 
                                    card_image="{{asset('/img/COBie_logo.png')}}" 
                                    card_title=""
                                    card_description=""
                                    card_link="https://single-market-economy.ec.europa.eu/sectors/construction/construction-products-regulation-cpr_en"
                                    card_link_title="Ver mais"
                                    />
                                
                                
                            </div>
                        </div>
                    </div>
                </div>         
            </div>
        </div>
    </div>
    <div class="py-5" style="background-color: white;">
        <div class="container py-5">
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h1>Como pode ajudar? </h1>
                    <h2>junte-se a nós</h2>
                    <br>
                    <p>
                        Cada actor da indústria da construção tem uma perspectiva sobre o tipo de dados que devem estar presentes num produto/sistema de construção. Assim, cada pessoa envolvida na indústria da construção pode contribuir para a especificação das propriedades dos produtos de construção.<br><br>
                        Esta iniciativa dá-lhe a oportunidade de ser ouvido/a, e de a sua opinião ser tida em conta. Uma vez registado/a nesta plataforma, terá acesso a uma variedade de Modelos de Dados de Produtos de diferentes produtos de construção. Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.<br><br>
                        Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.
                    </p>
                </div>


                <div class="basis-1/2">
                    <div class="flex flex-col">
                        <div class="content-center">
                            <x-button-primary-pdts 
                                link="{{route('dashboard')}}"
                                title="Junte-se a nós"/>    
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