if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const serviceWorkerMeta = document.querySelector('meta[name="service-worker-url"]');
        const serviceWorkerUrl = serviceWorkerMeta?.getAttribute('content') || '/service-worker.js';

        navigator.serviceWorker.register(serviceWorkerUrl).catch(() => {
            // PWA registration is optional and must never block the CRM.
        });
    });
}
