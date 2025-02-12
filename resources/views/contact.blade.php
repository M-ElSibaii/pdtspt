<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9 flex ls:flex-row md:flex-row sm:flex-col gap-6">
            <div class="basis-1/2 text-current">
                <h2>Contactos</h2>
                <p>Para entrar em contacto com a equipa de investigação contact-nos no nosso email:</p>
                <a href="mailto: pdts.portugal@gmail.com">pdts.portugal@gmail.com</a>
                <img class="max-h-[500px] w-auto" src="{{asset('/img/contact.jpg')}}" alt="contact">
            </div>
            <div class="basis-1/2 flex flex-col">
                @if (session('success'))
                <div class="alert flex flex-row items-center bg-green-200 p-4 rounded border-b-2 border-green-300 py-4 mb-1">
                    <div class="alert-icon flex items-center bg-green-100 border-2 border-green-500 justify-center h-10 w-10 flex-shrink-0 rounded-full">
                        <span class="text-green-500">
                            <svg fill="currentColor" viewBox="0 0 20 20" class="h-6 w-6">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="alert-content ml-4">
                        <div class="alert-title font-semibold text-lg text-green-800">
                            {{ __('success') }}
                        </div>
                        <div class="alert-description text-sm text-green-600">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
                @endif


            </div>
        </div>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</x-app-layout>