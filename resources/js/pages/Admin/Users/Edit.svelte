<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Usuarios', href: '/admin/users' },
            { title: 'Editar', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router, page } from '@inertiajs/svelte';

    let { user, roles, userRoles } = $props();
    let errors = $derived(page.props.errors);

    const isSuperAdminUser = user.email === 'raul.andres.devs@gmail.com';

    let data = $state({
        name: user.name,
        email: user.email,
        roles: [...userRoles] as string[]
    });

    function submit(e) {
        e.preventDefault();
        router.put(`/admin/users/${user.id}`, data);
    }

    function toggleRole(roleName: string) {
        // En UI bloqueamos que el protegido pierda el super-admin
        if (isSuperAdminUser && roleName === 'super-admin') {
            return; 
        }

        if (data.roles.includes(roleName)) {
            data.roles = data.roles.filter(r => r !== roleName);
        } else {
            data.roles = [...data.roles, roleName];
        }
    }
</script>

<AppHead title={`Editar Usuario - ${user.name}`} />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Editar Usuario</h1>
            <p class="text-sm text-muted-foreground">
                Actualiza sus datos y asigna roles del sistema.
            </p>
        </div>
        <Link
            href="/admin/users"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50 shadow-sm"
            >Volver</Link
        >
    </div>

    <form
        class="max-w-3xl space-y-4 rounded-xl border border-sidebar-border/70 bg-background p-5 shadow-sm"
        onsubmit={submit}
    >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-foreground" for="name">Nombre</label>
                <input
                    id="name"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.name}
                    required
                />
                {#if errors.name}
                    <p class="mt-1 text-xs text-red-500">{errors.name}</p>
                {/if}
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground" for="email">Correo Electrónico</label>
                <input
                    id="email"
                    type="email"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary disabled:bg-muted/50 disabled:cursor-not-allowed"
                    bind:value={data.email}
                    disabled={isSuperAdminUser}
                    required
                />
                {#if errors.email}
                    <p class="mt-1 text-xs text-red-500">{errors.email}</p>
                {/if}
            </div>
        </div>

        <div class="pt-4">
            <h3 class="mb-3 text-sm font-medium text-foreground border-b pb-2 border-sidebar-border/50">Roles Asignados</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                {#each roles as role}
                    <label class="flex items-center space-x-2 cursor-pointer rounded border border-sidebar-border/50 bg-muted/10 px-3 py-2 hover:bg-muted/30 transition-colors">
                        <input 
                            type="checkbox" 
                            name="roles[]" 
                            value={role.name}
                            checked={data.roles.includes(role.name)}
                            onchange={() => toggleRole(role.name)}
                            disabled={isSuperAdminUser && role.name === 'super-admin'}
                            class="rounded border-sidebar-border text-primary shadow-sm focus:ring-primary h-4 w-4 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                        <span class="text-sm font-medium text-foreground">{role.name}</span>
                    </label>
                {/each}
            </div>
            {#if errors.roles}
                <p class="mt-2 text-xs text-red-500">{errors.roles}</p>
            {/if}
        </div>

        <div class="flex gap-2 pt-4 border-t border-sidebar-border/50 mt-6">
            <button
                type="submit"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 shadow-sm"
            >
                Guardar Cambios
            </button>
            <Link
                href="/admin/users"
                class="rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50 shadow-sm"
            >
                Cancelar
            </Link>
        </div>
    </form>
</div>
