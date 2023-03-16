<div
    id="carouselExampleCaptions"
    class="relative"
    style="background-color: rgb(51 65 85);"
    data-te-carousel-init
    data-te-carousel-slide>
    <div
        class="absolute right-0 bottom-0 left-0 z-[2] mx-[15%] mb-4 flex list-none justify-center p-0"
        data-te-carousel-indicators>
        <button
        type="button"
        data-te-target="#carouselExampleCaptions"
        data-te-slide-to="0"
        data-te-carousel-active
        class="mx-[3px] box-content h-[3px] w-[30px] flex-initial cursor-pointer border-0 border-y-[10px] border-solid border-transparent bg-white bg-clip-padding p-0 -indent-[999px] opacity-50 transition-opacity duration-[600ms] ease-[cubic-bezier(0.25,0.1,0.25,1.0)] motion-reduce:transition-none"
        aria-current="true"
        aria-label="Slide 1"></button>
        <button
        type="button"
        data-te-target="#carouselExampleCaptions"
        data-te-slide-to="1"
        class="mx-[3px] box-content h-[3px] w-[30px] flex-initial cursor-pointer border-0 border-y-[10px] border-solid border-transparent bg-white bg-clip-padding p-0 -indent-[999px] opacity-50 transition-opacity duration-[600ms] ease-[cubic-bezier(0.25,0.1,0.25,1.0)] motion-reduce:transition-none"
        aria-label="Slide 2"></button>
        <button
        type="button"
        data-te-target="#carouselExampleCaptions"
        data-te-slide-to="2"
        class="mx-[3px] box-content h-[3px] w-[30px] flex-initial cursor-pointer border-0 border-y-[10px] border-solid border-transparent bg-white bg-clip-padding p-0 -indent-[999px] opacity-50 transition-opacity duration-[600ms] ease-[cubic-bezier(0.25,0.1,0.25,1.0)] motion-reduce:transition-none"
        aria-label="Slide 3"></button>
    </div>
    <div
        class="relative w-full overflow-hidden after:clear-both after:block after:content-['']">
        <div
            class="relative float-left -mr-[100%] w-full transition-transform duration-[600ms] ease-in-out motion-reduce:transition-none"
            data-te-carousel-active
            data-te-carousel-item
            style="backface-visibility: hidden;">
            <div 
                class="h-[500px] w-full opacity-50 block bg-cover bg-center bg-no-repeat" 
                style="background-image: url({{asset('/img/standard.png')}});"></div>
            <div
                class="absolute inset-x-[15%] bottom-5 hidden py-5 text-center text-white md:block">
                <h1 class="text-white">
                    A normalização dos dados na indústria da contrução está a caminho
                </h1>
                <h3 class="my-3">
                    Aqui tem acesso a modelos de dados de produtos normalizados abertos
                </h3>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="Ver PDTs"/>
            </div>
        </div>
        <div
            class="relative float-left -mr-[100%] hidden w-full transition-transform duration-[600ms] ease-in-out motion-reduce:transition-none"
            data-te-carousel-item
            style="backface-visibility: hidden">
            <div 
                class="h-[500px] w-full opacity-50 block bg-cover bg-center bg-no-repeat" 
                style="background-image: url({{asset('/img/apibackground.png')}});"></div>
            <div
                class="absolute inset-x-[15%] bottom-5 hidden py-5 text-center text-white md:block">
                <h1 class="text-white">
                    As API's são a chave para ligar a indústria
                </h1>
                <h3 class="my-3">
                    Obtenha acesso a todos os Modelos de Dados de Produtos através do nosso API
                </h3>
                <x-button-primary-pdts 
                    link="{{route('apidoc')}}"
                    title="Documentação"/>
            </div>
        </div>
        <div
            class="relative float-left -mr-[100%] hidden w-full transition-transform duration-[600ms] ease-in-out motion-reduce:transition-none"
            data-te-carousel-item
            style="backface-visibility: hidden">
            <div 
                class="h-[500px] w-full opacity-50 block bg-cover bg-center bg-no-repeat" 
                style="background-image: url({{asset('/img/pdtpage.png')}});"></div>
            <div
                class="absolute inset-x-[15%] bottom-5 hidden py-5 text-center text-white md:block">
                <h1 class="text-white">
                    Faz parte da digitalização em construção em Portugal
                </h1>
                <h3 class="my-3">
                    Ver, rever, adicionar feedback e descarregar modelos de dados do produto
                </h3>
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    title="Participar"/>
            </div>
        </div>
    </div>
    <button
        class="absolute top-0 bottom-0 left-0 z-[1] flex w-[15%] items-center justify-center border-0 bg-none p-0 text-center text-white opacity-50 transition-opacity duration-150 ease-[cubic-bezier(0.25,0.1,0.25,1.0)] hover:text-white hover:no-underline hover:opacity-90 hover:outline-none focus:text-white focus:no-underline focus:opacity-90 focus:outline-none motion-reduce:transition-none"
        type="button"
        data-te-target="#carouselExampleCaptions"
        data-te-slide="prev">
        <span class="inline-block h-8 w-8">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </span>
        <span
            class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">
            Previous
        </span>
    </button>
    <button
        class="absolute top-0 bottom-0 right-0 z-[1] flex w-[15%] items-center justify-center border-0 bg-none p-0 text-center text-white opacity-50 transition-opacity duration-150 ease-[cubic-bezier(0.25,0.1,0.25,1.0)] hover:text-white hover:no-underline hover:opacity-90 hover:outline-none focus:text-white focus:no-underline focus:opacity-90 focus:outline-none motion-reduce:transition-none"
        type="button"
        data-te-target="#carouselExampleCaptions"
        data-te-slide="next">
        <span class="inline-block h-8 w-8">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </span>
        <span
            class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">
            Next
        </span>
    </button>
</div>
