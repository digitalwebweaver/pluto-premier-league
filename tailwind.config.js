import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            // Nav switches bottom-tab → sidebar at 680px (design.md §8).
            screens: {
                nav: '680px',
            },
            // ---- Palette (design.md §2.1) — meaning-bearing, never decorative ----
            colors: {
                ink: {
                    DEFAULT: '#12213D', // primary dark
                    2: '#1B2F52', // secondary dark / gradients
                },
                paper: {
                    DEFAULT: '#F6F4EF', // primary warm background
                    2: '#EDE9DF', // secondary surface
                },
                gold: {
                    DEFAULT: '#D9A441', // 1st place, CTAs, approved highlight
                    ink: '#9A6F1E', // accessible gold text on light
                },
                silver: '#B8C0CC', // 2nd place, neutral secondary
                bronze: '#B5473A', // 3rd place, error/destructive, absent/rejected
                turf: '#3F8F6B', // success / approved / present
                slate: '#5A6684', // secondary text, captions, muted borders
                line: '#DAD5C6', // hairline borders, dividers
            },
            // ---- Typography (design.md §2.2) ----
            fontFamily: {
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            // ---- Shape (design.md §2.3) ----
            borderRadius: {
                input: '8px',
                card: '14px',
                lg: '16px',
            },
            // ---- Elevation (design.md §2.3) — the ONLY two allowed shadows ----
            boxShadow: {
                row: '0 6px 20px rgba(18, 33, 61, 0.14)',
                modal: '0 30px 80px rgba(0, 0, 0, 0.4)',
            },
            // ---- Min touch target (design.md §8) ----
            minHeight: {
                tap: '44px',
            },
            minWidth: {
                tap: '44px',
            },
            keyframes: {
                totalPulse: {
                    '0%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.04)' },
                    '100%': { transform: 'scale(1)' },
                },
                fadeUp: {
                    from: { opacity: '0', transform: 'translateY(6px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'total-pulse': 'totalPulse 200ms ease-out',
                'fade-up': 'fadeUp 200ms ease',
            },
        },
    },
    plugins: [],
};
