import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configuración de Reverb con Echo
window.Echo = new Echo({
    broadcaster: 'reverb',
    host: window.location.hostname + ':6001'
});

// Iniciar la aplicación con Inertia
createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        if (!pages[`./Pages/${name}.vue`]) {
            console.error(`Componente ${name} no encontrado.`);
        }
        return (await pages[`./Pages/${name}.vue`]()).default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
