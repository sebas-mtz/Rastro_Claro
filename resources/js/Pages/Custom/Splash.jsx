import { useEffect } from 'react';
import { router } from '@inertiajs/react';

/**
 * Splash page component
 *
 * A simple full‑screen view that displays the company logo on a dark
 * background. After a short delay the page automatically navigates to
 * the custom login form using Inertia's router.
 */
export default function Splash() {
    useEffect(() => {
        // Redirect to the custom login after 2.5 seconds
        const timer = setTimeout(() => {
            router.visit('/login-custom');
        }, 2500);
        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="splash-screen">
            <img src="/assets/logo.png" alt="Itzcóatl Tech logo" className="splash-logo" />
        </div>
    );
}