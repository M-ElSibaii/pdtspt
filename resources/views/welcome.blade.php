<x-app-layout>
    <style>
        .carousel-item {
            height: 32rem;
            background: #777;
            color: white;
            position: relative;
            
        }
        .container-slider{
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding-bottom: 50px;
        }
        .overlay-image{
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            top: 0;
            background-position: center;
            background-size: cover; 
            opacity: 0.5;
        }
    </style>
    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
        </ol>
        
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="container-slider">
                    <div class="overlay-image" style="backgroung-image: url({{asset('/img/standard.png')}});"></div>  
                    <div class="carousel-caption d-none d-md-block">
                        <h3>A normalização dos dados na indústria da contrução está a caminho</h3>
                        <h5>Aqui tem acesso a modelos de dados de produtos normalizados abertos</h5>
                        <a class="btn btn-secondary" href="{{route('dashboard')}}">Ver PDTs</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container-slider">
                    <div class="overlay-image" style="backgroung-image: url({{asset('/img/apibackground.png')}});"></div>  
                    <div class="carousel-caption d-none d-md-block">
                        <h3>As API's são a chave para ligar a indústria</h3>
                        <h5>Obtenha acesso a todos os Modelos de Dados de Produtos através do nosso API</h5>
                        <a class="btn btn-secondary" href="{{route('apidoc')}}">Documentação</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container-slider">
                    <div class="overlay-image" style="backgroung-image: url({{asset('/img/pdtpage.png')}});"></div>  
                    <div class="carousel-caption d-none d-md-block">
                        <h3>Faz parte da digitalização em construção em Portugal</h3>
                        <h5>Ver, rever, adicionar feedback e descarregar modelos de dados do produto</h5>
                        <a class="btn btn-secondary" href="{{route('dashboard')}}">Participar</a>
                    </div>
                </div>
            </div>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>


    <!-- Marketing messaging and featurettes
  ================================================== -->
    <!-- Wrap the rest of the page in another container to center all the content. -->
    <div class="container" style="padding-top: 40px">
        <div class="container marketing">

            <!-- Three columns of text below the carousel -->
            <div class="row">
                <div class="col-lg-4">
                    <img class="bd-placeholder-img rounded-circle" width="140" height="140" src="{{asset('/img/downloadthumb.png')}}" alt="PDTs Downlaod">
                    <h2 class="fw-normal">PDTs Downlaod</h2>
                    <p>Pode descarregar qualquer um dos PDTs nos formatos:
                        CSV, XML, e JSON</p>
                    <p><a class="btn btn-secondary" href="{{route('dashboard')}}">Ver detalhes &raquo;</a></p>
                </div><!-- /.col-lg-4 -->
                <div class="col-lg-4">
                    <img class="bd-placeholder-img rounded-circle" width="140" height="140" src="{{asset('/img/surveythumb.png')}}" alt="PDTs Downlaod">
                    <h2 class="fw-normal">Análise de PDTs</h2>
                    <p>Dê o seu feedback sobre as propriedades e responda ao inquérito para nos ajudar a melhorá-lo</p>
                    <p><a class="btn btn-secondary" href="{{route('dashboard')}}">Ver detalhes &raquo;</a></p>
                </div><!-- /.col-lg-4 -->
                <div class="col-lg-4">
                    <img class="bd-placeholder-img rounded-circle" width="140" height="140" src="{{asset('/img/apithumb.png')}}" alt="PDTs Downlaod">
                    <h2 class="fw-normal">Ligar aos PDTs</h2>
                    <p>Utilize o nosso API para se ligar à sua aplicação web ou plugin</p>
                    <p><a class="btn btn-secondary" href="{{route('apidoc')}}">Ver detalhes &raquo;</a></p>
                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->
        </div>
    </div>

    <!-- START THE FEATURETTES -->

    <hr class="featurette-divider">

    <div class="row featurette justify-content-center">
        <div class="col-md-7 text-center">
            <br>
            <h1 class="featurette-heading fw-normal lh-1">A iniciativa portuguesa</h1>
            <br>
            <p class="lead">
                Embora existam algumas iniciativas a nível internacional para a definição de PDT, este é ainda um processo em amadurecimento, particularmente em face do recente aparecimento de normas internacionais como a ISO 23386 e a ISO 23387 que visam a normalização do processo de criação de PDT’s e da sua ligação através de dicionários de dados.
                No contexto da comissão CT197 e dos projetos de investigação SECCLASS e REV@Construction, a equipa liderada pela Universidade do Minho está a iniciar esforços comuns para a criação de PDT a nível nacional e a descrevê-los em pormenor com o objetivo de mobilizar a indústria em geral. Serão tidos em conta vários recursos de dados, entre outros fatores, tais como os requisitos de interoperabilidade IFC, Regulamentos de Produtos de Construção (CPR), Declarações Ambientais de Produtos (EPD) e outras iniciativas de normalização. Estes esforços envolverão a consulta direta a vários intervenientes na cadeia de valor para assegurar que os PDT criados reúnam consenso generalizado na indústria AEC.
                <br>
            </p>
            <br>
            <div class="image-container">
                <a href="http://www.ct197.pt/" target="_blank" rel="noopener noreferrer"><img src="img/ct197-logo.png" alt="" style="height: 100px; "></a>
                <a href="https://secclass.pt/" target="_blank" rel="noopener noreferrer"><img src="img/SECClasS-Logo-Website.png" alt="" style="height: 90px; "></a>
                <a href="https://revconstruction.pt/" target="_blank" rel="noopener noreferrer"><img src="img/rev-logo-main-horizontal-dark.svg" alt="" style="height: 60px; "></a>
            </div>
            <br>
            <button class="btn btn-secondary" style="background-color: black;" href="{{route('dashboard')}}">Junte-se a nós &raquo;</button>
            &emsp; &emsp; &emsp;<button class="btn btn-secondary" style="background-color: black;" href="{{route('participantes')}}">Participantes &raquo;</button>
        </div>
    </div>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="row featurette" style="background-color: white;">
                    <h1 class="featurette-heading fw-normal lh-1 text-center">O que são os PDTS? </h1>
                    <br>
                    <h3 class="text-center">Algo que irá mudar a forma como a indústria funciona</h3>

                    <div class="col-md-7 order-md-1" style="padding-top: 20px;">

                        <p class="lead text-center">
                            &emsp;A normalização é um dos pilares dos processos de implementação do BIM e é uma chave para melhorar a colaboração entre os atores da indústria. Um dos principais desafios no processo de normalização de dados para produtos de construção é a definição de informação relevante "não gráfica" e a sua nomenclatura. A definição destas informações de uma forma normalizada pode ser realizada através da utilização de Modelos de Dados de Produtos, também conhecidos como PDT (Product Data Templates).
                            <br> &emsp;Um PDT pode simplificamente ser descrito como uma estrutura de dados que visa antecipar a informação necessária por todos os intervenientes envolvidos num determinado produto em todo o seu ciclo de vida. A criação de uma PDT envolve a recolha de dados de fontes como normas harmonizadas, conjuntos de propriedades IFC, Regulamento Ambiental de Produtos, Declaração de Desempenho, COBIE, e outras fontes relevantes.
                            <br> &emsp;Uma vez que um PDT é preenchido por um utilizador, torna-se uma Ficha de Dados do Produto (PDS). Uma PDS pode ser utilizada pelo seu criador, como um fabricante, no seu website, objectos BIM, e bibliotecas de objectos online. O formato digital do PDS permite aos seus utilizadores automatizar as suas operações de dados e integrá-los sem problemas nos processos BIM.
                        </p>
                    </div>
                    <div class="col-md-5 order-md-2" style="background-color: white;">
                        <img style="padding-top: 20px;" class="bd-placeholder-img rectangle" width="500" height="400" src="{{asset('/img/initiative.jpg')}}" alt="PDT">
                    </div>
                </div>

                <hr class="featurette-divider">


                <div class="row featurette" style="background-color: white">
                    <div class="col-md-7 order-md-2">
                        <h1 class="featurette-heading fw-normal lh-1 text-center" style="padding-top: 50px;">As fontes de informação </h1>
                        <br>
                        <p class="lead text-center">
                            Serão tidos em conta vários recursos de dados, entre outros fatores, tais como os requisitos de interoperabilidade IFC, Regulamentos de Produtos de Construção (CPR), Declarações Ambientais de Produtos (EPD) e outras iniciativas de normalização. Estes esforços envolverão a consulta direta a vários intervenientes na cadeia de valor para assegurar que os PDT criados reúnam consenso generalizado na indústria AEC.
                        </p>

                    </div>
                    <div class="col-md-5 order-md-1">
                        <img class="bd-placeholder-img rectangle" width="500" height="400" src="{{asset('/img/pdtsource.png')}}" alt="PDT">
                    </div>
                </div>
                <div class="home_content container" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div class="card" style="width: 18rem;">
                        <img class="card-img-top" src="{{asset('/img/IFC.png')}}" alt="Card image cap">
                        <div class="card-body text-center">
                            <h5 class="card-title"><strong>IFC</strong></h5>
                            <button class="btn btn-secondary" style="background-color: black;" href="https://buildingsmart.pt/">Ver mais</button>
                        </div>
                    </div>
                    <div class="card" style="width: 18rem;">
                        <img style="padding-top: 80px;" class="card-img-top" src="{{asset('/img/EPD.png')}}" alt="Card image cap">
                        <div class="card-body text-center" style="padding-top: 107px;">
                            <h5 class="card-title"><strong>EPD</strong></h5>
                            <button class="btn btn-secondary" style="background-color: black;" href="https://www.environdec.com/home">Ver mais</button>
                        </div>
                    </div>
                    <div class="card" style="width: 18rem;">
                        <img class="card-img-top" src="{{asset('/img/CPR.png')}}" alt="Card image cap">
                        <div class="card-body text-center" style="padding-top: 53px;">
                            <h5 class="card-title"><strong>CPR</strong></h5>
                            <button class="btn btn-secondary" style="background-color: black;" href="https://single-market-economy.ec.europa.eu/sectors/construction/construction-products-regulation-cpr_en">Ver mais</button>
                        </div>
                    </div>
                </div>
                <hr class="featurette-divider">

                <div class="row featurette">
                    <div class="col-md-7">
                        <h1 class="featurette-heading fw-normal lh-1 text-center">Como pode ajudar? </h1>
                        <h2 class="text-muted text-center"> junte-se a nós</h2>
                        <br>
                        <p class="lead text-center">
                            Cada actor da indústria da construção tem uma perspectiva sobre o tipo de dados que devem estar presentes num produto/sistema de construção. Assim, cada pessoa envolvida na indústria da construção pode contribuir para a especificação das propriedades dos produtos de construção.
                            Esta iniciativa dá-lhe a oportunidade de ser ouvido/a, e de a sua opinião ser tida em conta. Uma vez registado/a nesta plataforma, terá acesso a uma variedade de Modelos de Dados de Produtos de diferentes produtos de construção. Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.
                            Tudo o que tem de fazer é responder ao questionário e partilhar o seu feedback para contribuir na criação de Modelos de Dados de Produtos para produtos de construção na indústria portuguesa.
                        </p>
                    </div>


                    <div class="col-md-5" style="padding-top: 150px; position: relative;">
                        <div style="position: absolute; top: 20%; left: 50%; transform: translate(-50%, -50%);">
                            <button class="btn btn-secondary" style="background-color: black;" href="{{route('dashboard')}}">Junte-se a nós &raquo;</button>
                        </div>
                        <img class="bd-placeholder-img rectangle" width="500" height="300" src="{{asset('/img/surveyphoto.jpg')}}" alt="Survey">
                    </div>
                </div>

                <hr class="featurette-divider">

                <!-- /END THE FEATURETTES -->

                <!-- /.container -->


                <!-- FOOTER -->
                <footer class="container">
                    <p class="float-end"><a href="#">Back to top</a></p>
                    <p>&copy; 2021 UMinho. All rights reserved &middot; <a href="{{route('privacypolicy')}}"> Política de privacidade</a></p>
                </footer>

            </div>
        </div>
    </div>



</x-app-layout>