<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Edificios', href: '/admin/buildings' },
            { title: 'Editar', href: '#' },
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

    type Building = {
        id: number;
        name: string;
        campus_id: number | null;
        pos_x: number;
        pos_y: number;
        width: number;
        height: number;
    };

    let { building, campuses }: { building: Building; campuses: Campus[] } =
        $props();

    let data = $state({
        name: building.name ?? '',
        campus_id: (building.campus_id ?? '') as string | number,
        pos_x: building.pos_x ?? 0,
        pos_y: building.pos_y ?? 0,
        width: building.width ?? 0,
        height: building.height ?? 0,
    });

    function submit() {
        router.put(`/admin/buildings/${building.id}`, {
            ...data,
            campus_id:
                data.campus_id === '' ? null : Number(data.campus_id),
        });
    }
</script>

<AppHead title="Editar Edificio" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Editar Edificio</h1>
            <p class="text-sm text-muted-foreground">
                Actualiza los datos del edificio.
            </p>
        </div>
        <Link
            href="/admin/buildings"
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
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
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

            <div class="md:col-span-2">
                <label
                    class="block text-sm font-medium text-foreground"
                    for="campus_id"
                    >Campus</label
                >
                <select
                    id="campus_id"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.campus_id}
                >
                    <option value="">Sin campus</option>
                    {#each campuses as c (c.id)}
                        <option value={c.id}>{c.name}</option>
                    {/each}
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground" for="pos_x"
                    >X</label
                >
                <input
                    id="pos_x"
                    type="number"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.pos_x}
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-foreground" for="pos_y"
                    >Y</label
                >
                <input
                    id="pos_y"
                    type="number"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.pos_y}
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-foreground" for="width"
                    >Ancho</label
                >
                <input
                    id="width"
                    type="number"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.width}
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-foreground" for="height"
                    >Alto</label
                >
                <input
                    id="height"
                    type="number"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.height}
                />
            </div>
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
            >
                Guardar cambios
            </button>
            <Link
                href="/admin/buildings"
                class="inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50"
            >
                Cancelar
            </Link>
        </div>
    </form>
</div>

