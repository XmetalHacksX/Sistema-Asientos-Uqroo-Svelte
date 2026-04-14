<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Eventos', href: '/admin/events' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    let { events } = $props();

    function destroyEvent(id: number) {
        if (!confirm('¿Eliminar este evento?')) return;
        router.delete(`/admin/events/${id}`);
    }

    // Formateador limpio para la tabla
    function formatDateTime(dateStr: string) {
        if (!dateStr) return '-';
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
    }
</script>

<AppHead title="Eventos" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Eventos</h1>
            <p class="text-sm text-muted-foreground">
                Administra eventos y el espacio asignado.
            </p>
        </div>
        <Link
            href="/admin/events/create"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 shadow-sm"
        >
            Crear Nuevo Evento
        </Link>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-sm"
    >
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th
                        class="px-5 py-4 font-semibold uppercase tracking-wider text-xs w-20 text-center"
                        >Póster</th
                    >
                    <th
                        class="px-5 py-4 font-semibold uppercase tracking-wider text-xs"
                        >Evento</th
                    >
                    <th
                        class="px-5 py-4 font-semibold uppercase tracking-wider text-xs"
                        >Inicio</th
                    >
                    <th
                        class="px-5 py-4 font-semibold uppercase tracking-wider text-xs"
                        >Espacio Asignado</th
                    >
                    <th
                        class="px-5 py-4 font-semibold uppercase tracking-wider text-xs text-right"
                        >Acciones</th
                    >
                </tr>
            </thead>
            <tbody class="divide-y divide-sidebar-border/60">
                {#if events?.length}
                    {#each events as ev}
                        <tr class="transition-colors hover:bg-muted/20">
                            <td class="px-5 py-4">
                                <div
                                    class="relative mx-auto h-16 w-12 shrink-0 overflow-hidden rounded-md border border-sidebar-border/50 bg-slate-100 shadow-sm"
                                >
                                    {#if ev.poster_url}
                                        <img
                                            src={ev.poster_url}
                                            alt={`Póster de ${ev.name}`}
                                            class="h-full w-full object-cover"
                                        />
                                    {:else}
                                        <div
                                            class="flex h-full w-full flex-col items-center justify-center text-[9px] font-bold text-slate-400"
                                        >
                                            <svg
                                                class="h-4 w-4 mb-0.5 opacity-50"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg"
                                                ><path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                ></path></svg
                                            >
                                            N/A
                                        </div>
                                    {/if}
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span
                                        class="font-bold text-foreground text-base"
                                        >{ev.name}</span
                                    >
                                    {#if ev.description}
                                        <span
                                            class="text-xs text-muted-foreground line-clamp-1 max-w-[250px] mt-0.5"
                                            >{ev.description}</span
                                        >
                                    {/if}
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 text-slate-600 capitalize font-medium"
                            >
                                {formatDateTime(ev.start_time)}
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"
                                    ></span>
                                    {ev.space?.name ||
                                        `Sala ID: ${ev.space_id}`}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div
                                    class="inline-flex gap-2 justify-end w-full"
                                >
                                    <Link
                                        href={`/admin/events/${ev.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50 transition-colors shadow-sm"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => destroyEvent(ev.id)}
                                        class="rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-100 border border-red-100 transition-colors shadow-sm"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    {/each}
                {:else}
                    <tr>
                        <td
                            class="px-5 py-8 text-center text-muted-foreground"
                            colspan="5"
                        >
                            <div
                                class="flex flex-col items-center justify-center"
                            >
                                <svg
                                    class="h-10 w-10 text-slate-300 mb-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    ><path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    ></path></svg
                                >
                                <span>No hay eventos registrados.</span>
                            </div>
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
