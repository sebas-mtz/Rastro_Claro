import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { PreferencesProvider } from './Contexts/PreferencesContext';

createInertiaApp({
  title: (title) => (title ? `${title} | FarmManager Pro` : 'FarmManager Pro'),
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
  setup({ el, App, props }) {
    const initialPreferences = props.initialPage?.props?.auth?.user?.settings ?? {};

    createRoot(el).render(
      <PreferencesProvider initialPreferences={initialPreferences}>
        <App {...props} />
      </PreferencesProvider>,
    );
  },
});
