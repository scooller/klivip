import '@web.awesome.me/webawesome-pro/dist/styles/webawesome.css';
import '../scss/app.scss';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const webAwesomeKitCode = import.meta.env.VITE_WEBAWESOME_KIT_CODE;

if (webAwesomeKitCode) {
    document.documentElement.setAttribute('data-fa-kit-code', webAwesomeKitCode);
}

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx')
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#e0b33b',
    },
});
