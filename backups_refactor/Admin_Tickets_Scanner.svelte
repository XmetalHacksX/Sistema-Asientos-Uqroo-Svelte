<script>
    import { onMount, onDestroy } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { fade, slide } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';

    let html5QrCode = null;
    let cameraDevices = [];
    let selectedCameraId = '';
    let isScanning = false;
    let scanResult = null; // { status: 'success'|'error', title: '', message: '', seat: '', name: '' }
    let stats = { total: 0, valid: 0, invalid: 0 };
    let recentScans = [];
    let isMounted = false;

    // Cargar librería de QR desde CDN e inicializar
    onMount(async () => {
        isMounted = true;
        if (typeof window === 'undefined') return;

        // Cargar script dinámicamente si no existe
        if (!window.Html5Qrcode) {
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
            const devices = await window.Html5Qrcode.getCameras();
            cameraDevices = devices || [];
            if (cameraDevices.length > 0) {
                // Auto-seleccionar cámara trasera si existe, de lo contrario la primera
                const backCamera = cameraDevices.find(d => 
                    d.label.toLowerCase().includes('back') || 
                    d.label.toLowerCase().includes('trasera') || 
                    d.label.toLowerCase().includes('rear')
                );
                selectedCameraId = backCamera ? backCamera.id : cameraDevices[0].id;
                startScanning();
            }
        } catch (err) {
            console.error('Error al obtener cámaras:', err);
            showFeedback('error', 'Error de Cámara', 'No se pudo acceder a las cámaras. Asegúrate de dar permisos.');
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
            html5QrCode = new window.Html5Qrcode('reader-element');
            isScanning = true;

            await html5QrCode.start(
                selectedCameraId,
                {
                    fps: 10,
                    qrbox: (width, height) => {
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
            showFeedback('error', 'Error de Escaneo', 'No se pudo iniciar el escáner con la cámara seleccionada.');
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
    const handleCameraChange = async (event) => {
        selectedCameraId = event.target.value;
        await stopScanning();
        setTimeout(startScanning, 300);
    };

    // Al detectar un código QR con éxito
    const onScanSuccess = async (decodedText) => {
        // Pausar escaneo para no procesar múltiples veces en milisegundos
        await stopScanning();

        // Feedback háptico si es soportado por el celular del validador
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100]);
        }

        // Intentar parsear el token: puede ser una URL completa o un token directo
        let token = decodedText.trim();
        if (token.includes('/')) {
            const parts = token.split('/');
            token = parts[parts.length - 1]; // Obtiene la última sección
        }

        try {
            // Realizar validación vía POST AJAX al servidor
            const response = await fetch('/admin/boletos/validar-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ token })
            });

            if (!response.ok) throw new Error('Error de servidor');

            const data = await response.json();
            stats.total += 1;

            if (data.result === 'success') {
                stats.valid += 1;
                showFeedback('success', '¡Boleto Válido!', `Asiento ${data.seat || 'S/N'} registrado para ${data.name || 'Invitado'}.`, data);
                addRecentScan('success', data.name, data.seat, data.event);
            } else if (data.result === 'ya_validado') {
                stats.total -= 1; // No contar como nuevo escaneo
                showFeedback('warning', 'Ya Validado', `Escaneado previamente el ${data.validated_at}. Asistente: ${data.name || 'Invitado'}.`, data);
                addRecentScan('warning', data.name, data.seat, 'Ya ingresado');
            } else {
                stats.invalid += 1;
                showFeedback('error', 'Boleto Inválido', 'El código escaneado no es válido o está impago.', null);
                addRecentScan('error', 'Desconocido', 'N/A', 'Inválido');
            }
        } catch (err) {
            console.error('Error al validar boleto:', err);
            showFeedback('error', 'Error de Red', 'No se pudo conectar con el servidor para validar el boleto.', null);
        }

        // Reanudar escaneo después de 3.5 segundos de mostrar la alerta
        setTimeout(() => {
            if (isMounted) startScanning();
        }, 3500);
    };

    const onScanFailure = (error) => {
        // Ignoramos fallos continuos de lectura (cuando la cámara busca QRs pero no encuentra)
    };

    const showFeedback = (status, title, message, data = null) => {
        scanResult = { status, title, message, seat: data?.seat || '', name: data?.name || '' };
    };

    const addRecentScan = (status, name, seat, event) => {
        recentScans = [{ status, name, seat, event, time: new Date().toLocaleTimeString() }, ...recentScans.slice(0, 4)];
    };
</script>

<AppHead title="Validador de Boletos - Escáner de Cámara" />

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap');

    :global(body) {
        background: #090d16;
        font-family: 'Outfit', sans-serif;
        color: #f1f5f9;
        margin: 0;
        padding: 0;
    }

    .scanner-dashboard {
        max-width: 600px;
        margin: 0 auto;
        padding: 1.5rem 1rem 4rem;
        min-height: 100vh;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Encabezado */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        padding: 1rem 1.5rem;
    }

    .header h1 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.25rem;
        margin: 0;
        font-weight: 700;
        background: linear-gradient(135deg, #a5b4fc, #818cf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

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

    /* Módulo de Estadísticas */
    .stats-card {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .stat-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 1rem 0.5rem;
        text-align: center;
    }

    .stat-box label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.05em;
        display: block;
        margin-bottom: 0.25rem;
    }

    .stat-num {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
    }

    /* Escaneos Recientes */
    .recent-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        padding: 1.5rem;
    }

    .recent-card h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.05rem;
        margin: 0 0 1rem 0;
        font-weight: 700;
        color: #94a3b8;
    }

    .recent-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recent-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 1rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.01);
        border: 1px solid rgba(255, 255, 255, 0.03);
    }

    .recent-info {
        display: flex;
        flex-direction: column;
    }

    .recent-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #f1f5f9;
    }

    .recent-event {
        font-size: 0.75rem;
        color: #64748b;
    }

    .recent-meta {
        text-align: right;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .recent-seat {
        background: rgba(99, 102, 241, 0.1);
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: #818cf8;
        padding: 0.1rem 0.4rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        font-family: 'Space Grotesk', sans-serif;
    }

    .recent-time {
        font-size: 0.65rem;
        color: #64748b;
        margin-top: 0.2rem;
    }
</style>

<div class="scanner-dashboard" in:fade={{ duration: 400 }}>
    <!-- Encabezado -->
    <div class="header">
        <h1>Escáner de Boletos</h1>
        <a href="/" class="btn-secondary" style="padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 10px; text-decoration: none;">
            Salir
        </a>
    </div>

    <!-- Lector / Cámara -->
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

    <!-- Módulo de Estadísticas rápidas -->
    <div class="stats-card">
        <div class="stat-box" style="border-color: rgba(99, 102, 241, 0.1);">
            <label>Escaneados</label>
            <span class="stat-num" style="color: #818cf8;">{stats.total}</span>
        </div>
        <div class="stat-box" style="border-color: rgba(52, 211, 153, 0.1);">
            <label>Válidos</label>
            <span class="stat-num" style="color: #34d399;">{stats.valid}</span>
        </div>
        <div class="stat-box" style="border-color: rgba(248, 113, 113, 0.1);">
            <label>Rechazados</label>
            <span class="stat-num" style="color: #f87171;">{stats.invalid}</span>
        </div>
    </div>

    <!-- Lista de Escaneos Recientes -->
    <div class="recent-card">
        <h2>Historial Reciente (Sesión)</h2>
        {#if recentScans.length === 0}
            <p style="font-size: 0.85rem; color: #64748b; text-align: center; margin: 1rem 0;">
                No se han realizado lecturas en esta sesión.
            </p>
        {:else}
            <div class="recent-list">
                {#each recentScans as scan}
                    <div class="recent-item" transition:slide>
                        <div class="recent-info">
                            <span class="recent-name" style="color: {scan.status === 'success' ? '#34d399' : scan.status === 'warning' ? '#fbbf24' : '#f87171'}">
                                {scan.name}
                            </span>
                            <span class="recent-event">{scan.event}</span>
                        </div>
                        <div class="recent-meta">
                            <span class="recent-seat">{scan.seat || 'S/N'}</span>
                            <span class="recent-time">{scan.time}</span>
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</div>
