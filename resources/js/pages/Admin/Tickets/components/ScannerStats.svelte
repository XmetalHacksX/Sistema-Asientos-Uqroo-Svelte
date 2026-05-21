<script lang="ts">
    interface Stats {
        total: number;
        valid: number;
        invalid: number;
    }

    // Props using Svelte 5 runes
    let { stats }: { stats: Stats } = $props();
</script>

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

<style>
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
</style>
