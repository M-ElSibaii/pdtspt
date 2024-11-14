const defaultTheme = require('./node_modules/tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.js',
        './resources/views/**/*.blade.php',
        './resources/**/*.blade.php',
        './node_modules/tw-elements/dist/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Open Sans', ...defaultTheme.fontFamily.sans],
            },
        },
        screens: {
            'sm': { 'max': '767px' },
            // => @media (max-width: 767px) { ... }

            'md': { 'min': '768px', 'max': '1023px' },
            // => @media (min-width: 768px and max-width: 1023px) { ... }

            'lg': { 'min': '1024px', 'max': '4000px' },
            // => @media (min-width: 1024px) { ... }
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('./node_modules/tw-elements/dist/plugin'),
        require('tailwindcss/colors'),
    ],
};
