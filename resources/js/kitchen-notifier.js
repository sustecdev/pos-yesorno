const SOUND_PROFILES = {
    new: { frequency: 880, duration: 0.15, repeat: 2 },
    fire: { frequency: 660, duration: 0.1, repeat: 3 },
    modify: { frequency: 440, duration: 0.2, repeat: 2 },
    cancel: { frequency: 220, duration: 0.3, repeat: 1 },
    rush: { frequency: 990, duration: 0.12, repeat: 4 },
};

let audioContext = null;
let repeatInterval = null;
let pendingAlerts = [];

function getAudioContext() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioContext;
}

function playTone(profile) {
    const ctx = getAudioContext();
    const config = SOUND_PROFILES[profile] || SOUND_PROFILES.new;

    for (let i = 0; i < config.repeat; i++) {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = config.frequency;
        osc.type = 'sine';
        const start = ctx.currentTime + i * (config.duration + 0.08);
        gain.gain.setValueAtTime(0.3, start);
        gain.gain.exponentialRampToValueAtTime(0.01, start + config.duration);
        osc.start(start);
        osc.stop(start + config.duration);
    }
}

function showBrowserNotification(title, body) {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') {
        new Notification(title, { body, icon: '/favicon.ico' });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}

function triggerHaptic() {
    if ('vibrate' in navigator) {
        navigator.vibrate([200, 100, 200]);
    }
}

function flashScreenEdge(color = 'amber') {
    const el = document.getElementById('kds-screen');
    if (!el) return;
    el.classList.add('screen-edge-flash');
    if (color === 'red') el.style.boxShadow = 'inset 0 0 0 4px rgba(232,93,76,0.8)';
    setTimeout(() => {
        el.classList.remove('screen-edge-flash');
        el.style.boxShadow = '';
    }, 600);
}

function startRepeatLoop(profile) {
    stopRepeatLoop();
    playTone(profile);
    repeatInterval = setInterval(() => playTone(profile), 4000);
}

function stopRepeatLoop() {
    if (repeatInterval) {
        clearInterval(repeatInterval);
        repeatInterval = null;
    }
}

window.TeboKitchenNotifier = {
    handleAlert(data) {
        const { type, sound, payload } = data;
        const profile = sound || 'new';

        pendingAlerts.push(data);

        playTone(profile);
        flashScreenEdge(type === 'rush_order' || type === 'allergy_alert' ? 'red' : 'amber');

        if (type === 'rush_order' || type === 'allergy_alert') {
            triggerHaptic();
            startRepeatLoop(profile);
        }

        if (document.hidden) {
            const table = payload?.table || 'Kitchen';
            const restaurantName = document.querySelector('meta[name="restaurant-name"]')?.content || 'Restaurant';
            showBrowserNotification(`${restaurantName} — ${type.replace(/_/g, ' ')}`, `Table ${table}`);
        }

        window.dispatchEvent(new CustomEvent('tebo-kitchen-alert', { detail: data }));
    },

    acknowledge() {
        stopRepeatLoop();
        pendingAlerts = [];
    },

    getPendingCount() {
        return pendingAlerts.length;
    },
};

document.addEventListener('livewire:init', () => {
    Livewire.on('kitchen-alert-received', () => {});

    Livewire.on('kitchen-alert-acked', () => {
        window.TeboKitchenNotifier?.acknowledge();
    });
});

if (window.Echo) {
    document.addEventListener('DOMContentLoaded', () => {
        const stationEl = document.querySelector('[data-station-id]');
        const stationId = stationEl?.dataset.stationId;
        if (stationId) {
            window.Echo.channel(`kitchen.station.${stationId}`)
                .listen('.KitchenAlertReceived', (data) => {
                    window.TeboKitchenNotifier?.handleAlert(data);
                });
        }

        window.Echo.channel('kitchen.expo')
            .listen('.KitchenAlertReceived', (data) => {
                window.TeboKitchenNotifier?.handleAlert(data);
            });
    });
}
