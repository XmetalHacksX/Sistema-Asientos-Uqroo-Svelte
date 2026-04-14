<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Salas/Teatros', href: '/admin/spaces' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    type SpaceRow = {
        id: number;
        name: string;
        building_id: number | null;
        building?: { id: number; name: string } | null;
        nodes_count?: number;
    };

    let { spaces }: { spaces: SpaceRow[] } = $props();

    function destroySpace(id: number) {
        if (!confirm('¿Eliminar esta sala/teatro? Se eliminarán sus asientos.'))
            return;
        router.delete(`/admin/spaces/${id}`);
    }
</script>

<AppHead title="Salas / Teatros" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">
                Salas / Teatros
            </h1>
            <p class="text-sm text-muted-foreground">
                Crea salas y genera asientos automáticamente.
            </p>
        </div>
        <Link
            href="/admin/spaces/create"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Crear Sala
        </Link>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background dark:border-sidebar-border"
    >
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/40 text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nombre de la Sala</th>
                    <th class="px-4 py-3 font-medium">Edificio</th>
                    <th class="px-4 py-3 font-medium">Cantidad de Asientos</th>
                    <th class="px-4 py-3 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {#if spaces?.length}
                    {#each spaces as s (s.id)}
                        <tr
                            class="border-t border-sidebar-border/60 dark:border-sidebar-border"
                        >
                            <td class="px-4 py-3 font-medium text-foreground">
                                {s.name}
                            </td>
                            <td class="px-4 py-3">
                                {s.building?.name ??
                                    (s.building_id ? `#${s.building_id}` : '-')}
                            </td>
                            <td class="px-4 py-3">
                                {typeof s.nodes_count === 'number'
                                    ? s.nodes_count
                                    : `ID: ${s.id}`}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        href={`/admin/teatro/spaces/${s.id}/edit`}
                                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:opacity-90"
                                    >
                                        Diseñar Plano
                                    </Link>

                                    <Link
                                        href={`/admin/spaces/${s.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50"
                                    >
                                        Editar
                                    </Link>

                                    <button
                                        type="button"
                                        onclick={() => destroySpace(s.id)}
                                        class="rounded-lg bg-destructive px-3 py-1.5 text-sm font-medium text-destructive-foreground hover:opacity-90"
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
                            class="px-4 py-6 text-center text-muted-foreground"
                            colspan="4"
                        >
                            No hay salas registradas.
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
