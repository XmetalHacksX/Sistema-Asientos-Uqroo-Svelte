<script lang="ts">
    import { slide } from 'svelte/transition';

    interface ScanItem {
        status: 'success' | 'warning' | 'error';
        name: string;
        seat: string;
        event: string;
        time: string;
    }

    // Props using Svelte 5 runes
    let { recentScans }: { recentScans: ScanItem[] } = $props();
</script>

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

<style>
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
