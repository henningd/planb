import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Echo nur initialisieren, wenn ein Reverb-Key konfiguriert ist —
// sonst wirft Pusher und das blockiert alle weiteren Module-Imports
// (z. B. den Cytoscape-basierten Mitarbeiter-Hierarchie-Graph).
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
}