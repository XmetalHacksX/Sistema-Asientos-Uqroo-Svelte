<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Edificios', href: '/admin/buildings' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    type Building = {
        id: number;
        name: string;
        pos_x: number;
        pos_y: number;
        width: number;
        height: number;
        campus_id: number | null;
    };

    let { buildings }: { buildings: Building[] } = $props();

    function destroyBuilding(id: number) {
        if (!confirm('¿Eliminar este edificio?')) return;
        router.delete(`/admin/buildings/${id}`);
    }
</script>

<AppHead title="Edificios" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Edificios</h1>
            <p class="text-sm text-muted-foreground">
                Administra edificios y su campus asociado.
            </p>
        </div>
        <Link
            href="/admin/buildings/create"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Crear Edificio
        </Link>
    </div>

    <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background dark:border-sidebar-border">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/40 text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium">X</th>
                    <th class="px-4 py-3 font-medium">Y</th>
                    <th class="px-4 py-3 font-medium">Ancho</th>
                    <th class="px-4 py-3 font-medium">Alto</th>
                    <th class="px-4 py-3 font-medium">Campus ID</th>
                    <th class="px-4 py-3 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {#if buildings?.length}
                    {#each buildings as b (b.id)}
                        <tr class="border-t border-sidebar-border/60 dark:border-sidebar-border">
                            <td class="px-4 py-3 font-medium text-foreground">
                                {b.name}
                            </td>
                            <td class="px-4 py-3">{b.pos_x}</td>
                            <td class="px-4 py-3">{b.pos_y}</td>
                            <td class="px-4 py-3">{b.width}</td>
                            <td class="px-4 py-3">{b.height}</td>
                            <td class="px-4 py-3">{b.campus_id ?? '-'}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        href={`/admin/buildings/${b.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm hover:bg-muted/50"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => destroyBuilding(b.id)}
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
                        <td class="px-4 py-6 text-muted-foreground" colspan="7">
                            No hay edificios registrados.
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

