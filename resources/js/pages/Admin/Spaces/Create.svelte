<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Salas/Teatros', href: '/admin/spaces' },
            { title: 'Crear', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';
    import { slide } from 'svelte/transition';

    type Building = {
        id: number;
        name: string;
    };

    type TemplateSpace = {
        id: number;
        name: string;
    };

    let { buildings, templates = [] }: { buildings: Building[]; templates: TemplateSpace[] } = $props();

    let data = $state({
        building_id: '' as string | number,
        name: '',
        template_space_id: '' as string | number,
        rows: 10,
        cols: 10,
        is_template: false,
    });

    function submit() {
        router.post('/admin/spaces', {
            building_id: Number(data.building_id),
            name: data.name,
            template_space_id: data.template_space_id ? Number(data.template_space_id) : null,
            rows: data.template_space_id ? null : Number(data.rows),
            cols: data.template_space_id ? null : Number(data.cols),
            is_template: data.is_template,
        });
    }
</script>

<AppHead title="Crear Sala / Teatro" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Crear Sala / Teatro</h1>
            <p class="text-sm text-muted-foreground">
                Define una plantilla para clonar o especifica filas y asientos para una retícula base.
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
                for="building_id"
                >Edificio</label
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

        <div>
            <label
                class="block text-sm font-medium text-foreground"
                for="template_space_id"
                >Clonar desde Plantilla (Opcional)</label
            >
            <select
                id="template_space_id"
                class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                bind:value={data.template_space_id}
            >
                <option value="">Generar sala vacía (desde cero)</option>
                {#each templates as t (t.id)}
                    <option value={t.id}>{t.name}</option>
                {/each}
            </select>
        </div>

        {#if !data.template_space_id}
            <div class="grid gap-4 md:grid-cols-2" transition:slide={{ duration: 200 }}>
                <div>
                    <label class="block text-sm font-medium text-foreground" for="rows"
                        >Filas</label
                    >
                    <input
                        id="rows"
                        type="number"
                        min="1"
                        class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                        bind:value={data.rows}
                        required={!data.template_space_id}
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground" for="cols"
                        >Asientos por fila</label
                    >
                    <input
                        id="cols"
                        type="number"
                        min="1"
                        class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                        bind:value={data.cols}
                        required={!data.template_space_id}
                    />
                </div>
            </div>
        {/if}

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
                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50"
                disabled={data.building_id === '' || data.name.trim() === ''}
            >
                {data.template_space_id ? 'Clonar y Crear Sala' : 'Crear y Generar Asientos'}
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
