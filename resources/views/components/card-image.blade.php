@props(['card_image', 'card_title', 'card_description', 'card_link', 'card_link_title'])
<div class="h-full">
    <div class="flex flex-col h-full">
        <div class="block max-w-sm rounded-lg bg-white dark:bg-neutral-700 h-full">
            <div class="p-3 flex flex-col justify-between">
                <a class="content-center" href="{{ $card_link }}" data-te-ripple-init data-te-ripple-color="light">
                    <div class="h-10">
                        <img class="w-full h-full" src="{{ $card_image }}" alt="" />
                    </div>
                </a>
                <div>
                    <h5 class="mb-2 text-xl font-medium leading-tight text-neutral-800 dark:text-neutral-50">
                        {{ $card_title }}
                    </h5>
                    <p class="mb-4 text-base text-neutral-600 dark:text-neutral-200">
                        {{ $card_description }}
                    </p>
                    <a href="{{ $card_link }}">
                        <x-secondary-button>
                            {{ $card_link_title }}
                        </x-secondary-button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>