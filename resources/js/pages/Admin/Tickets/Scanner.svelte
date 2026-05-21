<script lang="ts">
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import QrCameraReader from './components/QrCameraReader.svelte';
    import ScannerStats from './components/ScannerStats.svelte';
    import RecentScansList from './components/RecentScansList.svelte';

    interface ScanResult {
        status: 'success' | 'warning' | 'error';
        title: string;
        message: string;
        seat?: string;
        name?: string;
    }

    interface ScanItem {
        status: 'success' | 'warning' | 'error';
        name: string;
        seat: string;
        event: string;
        time: string;
    }

    // Svelte 5 state runes
    let stats = $state({
        total: 0,
        valid: 0,
        invalid: 0
    });

    let recentScans = $state<ScanItem[]>([]);

    const addRecentScan = (status: 'success' | 'warning' | 'error', name: string, seat: string, event: string) => {
        recentScans = [
            { status, name, seat, event, time: new Date().toLocaleTimeString() },
            ...recentScans.slice(0, 4)
        ];
    };

    // Callback validation logic passed down to reader
    const handleScan = async (decodedText: string): Promise<ScanResult> => {
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
                addRecentScan('success', data.name || 'Invitado', data.seat || 'S/N', data.event || 'Evento');
                return {
                    status: 'success',
                    title: '¡Boleto Válido!',
                    message: `Asiento ${data.seat || 'S/N'} registrado para ${data.name || 'Invitado'}.`,
                    seat: data.seat,
                    name: data.name
                };
            } else if (data.result === 'ya_validado') {
                stats.total -= 1; // No contar como nuevo escaneo
                addRecentScan('warning', data.name || 'Invitado', data.seat || 'S/N', 'Ya ingresado');
                return {
                    status: 'warning',
                    title: 'Ya Validado',
                    message: `Escaneado previamente el ${data.validated_at}. Asistente: ${data.name || 'Invitado'}.`,
                    seat: data.seat,
                    name: data.name
                };
            } else {
                stats.invalid += 1;
                addRecentScan('error', 'Desconocido', 'N/A', 'Inválido');
                return {
                    status: 'error',
                    title: 'Boleto Inválido',
                    message: 'El código escaneado no es válido o está impago.'
                };
            }
        } catch (err) {
            console.error('Error al validar boleto:', err);
            stats.invalid += 1;
            addRecentScan('error', 'Error de Red', 'N/A', 'Error');
            return {
                status: 'error',
                title: 'Error de Red',
                message: 'No se pudo conectar con el servidor para validar el boleto.'
            };
        }
    };
</script>

<AppHead title="Validador de Boletos - Escáner de Cámara" />

<div class="scanner-dashboard" in:fade={{ duration: 400 }}>
    <!-- Encabezado -->
    <div class="header">
        <h1>Escáner de Boletos</h1>
        <a href="/" class="btn-secondary" style="padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 10px; text-decoration: none;">
            Salir
        </a>
    </div>

    <!-- Lector / Cámara componentizado -->
    <QrCameraReader onScan={handleScan} />

    <!-- Módulo de Estadísticas rápidas componentizado -->
    <ScannerStats {stats} />

    <!-- Lista de Escaneos Recientes componentizado -->
    <RecentScansList {recentScans} />
</div>

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
</style>
