import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// El token CSRF cambia al cerrar sesión (se invalida la sesión), pero con las navegaciones de
// Inertia la página no se recarga y la <meta> se quedaría con el viejo: el siguiente formulario
// (ej. el login del modal) fallaba con 419 y había que intentarlo dos veces.
router.on('navigate', (event) => {
    const token = event.detail.page.props.csrfToken;
    const meta = document.querySelector('meta[name="csrf-token"]');

    if (token && meta) {
        meta.content = token;
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
