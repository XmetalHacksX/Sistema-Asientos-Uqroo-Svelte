import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const host = import.meta.env.VITE_REVERB_HOST === 'localhost' 
    ? window.location.hostname 
    : (import.meta.env.VITE_REVERB_HOST || window.location.hostname);

const isHttps = window.location.protocol === 'https:';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? (isHttps ? 443 : 8080),
    wssPort: import.meta.env.VITE_REVERB_PORT ?? (isHttps ? 443 : 8080),
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
});

