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
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('./node_modules/tw-elements/dist/plugin'),
        require('tailwindcss/colors'),
    ],
};
