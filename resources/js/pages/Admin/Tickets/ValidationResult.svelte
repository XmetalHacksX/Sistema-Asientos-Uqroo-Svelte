<script>
    import { fade, scale } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';

    // Props que vienen de Inertia
    let { result, seat, event, name, validated_at } = $props();

    // Textos y estilos dinámicos basados en el resultado de validación
    const themes = {
        success: {
            bg: 'linear-gradient(135deg, #064e3b, #022c22)',
            border: 'rgba(52, 211, 153, 0.2)',
            color: '#34d399',
            iconBg: 'rgba(52, 211, 153, 0.1)',
            title: '¡Acceso Permitido!',
            subtitle: 'Boleto válido y registrado con éxito.',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16"><polyline points="20 6 9 17 4 12"></polyline></svg>`
        },
        ya_validado: {
            bg: 'linear-gradient(135deg, #78350f, #451a03)',
            border: 'rgba(251, 191, 36, 0.2)',
            color: '#fbbf24',
            iconBg: 'rgba(251, 191, 36, 0.1)',
            title: 'Boleto Ya Escaneado',
            subtitle: 'Este boleto ya fue validado previamente.',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`
        },
        invalido: {
            bg: 'linear-gradient(135deg, #7f1d1d, #450a0a)',
            border: 'rgba(248, 113, 113, 0.2)',
            color: '#f87171',
            iconBg: 'rgba(248, 113, 113, 0.1)',
            title: 'Boleto Inválido',
            subtitle: 'El boleto no existe o no ha sido pagado.',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`
        }
    };

    const currentTheme = $derived(themes[result] || themes.invalido);
</script>

<AppHead title={`Validación de Boleto - ${currentTheme.title}`} />

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Space+Grotesk:wght@500;700&display=swap');

    .viewport {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        box-sizing: border-box;
        font-family: 'Outfit', sans-serif;
        color: #f8fafc;
    }

    .result-card {
        width: 100%;
        max-width: 480px;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(20px);
        border-radius: 28px;
        padding: 2.5rem;
        text-align: center;
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
        box-sizing: border-box;
    }

    .icon-wrapper {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
    }

    .icon-wrapper :global(svg) {
        width: 48px;
        height: 48px;
    }

    .status-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        letter-spacing: -0.02em;
    }

    .status-subtitle {
        font-size: 1.05rem;
        color: #94a3b8;
        line-height: 1.5;
        margin-bottom: 2.5rem;
    }

    /* Detalles del Boleto */
    .details-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
    }

    .val {
        font-size: 1rem;
        font-weight: 600;
        color: #f1f5f9;
        text-align: right;
    }

    .seat-badge {
        background: rgba(99, 102, 241, 0.15);
        border: 1px solid rgba(99, 102, 241, 0.3);
        color: #a5b4fc;
        padding: 0.2rem 0.75rem;
        border-radius: 8px;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* Botón de acción */
    .btn-action {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1rem 2rem;
        border-radius: 16px;
        font-weight: 600;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        background: #f8fafc;
        color: #0f172a;
        box-shadow: 0 4px 14px rgba(255, 255, 255, 0.1);
        text-decoration: none;
    }

    .btn-action:hover {
        background: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 255, 255, 0.15);
    }
</style>

<div class="viewport" style="background: {currentTheme.bg};" in:fade={{ duration: 400 }}>
    <div class="result-card" style="border: 1px solid {currentTheme.border};" in:scale={{ duration: 450, start: 0.95 }}>
        <!-- Ícono circular -->
        <div class="icon-wrapper" style="background-color: {currentTheme.iconBg}; color: {currentTheme.color};">
            {@html currentTheme.icon}
        </div>

        <!-- Títulos -->
        <h1 class="status-title" style="color: {currentTheme.color};">{currentTheme.title}</h1>
        <p class="status-subtitle">{currentTheme.subtitle}</p>

        <!-- Información de boleto si no es inválido -->
        {#if result !== 'invalido'}
            <div class="details-box">
                <div class="detail-row">
                    <span class="label">Asistente</span>
                    <span class="val">{name}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Evento</span>
                    <span class="val" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {event}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Asiento</span>
                    <span class="seat-badge">{seat || 'S/N'}</span>
                </div>
                {#if result === 'ya_validado'}
                    <div class="detail-row" style="background: rgba(239, 68, 68, 0.05); margin: 0.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem; border-radius: 0 0 20px 20px;">
                        <span class="label" style="color: #f87171;">Escaneado el</span>
                        <span class="val" style="color: #f87171; font-size: 0.9rem;">{validated_at}</span>
                    </div>
                {/if}
            </div>
        {/if}

        <!-- Botón de acción -->
        <a href="/admin/boletos/escanear" class="btn-action">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
            </svg>
            Abrir Escáner Continuo
        </a>
    </div>
</div>
