<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Eventos', href: '/admin/events' },
            { title: 'Crear', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';

    type Space = {
        id: number;
        name: string;
    };

    let { spaces }: { spaces: Space[] } = $props();

    let data = $state({
        name: '',
        description: '',
        start_time: '',
        end_time: '',
        space_id: '' as string | number,
        poster: null as File | null, // Aquí guardaremos el archivo físico
    });

    function submit() {
        // En Interia, para mandar archivos hay que usar forceFormData: true
        router.post(
            '/admin/events',
            {
                name: data.name,
                description: data.description,
                start_time: data.start_time,
                end_time: data.end_time || null,
                space_id: Number(data.space_id),
                poster: data.poster,
            },
            {
                forceFormData: true, // Obligamos a Inertia a enviarlo como archivo
            },
        );
    }
</script>

<AppHead title="Crear Evento" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Crear Evento</h1>
            <p class="text-sm text-muted-foreground">
                Llena los datos y sube un póster para tu evento.
            </p>
        </div>
        <Link
            href="/admin/events"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50"
            >Volver</Link
        >
    </div>

    <form
        class="max-w-2xl space-y-4 rounded-xl border border-sidebar-border/70 bg-background p-5"
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

        <div>
            <label
                class="block text-sm font-medium text-foreground"
                for="description">Descripción</label
            >
            <textarea
                id="description"
                rows="3"
                class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary resize-none"
                bind:value={data.description}
            ></textarea>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label
                    class="block text-sm font-medium text-foreground"
                    for="start_time">Inicio</label
                >
                <input
                    id="start_time"
                    type="datetime-local"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.start_time}
                    required
                />
            </div>
            <div>
                <label
                    class="block text-sm font-medium text-foreground"
                    for="end_time">Fin (Opcional)</label
                >
                <input
                    id="end_time"
                    type="datetime-local"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.end_time}
                />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label
                    class="block text-sm font-medium text-foreground"
                    for="poster">Póster (Imagen)</label
                >
                <input
                    id="poster"
                    type="file"
                    accept="image/*"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                    onchange={(e) => (data.poster = e.target.files[0])}
                />
            </div>

            <div>
                <label
                    class="block text-sm font-medium text-foreground"
                    for="space_id">Espacio Asignado</label
                >
                <select
                    id="space_id"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                    bind:value={data.space_id}
                    required
                >
                    <option value="" disabled>Selecciona un espacio</option>
                    {#each spaces as s (s.id)}
                        <option value={s.id}>{s.name}</option>
                    {/each}
                </select>
            </div>
        </div>

        <div class="flex gap-2 pt-4">
            <button
                type="submit"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                disabled={data.space_id === ''}>Crear Evento</button
            >
            <Link
                href="/admin/events"
                class="rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50"
                >Cancelar</Link
            >
        </div>
    </form>
</div>
