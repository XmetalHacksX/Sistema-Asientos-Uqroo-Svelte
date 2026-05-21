<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Roles', href: '/admin/roles' },
            { title: 'Crear', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router, page } from '@inertiajs/svelte';

    let { permissions } = $props();
    let errors = $derived(page.props.errors);

    let data = $state({
        name: '',
        permissions: [] as string[]
    });

    function submit(e) {
        e.preventDefault();
        router.post('/admin/roles', data);
    }

    function togglePermission(permissionName: string) {
        if (data.permissions.includes(permissionName)) {
            data.permissions = data.permissions.filter(p => p !== permissionName);
        } else {
            data.permissions = [...data.permissions, permissionName];
        }
    }
</script>

<AppHead title="Crear Rol" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Crear Rol</h1>
            <p class="text-sm text-muted-foreground">
                Llena los datos y selecciona los permisos para el nuevo rol.
            </p>
        </div>
        <Link
            href="/admin/roles"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50 shadow-sm"
        >
            Volver
        </Link>
    </div>

    <form
        class="max-w-3xl space-y-4 rounded-xl border border-sidebar-border/70 bg-background p-5 shadow-sm"
        onsubmit={submit}
    >
        <div>
            <label class="block text-sm font-medium text-foreground" for="name">Nombre del Rol</label>
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

        <div class="pt-2">
            <h3 class="mb-3 text-sm font-medium text-foreground border-b pb-2 border-sidebar-border/50">Permisos del Sistema</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                {#each permissions as permission}
                    <label class="flex items-center space-x-2 cursor-pointer rounded border border-sidebar-border/50 bg-muted/10 px-3 py-2 hover:bg-muted/30 transition-colors">
                        <input 
                            type="checkbox" 
                            name="permissions[]" 
                            value={permission.name}
                            checked={data.permissions.includes(permission.name)}
                            onchange={() => togglePermission(permission.name)}
                            class="rounded border-sidebar-border text-primary shadow-sm focus:ring-primary h-4 w-4"
                        >
                        <span class="text-sm font-medium text-foreground">{permission.name}</span>
                    </label>
                {/each}
            </div>
            {#if errors.permissions}
                <p class="mt-2 text-xs text-red-500">{errors.permissions}</p>
            {/if}
        </div>

        <div class="flex gap-2 pt-4 border-t border-sidebar-border/50 mt-6">
            <button
                type="submit"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 shadow-sm"
            >
                Guardar Rol
            </button>
            <Link
                href="/admin/roles"
                class="rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50 shadow-sm"
            >
                Cancelar
            </Link>
        </div>
    </form>
</div>
