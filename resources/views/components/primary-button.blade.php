<button {{ $attributes->merge(['data-te-ripple-init','data-te-ripple-color' =>'light','type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-slate-700 dark:bg-slate-200 rounded-md font-semibold text-xs text-white dark:text-gray-900 uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-white focus:bg-slate-900 dark:focus:bg-white active:bg-slate-900 dark:active:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
