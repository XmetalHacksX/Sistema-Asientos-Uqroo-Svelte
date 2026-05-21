<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Roles', href: '/admin/roles' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    let { roles } = $props();

    function destroyRole(id) {
        if (!confirm('¿Eliminar este rol?')) return;
        router.delete(`/admin/roles/${id}`);
    }
</script>

<AppHead title="Roles y Permisos" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Roles y Permisos</h1>
            <p class="text-sm text-muted-foreground">
                Administra roles y permisos para controlar el acceso al sistema.
            </p>
        </div>
        <Link
            href="/admin/roles/create"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 shadow-sm"
        >
            Crear Nuevo Rol
        </Link>
    </div>

    <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs">Nombre</th>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs">Permisos</th>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sidebar-border/60">
                {#if roles?.length}
                    {#each roles as role}
                        <tr class="transition-colors hover:bg-muted/20">
                            <td class="px-5 py-4">
                                <span class="font-bold text-foreground text-base">{role.name}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-muted-foreground">
                                    {role.permissions?.length || 0} permisos
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex gap-2 justify-end w-full">
                                    <Link
                                        href={`/admin/roles/${role.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50 transition-colors shadow-sm"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => destroyRole(role.id)}
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
                        <td class="px-5 py-8 text-center text-muted-foreground" colspan="3">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="h-10 w-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span>No hay roles registrados.</span>
                            </div>
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
