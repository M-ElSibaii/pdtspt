<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Participantes ') }}
        </h2>
    </x-slot>

    <div class="py-9">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4  mx-auto bg-white dark:bg-gray-800 shadow sm:rounded-lg">

                <!-- Client 1 - HCF Bootstrap 5 Component -->

                <section class="bg-light py-5 py-xl-6">
                    <div class="container mb-5 mb-md-6">
                        <div class="row justify-content-md-center">
                            <div class="col-12 col-md-10 col-lg-8 col-xl-7 col-xxl-6 text-center">
                                <h2 class="mb-4 display-5" style="color: black;">Instituições envolvidas</h2>
                                <hr class="w-50 mx-auto mb-0 text-secondary">
                            </div>
                        </div>
                    </div>
                    <div class="container overflow-hidden">
                        <div class="row gy-5 gy-md-6">
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="https://secclass.pt/" target="_blank" rel="noopener noreferrer"><img src="img/SECClasS-Logo-Website.png" alt="" style="height: 90px; "></a>
                            </div>
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="https://revconstruction.pt/" target="_blank" rel="noopener noreferrer"><img src="img/rev-logo-main-horizontal-dark.svg" alt="" style="height: 80px; "></a>
                            </div>
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="http://www.ct197.pt/" target="_blank" rel="noopener noreferrer"><img src="img/ct197-logo.png" alt="" style="height: 100px; "></a>
                            </div>
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="https://www.uminho.pt/PT" target="_blank" rel="noopener noreferrer"><img src="img/Uminho.svg" alt="" style="height: 100px; "></a>
                            </div>
                        </div>
                </section>
                <section class="bg-light py-5 py-xl-6">
                    <div class="container mb-5 mb-md-6">
                        <div class="row justify-content-md-center">
                            <div class="col-12 col-md-10 col-lg-8 col-xl-7 col-xxl-6 text-center">
                                <h2 class="mb-4 display-5" style="color: black;">Financiamento</h2>
                                <hr class="w-50 mx-auto mb-0 text-secondary">
                            </div>
                        </div>
                    </div>
                    <div class="container overflow-hidden">
                        <div class="row gy-5 gy-md-6">
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="https://www.mitportugal.org/" target="_blank" rel="noopener noreferrer"><img src="img/mitportugal.png" alt="" style="height: 45px; "></a>
                            </div>
                            <div class="col-6 col-md-3 align-self-center text-center">
                                <a href="https://www.fct.pt/en/" target="_blank" rel="noopener noreferrer"><img src="img/fct.svg" alt="" style="height: 60px; "></a>
                            </div>
                        </div>
                </section>
            </div>
        </div>

    </div>


    <div class="ml-2 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
        © 2021 UMinho. All rights reserved. <a href="{{route('privacypolicy')}}"> Política de privacidade</a>
        <p></p>
    </div>


</x-app-layout>