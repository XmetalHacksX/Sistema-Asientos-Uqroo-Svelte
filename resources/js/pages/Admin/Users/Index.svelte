<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Usuarios', href: '/admin/users' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    let { users } = $props();

    function destroyUser(id) {
        if (!confirm('¿Eliminar este usuario?')) return;
        router.delete(`/admin/users/${id}`);
    }
</script>

<AppHead title="Usuarios" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Usuarios</h1>
            <p class="text-sm text-muted-foreground">
                Administra cuentas y asigna roles del sistema.
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs">Nombre</th>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs">Email</th>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs">Roles Asignados</th>
                    <th class="px-5 py-4 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sidebar-border/60">
                {#if users?.length}
                    {#each users as user}
                        <tr class="transition-colors hover:bg-muted/20">
                            <td class="px-5 py-4">
                                <span class="font-bold text-foreground text-base">{user.name}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-muted-foreground">{user.email}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    {#each user.roles as role}
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-primary/10 text-primary border border-primary/20 shadow-sm">
                                            {role.name}
                                        </span>
                                    {/each}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex gap-2 justify-end w-full">
                                    <Link
                                        href={`/admin/users/${user.id}/edit`}
                                        class="rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50 transition-colors shadow-sm"
                                    >
                                        Editar
                                    </Link>
                                    {#if user.email !== 'raul.andres.devs@gmail.com'}
                                        <button
                                            type="button"
                                            onclick={() => destroyUser(user.id)}
                                            class="rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-100 border border-red-100 transition-colors shadow-sm"
                                        >
                                            Eliminar
                                        </button>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                    {/each}
                {:else}
                    <tr>
                        <td class="px-5 py-8 text-center text-muted-foreground" colspan="4">
                            No hay usuarios.
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
