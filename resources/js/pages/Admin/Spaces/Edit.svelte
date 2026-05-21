<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Salas/Teatros', href: '/admin/spaces' },
            { title: 'Editar', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    type Building = {
        id: number;
        name: string;
    };

    type Space = {
        id: number;
        name: string;
        building_id: number;
        is_template?: boolean;
    };

    let { space, buildings }: { space: Space; buildings: Building[] } =
        $props();

    let data = $state({
        building_id: space.building_id as string | number,
        name: space.name,
        is_template: !!space.is_template,
    });

    function submit() {
        router.put(`/admin/spaces/${space.id}`, {
            building_id: Number(data.building_id),
            name: data.name,
            is_template: data.is_template,
        });
    }
</script>

<AppHead title="Editar Sala / Teatro" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">
                Editar Sala / Teatro
            </h1>
            <p class="text-sm text-muted-foreground">
                Actualiza el nombre, el edificio asignado y el estado de plantilla de esta sala.
            </p>
        </div>
        <Link
            href="/admin/spaces"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50"
        >
            Volver
        </Link>
    </div>

    <form
        class="max-w-2xl space-y-4 rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        onsubmit={(e) => {
            e.preventDefault();
            submit();
        }}
    >
        <div>
            <label
                class="block text-sm font-medium text-foreground"
                for="building_id">Edificio</label
            >
            <select
                id="building_id"
                class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                bind:value={data.building_id}
                required
            >
                <option value="" disabled>Selecciona un edificio</option>
                {#each buildings as b (b.id)}
                    <option value={b.id}>{b.name}</option>
                {/each}
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-foreground" for="name"
                >Nombre de la sala</label
            >
            <input
                id="name"
                class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                bind:value={data.name}
                required
            />
        </div>

        <div class="flex items-center gap-2 py-2">
            <input
                id="is_template"
                type="checkbox"
                class="h-4 w-4 rounded border-sidebar-border text-primary focus:ring-primary bg-background"
                bind:checked={data.is_template}
            />
            <label
                for="is_template"
                class="text-sm font-medium text-foreground cursor-pointer select-none"
                >Marcar esta sala como Plantilla reutilizable</label
            >
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                disabled={data.building_id === '' || data.name.trim() === ''}
            >
                Guardar Cambios
            </button>
            <Link
                href="/admin/spaces"
                class="inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50"
            >
                Cancelar
            </Link>
        </div>
    </form>
</div>
