import { readonly, ref } from 'vue';

// Modo claro / oscuro. El primer pintado lo resuelve el script en línea de app.blade.php (sin
// parpadeo); aquí solo se mantiene sincronizado. Sin preferencia guardada se sigue al sistema.
const STORAGE_KEY = 'theme';
const root = document.documentElement;
const media = window.matchMedia('(prefers-color-scheme: dark)');

const readStored = () => {
    try { return localStorage.getItem(STORAGE_KEY); } catch { return null; }
};

const isDark = ref(root.classList.contains('dark'));

const apply = (dark) => {
    isDark.value = dark;
    root.classList.toggle('dark', dark);
};

media.addEventListener('change', (e) => {
    if (!readStored()) apply(e.matches);
});

export function toggleTheme() {
    apply(!isDark.value);
    try { localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light'); } catch { /* sin almacenamiento */ }
}

export function useTheme() {
    return { isDark: readonly(isDark), toggleTheme };
}

// La tienda está diseñada en oscuro y el resto en claro (ver tailwind.config.js).
// Misma regla que en app.blade.php, que la aplica en la carga inicial.
export function surfaceFor(component) {
    return component.startsWith('Shop/') || component === 'Auth/ActivateAccount' ? 'shop' : 'app';
}

export function syncSurface(component) {
    root.dataset.surface = surfaceFor(component);
}
