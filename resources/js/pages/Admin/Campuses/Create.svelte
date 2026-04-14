<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Campus', href: '/admin/campuses' },
            { title: 'Crear', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    let data = $state({
        name: '',
    });

    function submit() {
        router.post('/admin/campuses', data);
    }
</script>

<AppHead title="Crear Campus" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Crear Campus</h1>
            <p class="text-sm text-muted-foreground">Registra un nuevo campus.</p>
        </div>
        <Link
            href="/admin/campuses"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50"
        >
            Volver
        </Link>
    </div>

    <form
        class="max-w-xl space-y-4 rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        onsubmit={(e) => {
            e.preventDefault();
            submit();
        }}
    >
        <div>
            <label class="block text-sm font-medium text-foreground" for="name"
                >Nombre</label
            >
            <input
                id="name"
                class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                bind:value={data.name}
                required
            />
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
            >
                Guardar
            </button>
            <Link
                href="/admin/campuses"
                class="inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50"
            >
                Cancelar
            </Link>
        </div>
    </form>
</div>

