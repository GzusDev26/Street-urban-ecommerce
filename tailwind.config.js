import defaultTheme from 'tailwindcss/defaultTheme';
import twColors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import plugin from 'tailwindcss/plugin';

/*
 * Modo claro / oscuro
 * -------------------
 * El admin está diseñado en claro y la tienda en oscuro. En vez de duplicar cada clase con `dark:`,
 * los grises y los colores de estado son variables CSS: cuando el modo activo es el contrario al del
 * diseño (admin en oscuro, tienda en claro), la escala se invierte y `text-gray-900`, `bg-red-50`,
 * `border-gray-800`, etc. siguen teniendo sentido sin tocar las vistas.
 *
 *  - <html class="dark">            → modo oscuro activo (lo pone resources/js/theme.js).
 *  - <html data-surface="shop">     → la página es de la tienda (diseño oscuro); si no, diseño claro.
 *  - .theme-fixed                   → zona que nunca cambia (boleta térmica, impresión).
 *  - .theme-shop-dark               → zona de la tienda que siempre va en oscuro (sobre fotos).
 *
 * Los blancos/negros de fondo y texto usan tokens con nombre (`canvas` fondo de página, `surface`
 * tarjetas, `sunken` zonas hundidas como el footer, `fg` texto principal), que
 * siempre describen la apariencia real; `text-white` sobre un botón naranja sigue siendo blanco.
 */

const SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];
const HUES = ['red', 'orange', 'amber', 'yellow', 'green', 'emerald', 'blue', 'indigo', 'violet', 'purple', 'pink'];

const toRgb = (hex) => {
    const n = parseInt(hex.slice(1), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
};
const mix = (hex, base, amount) => {
    const a = toRgb(hex);
    const b = toRgb(base);
    return a.map((c, i) => Math.round(c * amount + b[i] * (1 - amount)));
};
const channels = (value) => (Array.isArray(value) ? value : toRgb(value)).join(' ');

const DARK_SURFACE = '#17171a';

// Grises del admin en oscuro: escala invertida y afinada para que el texto tenga buen contraste.
const GRAY_ADMIN_DARK = {
    50: '#1e1e22', 100: '#26262b', 200: '#303036', 300: '#45454c', 400: '#83838d',
    500: '#a1a1aa', 600: '#b9b9c1', 700: '#cfcfd6', 800: '#e2e2e7', 900: '#f2f2f5', 950: '#fafafa',
};

// Grises de la tienda en claro: escala invertida sobre fondo claro.
const GRAY_SHOP_LIGHT = {
    50: '#0f0f10', 100: '#18181b', 200: '#27272a', 300: '#3f3f46', 400: '#52525b',
    500: '#71717a', 600: '#a1a1aa', 700: '#d4d4d8', 800: '#e4e4e7', 900: '#f1f1f2', 950: '#fafafa',
};

// Diseño claro visto en oscuro: los tonos suaves pasan a ser tintes oscuros y los fuertes se aclaran.
const hueAdminDark = (c) => ({
    50: mix(c[500], DARK_SURFACE, 0.12),
    100: mix(c[500], DARK_SURFACE, 0.18),
    200: mix(c[500], DARK_SURFACE, 0.3),
    300: mix(c[400], DARK_SURFACE, 0.55),
    400: c[400], 500: c[500], 600: c[400], 700: c[300], 800: c[200], 900: c[100], 950: c[50],
});

// Diseño oscuro visto en claro: los tonos claros (texto sobre negro) se oscurecen y los fondos
// oscuros pasan a ser tintes claros. 500-700 se mantienen porque son botones sólidos.
const hueShopLight = (c) => ({
    50: c[950], 100: c[900], 200: c[800], 300: c[700], 400: c[600],
    500: c[500], 600: c[600], 700: c[700], 800: c[200], 900: c[100], 950: c[50],
});

const paletteVars = (gray, hue) => {
    const vars = {};
    for (const s of SHADES) vars[`--gray-${s}`] = channels(gray[s]);
    for (const h of HUES) {
        const scale = hue(twColors[h]);
        for (const s of SHADES) vars[`--${h}-${s}`] = channels(scale[s]);
    }
    return vars;
};

const PALETTE_AUTHORED = paletteVars(twColors.gray, (c) => c);
const PALETTE_ADMIN_DARK = paletteVars(GRAY_ADMIN_DARK, hueAdminDark);
const PALETTE_SHOP_LIGHT = paletteVars(GRAY_SHOP_LIGHT, hueShopLight);

// Tokens semánticos (apariencia real, no la del diseño).
const TOKENS_LIGHT = {
    '--canvas': channels('#f4f5f7'),
    '--surface': channels('#ffffff'),
    '--surface-2': channels('#f9fafb'),
    '--sunken': channels('#eceef1'),
    '--fg': channels('#111111'),
};
const TOKENS_DARK = {
    '--canvas': channels('#0e0e10'),
    '--surface': channels(DARK_SURFACE),
    '--surface-2': channels('#1e1e22'),
    '--sunken': channels('#0a0a0b'),
    '--fg': channels('#ffffff'),
};
const TOKENS_SHOP_LIGHT = {
    '--canvas': channels('#f5f5f3'),
    '--surface': channels('#ffffff'),
    '--surface-2': channels('#efefed'),
    '--sunken': channels('#ebebe8'),
    '--fg': channels('#111111'),
};
const TOKENS_SHOP_DARK = {
    '--canvas': channels('#111111'),
    '--surface': channels('#1a1a1a'),
    '--surface-2': channels('#1f1f1f'),
    '--sunken': channels('#0a0a0a'),
    '--fg': channels('#ffffff'),
};

const varColor = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const varScale = (hue) => Object.fromEntries(SHADES.map((s) => [s, varColor(`${hue}-${s}`)]));

const themePalette = plugin(({ addBase }) => {
    addBase({
        ':root': { ...PALETTE_AUTHORED, ...TOKENS_LIGHT, colorScheme: 'light' },
        'html.dark': { ...TOKENS_DARK, colorScheme: 'dark' },
        'html.dark:not([data-surface="shop"])': PALETTE_ADMIN_DARK,
        'html[data-surface="shop"]': TOKENS_SHOP_DARK,
        'html[data-surface="shop"]:not(.dark)': { ...PALETTE_SHOP_LIGHT, ...TOKENS_SHOP_LIGHT, colorScheme: 'light' },
        '.theme-fixed': { ...PALETTE_AUTHORED, ...TOKENS_LIGHT, colorScheme: 'light' },
        // Piezas de la tienda que van sobre una foto oscura (navbar sobre el hero): siempre en oscuro.
        '.theme-shop-dark': { ...PALETTE_AUTHORED, ...TOKENS_SHOP_DARK, colorScheme: 'dark' },
        '@media print': {
            // Mismos selectores que arriba para ganarles por orden: lo impreso siempre sale en claro.
            'html.dark, html.dark:not([data-surface="shop"])': { ...PALETTE_AUTHORED, ...TOKENS_LIGHT },
        },
    });
});

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: varScale('gray'),
                ...Object.fromEntries(HUES.map((h) => [h, varScale(h)])),
                canvas: varColor('canvas'),
                surface: { DEFAULT: varColor('surface'), 2: varColor('surface-2') },
                sunken: varColor('sunken'),
                fg: varColor('fg'),
                'street-orange': {
                    50: '#fff7f0',
                    100: '#ffedde',
                    200: '#ffdac2',
                    300: '#ffc2a2',
                    400: '#ff8c42',
                    500: '#ff7a1e',
                    600: '#e67e2d',
                    700: '#cc6b1a',
                    800: '#b35a12',
                    900: '#8a4410',
                },
            },
        },
    },

    plugins: [
        forms,
        typography,
        themePalette,
    ],
};
