import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // ← Added this – very important for Flowbite components to be recognized
        './node_modules/flowbite/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        forms,
        // ← Add this line to enable Flowbite's additional utilities & components
        require('flowbite/plugin'),
    ],
    // Keep your existing safelist – it's fine
    safelist: [
        // Button backgrounds and hovers for roles edit page
        'bg-green-600',
        'hover:bg-green-700',
        'focus:ring-green-500',
        'bg-green-700',
        'bg-gray-600',
        'hover:bg-gray-700',
        'focus:ring-gray-500',
        'bg-gray-800',
        'hover:bg-gray-900',
        'focus:ring-gray-300',
        // Badge for permissions list
        'bg-blue-100',
        'text-blue-800',
        // Extra safety for common colors we'll use in CRM
        'bg-indigo-600',
        'hover:bg-indigo-700',
        'bg-red-600',
        'hover:bg-red-700',
    ],
};
