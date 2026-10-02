import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Hardcoded rather than VITE_* env vars: those bake at build time, but the app's
// runtime config is set by the container entrypoint, so the two would drift. The
// demo key is fixed (docker/entrypoint.sh REVERB_APP_KEY=reverbchatkey), and the
// browser reaches Reverb same-origin: nginx/apache proxy ws://host/app/... to it.
function buildEcho(extra = {}) {
    return new Echo({
        broadcaster: 'reverb',
        key: 'reverbchatkey',
        wsHost: window.location.hostname,
        wsPort: window.location.port ? Number(window.location.port) : 80,
        wssPort: window.location.port ? Number(window.location.port) : 443,
        forceTLS: window.location.protocol === 'https:',
        enabledTransports: ['ws', 'wss'],
        ...extra,
    });
}

const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';

// The default connection, authorized by the session (the normal app).
window.Echo = buildEcho();

// A connection that authorizes channels as a signed demo user instead of the
// session. Used by the side-by-side view so two panes can be two people at once.
window.makeDemoEcho = (token) => buildEcho({
    authEndpoint: '/broadcasting/auth-demo',
    auth: { headers: { 'X-Demo-Token': token, 'X-CSRF-TOKEN': csrf } },
});
