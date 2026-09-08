import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Be Vietnam Pro', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'fsel': {
                    'dark': '#0b1120',
                    'darker': '#0f172a',
                    'navy': '#1e293b',
                    'card': '#1a2332',
                    'input': '#1e2a3a',
                    'border': '#2a3a4a',
                    'teal': '#2dd4bf',
                    'teal-dark': '#14b8a6',
                    'purple': '#7c3aed',
                    'purple-light': '#8b5cf6',
                    'blue': '#3b82f6',
                    'blue-dark': '#1d4ed8',
                    'sidebar': '#1a2236',
                    'sidebar-active': '#3b5998',
                    'sidebar-hover': '#2a3a5a',
                    'accent': '#4f87f6',
                    'gold': '#fbbf24',
                },
            },
            backgroundImage: {
                'gradient-purple': 'linear-gradient(135deg, #1a1a4e 0%, #2d1b69 30%, #1a2f6a 60%, #0f172a 100%)',
                'gradient-space': 'linear-gradient(180deg, #1a1a4e 0%, #2d1b69 40%, #3d2d7a 70%, #4a3a8a 100%)',
                'gradient-btn': 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                'gradient-cta': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            },
            boxShadow: {
                'glow-teal': '0 0 20px rgba(45, 212, 191, 0.3)',
                'glow-blue': '0 0 20px rgba(59, 130, 246, 0.3)',
                'glow-purple': '0 0 20px rgba(124, 58, 237, 0.3)',
                'card': '0 4px 24px rgba(0, 0, 0, 0.3)',
            },
            animation: {
                'float': 'float 6s ease-in-out infinite',
                'twinkle': 'twinkle 3s ease-in-out infinite',
                'rocket': 'rocket 4s ease-in-out infinite',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-20px)' },
                },
                twinkle: {
                    '0%, 100%': { opacity: '0.3' },
                    '50%': { opacity: '1' },
                },
                rocket: {
                    '0%, 100%': { transform: 'translateY(0) rotate(-45deg)' },
                    '50%': { transform: 'translateY(-15px) rotate(-45deg)' },
                },
            },
        },
    },

    plugins: [forms],
};
