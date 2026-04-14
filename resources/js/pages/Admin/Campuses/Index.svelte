<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Campus', href: '/admin/campuses' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    type Campus = {
        id: number;
        name: string;
    };

    let { campuses }: { campuses: Campus[] } = $props();

    function destroyCampus(id: number) {
        if (!confirm('¿Eliminar este campus?')) return;
        router.delete(`/admin/campuses/${id}`);
    }
</script>

<AppHead title="Campus" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Campus</h1>
            <p class="text-sm text-muted-foreground">
                Administra campus para agrupar edificios.
            </p>
        </div>
        <Link
            href="/admin/campuses/create"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Crear Nuevo Campus
        </Link>
    </div>

    <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background dark:border-sidebar-border">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/40 text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">ID</th>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {#if campuses?.length}
                    {#each campuses as c (c.id)}
                        <tr class="border-t border-sidebar-border/60 dark:border-sidebar-border">
                            <td class="px-4 py-3">{c.id}</td>
                            <td class="px-4 py-3 font-medium text-foreground">
                                {c.name}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        href={`/admin/campuses/${c.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm hover:bg-muted/50"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => destroyCampus(c.id)}
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
                        <td class="px-4 py-6 text-muted-foreground" colspan="3">
                            No hay campus registrados.
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

