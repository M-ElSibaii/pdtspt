@props(['link' => '#', 'title','type' => 'button'])

<a href="{{ $link }}" >
    <button
        type="{{ $type }}"
        data-te-ripple-init
        data-te-ripple-color="light"
        class="inline-flex items-center px-4 py-2 bg-slate-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-900 focus:bg-slate-900 active:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        {{ $title }}
    </button>
</a>