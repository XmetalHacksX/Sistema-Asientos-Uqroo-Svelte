<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import { fade } from 'svelte/transition';

    interface ScanResult {
        status: 'success' | 'warning' | 'error';
        title: string;
        message: string;
        seat?: string;
        name?: string;
    }

    // Props using Svelte 5 runes
    let {
        onScan
    }: {
        onScan: (decodedText: string) => Promise<ScanResult>;
    } = $props();

    let html5QrCode = $state<any>(null);
    let cameraDevices = $state<any[]>([]);
    let selectedCameraId = $state<string>('');
    let isScanning = $state<boolean>(false);
    let scanResult = $state<ScanResult | null>(null);
    let isMounted = $state<boolean>(false);

    onMount(async () => {
        isMounted = true;
        if (typeof window === 'undefined') return;

        // Cargar script de html5-qrcode dinámicamente si no existe
        if (!(window as any).Html5Qrcode) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // Obtener cámaras disponibles
        try {
            const devices = await (window as any).Html5Qrcode.getCameras();
            cameraDevices = devices || [];
            if (cameraDevices.length > 0) {
                // Auto-seleccionar cámara trasera si existe, de lo contrario la primera
                const backCamera = cameraDevices.find((d: any) => 
                    d.label.toLowerCase().includes('back') || 
                    d.label.toLowerCase().includes('trasera') || 
                    d.label.toLowerCase().includes('rear')
                );
                selectedCameraId = backCamera ? backCamera.id : cameraDevices[0].id;
                startScanning();
            }
        } catch (err) {
            console.error('Error al obtener cámaras:', err);
            showLocalFeedback('error', 'Error de Cámara', 'No se pudo acceder a las cámaras. Asegúrate de dar permisos.');
        }
    });

    onDestroy(() => {
        stopScanning();
    });

    // Iniciar Escaneo
    const startScanning = async () => {
        if (!selectedCameraId || isScanning) return;

        try {
            scanResult = null;
            html5QrCode = new (window as any).Html5Qrcode('reader-element');
            isScanning = true;

            await html5QrCode.start(
                selectedCameraId,
                {
                    fps: 10,
                    qrbox: (width: number, height: number) => {
                        const size = Math.min(width, height) * 0.75;
                        return { width: size, height: size };
                    }
                },
                onScanSuccess,
                onScanFailure
            );
        } catch (err) {
            console.error('Error al iniciar escaneo:', err);
            isScanning = false;
            showLocalFeedback('error', 'Error de Escaneo', 'No se pudo iniciar el escáner con la cámara seleccionada.');
        }
    };

    // Detener Escaneo
    const stopScanning = async () => {
        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
                html5QrCode = null;
                isScanning = false;
            } catch (err) {
                console.error('Error al detener escaneo:', err);
            }
        }
    };

    // Cambiar Cámara
    const handleCameraChange = async (event: Event) => {
        const target = event.target as HTMLSelectElement;
        selectedCameraId = target.value;
        await stopScanning();
        setTimeout(startScanning, 300);
    };

    // Al detectar un código QR con éxito
    const onScanSuccess = async (decodedText: string) => {
        // Pausar escaneo
        await stopScanning();

        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100]);
        }

        // Llamar callback del padre para validar
        try {
            const result = await onScan(decodedText);
            scanResult = result;
        } catch (err) {
            console.error('Error in onScan callback:', err);
            showLocalFeedback('error', 'Error del Sistema', 'Ocurrió un error al procesar el código QR.');
        }

        // Reanudar escaneo después de 3.5 segundos
        setTimeout(() => {
            if (isMounted) {
                startScanning();
            }
        }, 3500);
    };

    const onScanFailure = (error: any) => {
        // Ignoramos fallos continuos de lectura
    };

    const showLocalFeedback = (status: 'success' | 'warning' | 'error', title: string, message: string) => {
        scanResult = { status, title, message };
    };
</script>

<div class="camera-card">
    <div class="camera-select-box">
        <label for="camera-select">Cámara Activa</label>
        <select id="camera-select" class="camera-select" onchange={handleCameraChange}>
            {#if cameraDevices.length === 0}
                <option>Cargando cámaras disponibles...</option>
            {:else}
                {#each cameraDevices as device}
                    <option value={device.id} selected={device.id === selectedCameraId}>
                        {device.label || `Cámara ${device.id}`}
                    </option>
                {/each}
            {/if}
        </select>
    </div>

    <div class="reader-wrapper">
        <div id="reader-element"></div>

        <!-- Overlay dinámico -->
        {#if isScanning && !scanResult}
            <div class="scanner-overlay" in:fade>
                <div class="corner-border"></div>
                <div class="laser-line"></div>
            </div>
        {/if}

        <!-- Toast Flotante de Validación -->
        {#if scanResult}
            <div class="validation-toast toast-{scanResult.status}" in:fade={{ duration: 200 }}>
                <div class="toast-icon">
                    {#if scanResult.status === 'success'}
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {:else if scanResult.status === 'warning'}
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    {:else}
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    {/if}
                </div>
                <h2 class="toast-title">{scanResult.title}</h2>
                <p class="toast-msg">{scanResult.message}</p>
            </div>
        {/if}
    </div>
</div>

<style>
    /* Lector de cámara */
    .camera-card {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }

    .camera-select-box {
        padding: 1rem 1.5rem;
        background: rgba(15, 23, 42, 0.6);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .camera-select-box label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
    }

    .camera-select {
        width: 100%;
        background: #0f172a;
        color: #f1f5f9;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
        outline: none;
        cursor: pointer;
    }

    .reader-wrapper {
        width: 100%;
        aspect-ratio: 1 / 1;
        position: relative;
        background: #020617;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #reader-element {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }

    #reader-element :global(video) {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
    }

    /* Overlay escáner animado */
    .scanner-overlay {
        position: absolute;
        inset: 0;
        pointer-events: none;
        border: 3px solid transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .laser-line {
        position: absolute;
        width: 75%;
        height: 2px;
        background: #6366f1;
        box-shadow: 0 0 12px #6366f1, 0 0 4px #818cf8;
        top: 12.5%;
        animation: scanAnim 2.5s infinite linear;
    }

    .corner-border {
        position: absolute;
        width: 75%;
        height: 75%;
        border: 2px dashed rgba(255, 255, 255, 0.3);
        border-radius: 16px;
    }

    @keyframes scanAnim {
        0% { top: 12.5%; }
        50% { top: 87.5%; }
        100% { top: 12.5%; }
    }

    /* Alerta/Toast flotante de validación */
    .validation-toast {
        position: absolute;
        inset: 0;
        backdrop-filter: blur(12px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        text-align: center;
        z-index: 100;
        box-sizing: border-box;
    }

    .toast-success { background: rgba(6, 78, 59, 0.95); }
    .toast-warning { background: rgba(120, 53, 15, 0.95); }
    .toast-error { background: rgba(127, 29, 29, 0.95); }

    .toast-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .toast-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .toast-msg {
        font-size: 1.05rem;
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.4;
        max-width: 320px;
    }
</style>
