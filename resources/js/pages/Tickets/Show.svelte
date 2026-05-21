<script module>
    export const layout = null;
</script>

<script>
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import { 
        GraduationCap, Printer, ArrowLeft, CheckCircle, 
        Calendar, Clock, MapPin, User, Ticket, Award, School
    } from 'lucide-svelte';

    // Props que vienen de Inertia
    let { reservation, qrSvg, ticketPrice } = $props();

    // Formatear fechas
    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-MX', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatTime = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleTimeString('es-MX', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Imprimir boleto
    const handlePrint = () => {
        window.print();
    };

    const eventDate = $derived(formatDate(reservation.event?.start_time));
    const eventTime = $derived(formatTime(reservation.event?.start_time));
    const guestName = $derived(reservation.guest_info?.name || reservation.user?.name || 'Invitado');
    const guestEmail = $derived(reservation.guest_info?.email || reservation.user?.email || 'N/A');
</script>

<AppHead title={`Boleto Digital - ${reservation.event?.name || 'Evento'}`} />

<!-- Estilos específicos para impresión y animaciones fluidas -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Space+Grotesk:wght@400;700&display=swap');

    :global(body) {
        background: radial-gradient(circle at top left, #052014, #010604);
        font-family: 'Outfit', sans-serif;
        color: #f1f5f9;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    .ticket-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }

    /* Tarjeta Principal del Boleto */
    .ticket-card {
        background: rgba(13, 22, 17, 0.4);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(0, 94, 53, 0.25);
        border-radius: 24px;
        width: 100%;
        max-width: 800px;
        display: flex;
        flex-direction: row;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(212, 154, 21, 0.08);
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .ticket-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 100% 0%, rgba(212, 154, 21, 0.08), transparent 45%);
        pointer-events: none;
    }

    /* Sección Principal del Evento */
    .ticket-main {
        flex: 1;
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 2px dashed rgba(0, 94, 53, 0.3);
        position: relative;
    }

    /* Muescas laterales para simular ticket físico */
    .ticket-main::after,
    .ticket-stub::after {
        content: '';
        position: absolute;
        right: -12px;
        width: 24px;
        height: 24px;
        background: #010604;
        border-radius: 50%;
        z-index: 10;
        box-shadow: inset -4px 0 6px -2px rgba(0,0,0,0.5);
    }

    .ticket-main::after {
        top: -12px;
    }

    .ticket-stub::after {
        bottom: -12px;
        left: -12px;
        right: auto;
    }

    /* Sección Lateral de Validación (Tear-off stub) */
    .ticket-stub {
        width: 280px;
        padding: 3rem 2rem;
        background: rgba(8, 15, 11, 0.6);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        position: relative;
    }

    /* Encabezados y Textos */
    .badge {
        background: linear-gradient(135deg, #005E35, #004526);
        color: #f8fafc;
        border: 1px solid rgba(212, 154, 21, 0.3);
        padding: 0.35rem 1rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        align-self: flex-start;
        box-shadow: 0 4px 12px rgba(0, 94, 53, 0.3);
        margin-bottom: 1.5rem;
    }

    .status-badge {
        padding: 0.35rem 1rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        margin-top: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .status-confirmed {
        background: rgba(0, 94, 53, 0.2);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .status-validated {
        background: rgba(212, 154, 21, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(212, 154, 21, 0.4);
    }

    .event-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2.15rem;
        font-weight: 800;
        line-height: 1.25;
        background: linear-gradient(to right, #ffffff, #d8f3e5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 2rem;
    }

    /* Retícula de Detalles */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem 2rem;
        margin-top: auto;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #688f7b;
        margin-bottom: 0.25rem;
    }

    .detail-value {
        font-size: 1rem;
        font-weight: 700;
        color: #f8fafc;
    }

    /* Asiento Destacado */
    .seat-highlight {
        background: rgba(212, 154, 21, 0.1);
        border: 1px dashed rgba(212, 154, 21, 0.45);
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        align-self: flex-start;
    }

    .seat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.45rem;
        font-weight: 800;
        color: #fbbf24;
    }

    /* Estilo del QR SVG */
    .qr-box {
        background: white;
        padding: 1.25rem;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        margin-bottom: 1.5rem;
    }

    .qr-box:hover {
        transform: scale(1.05);
    }

    .qr-box :global(svg) {
        width: 170px !important;
        height: 170px !important;
        display: block;
    }

    .stub-instruction {
        font-size: 0.78rem;
        color: #8fae9b;
        line-height: 1.4;
        max-width: 200px;
        margin-top: 0.5rem;
    }

    /* Botones de acción */
    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        z-index: 20;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-primary {
        background: #f8fafc;
        color: #005E35;
        box-shadow: 0 4px 14px rgba(255, 255, 255, 0.1);
    }

    .btn-primary:hover {
        background: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 255, 255, 0.15);
    }

    .btn-secondary {
        background: rgba(0, 94, 53, 0.2);
        color: #d8f3e5;
        border: 1px solid rgba(0, 94, 53, 0.4);
    }

    .btn-secondary:hover {
        background: rgba(0, 94, 53, 0.3);
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ticket-card {
            flex-direction: column;
            border-radius: 20px;
            max-width: 450px;
        }

        .ticket-main {
            border-right: none;
            border-bottom: 2px dashed rgba(0, 94, 53, 0.3);
            padding: 2rem;
        }

        .ticket-stub {
            width: 100%;
            padding: 2.5rem 2rem;
        }

        .ticket-main::after,
        .ticket-stub::after {
            display: none;
        }

        .event-title {
            font-size: 1.65rem;
        }
    }

    /* CSS para Impresión — diseño limpio optimizado para papel */
    @media print {
        :global(body) {
            background: #ffffff !important;
            color: #1e293b !important;
            font-family: 'Outfit', sans-serif !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .ticket-container {
            padding: 0.5in;
            min-height: auto;
            display: block;
        }

        .ticket-card {
            background: #ffffff !important;
            border: 2px solid #005E35 !important;
            border-radius: 16px !important;
            box-shadow: none !important;
            flex-direction: row !important;
            max-width: 100% !important;
            page-break-inside: avoid;
        }

        .ticket-card::before {
            display: none !important;
        }

        .ticket-main {
            border-right: 2px dashed #005E35 !important;
            padding: 0.4in !important;
            color: #1e293b !important;
        }

        .ticket-main::after,
        .ticket-stub::after {
            background: #ffffff !important;
            box-shadow: inset -3px 0 5px -2px rgba(0,0,0,0.1) !important;
            border: 1px solid #005E35 !important;
        }

        .ticket-stub {
            width: 240px !important;
            padding: 0.4in !important;
            background: #fbfdfc !important;
            border-left: none !important;
        }

        .badge {
            display: inline-flex !important;
            background: #005E35 !important;
            color: white !important;
            padding: 0.25rem 0.8rem !important;
            border-radius: 9999px !important;
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.15em !important;
            text-transform: uppercase !important;
            box-shadow: none !important;
            margin-bottom: 1rem !important;
            border: none !important;
        }

        .event-title {
            background: none !important;
            -webkit-text-fill-color: initial !important;
            color: #0f172a !important;
            font-size: 1.6rem !important;
            margin-bottom: 1rem !important;
        }

        .details-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.75rem 1.5rem !important;
            margin-top: auto !important;
        }

        .detail-label {
            color: #475569 !important;
            font-size: 0.65rem !important;
        }

        .detail-value {
            color: #0f172a !important;
            font-size: 0.95rem !important;
        }

        .seat-highlight {
            background: #f0fdf4 !important;
            border: 1px dashed #D49A15 !important;
            padding: 0.5rem 1rem !important;
            border-radius: 10px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            margin-top: 0.75rem !important;
        }

        .seat-number {
            color: #D49A15 !important;
            font-size: 1.25rem !important;
            font-weight: 800 !important;
        }

        .qr-box {
            background: white !important;
            padding: 0.75rem !important;
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: none !important;
            margin-bottom: 1rem !important;
        }

        .qr-box :global(svg) {
            width: 140px !important;
            height: 140px !important;
        }

        .stub-instruction {
            color: #475569 !important;
            font-size: 0.7rem !important;
        }

        .status-badge {
            display: inline-flex !important;
            padding: 0.2rem 0.8rem !important;
            border-radius: 9999px !important;
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            box-shadow: none !important;
            margin-top: 0.75rem !important;
        }

        .status-confirmed {
            background: #e6f7f0 !important;
            color: #005E35 !important;
            border: 1px solid #a7f3d0 !important;
        }

        .status-validated {
            background: #fffbeb !important;
            color: #D49A15 !important;
            border: 1px solid #fde68a !important;
        }

        .btn, .actions {
            display: none !important;
        }
    }
</style>

<div class="ticket-container" in:fade={{ duration: 600 }}>
    <!-- Tarjeta del Boleto -->
    <div class="ticket-card">
        <!-- Cuerpo Principal del Boleto -->
        <div class="ticket-main">
            <span class="badge">Boleto Digital · UQROO</span>
            
            <h1 class="event-title">{reservation.event?.name}</h1>

            <div class="seat-highlight">
                <span class="detail-label" style="margin-bottom: 0; color: #b45309;">Asiento:</span>
                <span class="seat-number">{reservation.node?.identifier || 'S/N'}</span>
            </div>

            <!-- Retícula con Detalles -->
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Fecha</span>
                    <span class="detail-value">{eventDate}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Hora</span>
                    <span class="detail-value">{eventTime}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Ubicación</span>
                    <span class="detail-value">{reservation.event?.space?.name || 'Teatro Universitario UQROO'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Asistente</span>
                    <span class="detail-value">{guestName}</span>
                </div>
            </div>
        </div>

        <!-- Stub Derecho (Código QR / Validación) -->
        <div class="ticket-stub">
            <div class="qr-box">
                {@html qrSvg}
            </div>

            <span class="detail-label">Código de Boleto</span>
            <span class="detail-value" style="font-family: 'Space Grotesk', sans-serif; font-size: 0.85rem; letter-spacing: 0.05em; color: #fbbf24;">
                {reservation.ticket_token ? reservation.ticket_token.substring(0, 18).toUpperCase() : 'N/A'}...
            </span>

            {#if reservation.status === 'ingresado'}
                <span class="status-badge status-validated">Ingresado</span>
                <p class="stub-instruction" style="color: #fbbf24;">
                    Acceso registrado el {formatDate(reservation.validated_at)} a las {formatTime(reservation.validated_at)}
                </p>
            {:else}
                <span class="status-badge status-confirmed">Confirmado</span>
                <p class="stub-instruction">Presenta este código QR en la entrada para que sea escaneado.</p>
            {/if}
        </div>
    </div>

    <!-- Botones de Acción (Imprimir / Regresar) -->
    <div class="actions">
        <button class="btn btn-primary" onclick={handlePrint}>
            <Printer class="h-4.5 w-4.5" />
            Imprimir Boleto
        </button>
        <a href="/" class="btn btn-secondary">
            Regresar a Cartelera
        </a>
    </div>
</div>
