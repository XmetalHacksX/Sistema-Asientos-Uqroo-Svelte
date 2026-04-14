<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Eventos', href: '/admin/events' },
            { title: 'Editar', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    // IMPORTANTE: Importamos "page" para poder leer los errores de validación de Laravel
    import { Link, router, page } from '@inertiajs/svelte';

    type Space = { id: number; name: string };

    let { event, spaces } = $props();

    function toDatetimeLocal(value) {
        if (!value) return '';
        const normalized = String(value)
            .trim()
            .replace(' ', 'T')
            .replace('Z', '');
        return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
    }

    let data = $state({
        name: event.name ?? '',
        description: event.description ?? '',
        start_time: toDatetimeLocal(event.start_time),
        end_time: toDatetimeLocal(event.end_time),
        space_id: (event.space_id ?? '') as string | number,
        poster: null as File | null, // Aquí guardaremos el archivo físico
    });

    function submit() {
        // Truco de ingeniería para mandar archivos por PUT en Interia
        router.post(
            `/admin/events/${event.id}`,
            {
                _method: 'put',
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

<AppHead title="Editar Evento" />

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-foreground">Editar Evento</h1>
            <p class="text-sm text-muted-foreground">
                Actualiza el evento y sube un póster.
            </p>
        </div>
        <Link
            href="/admin/events"
            class="rounded-lg border border-sidebar-border bg-background px-3 py-2 text-sm hover:bg-muted/50"
            >Volver</Link
        >
    </div>

    {#if page.props.errors && Object.keys(page.props.errors).length > 0}
        <div
            class="mb-4 max-w-2xl rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600 shadow-sm"
        >
            <strong class="font-bold"
                >¡Atención! Hubo un problema al guardar:</strong
            >
            <ul class="mt-2 list-inside list-disc">
                {#each Object.values(page.props.errors) as error}
                    <li>{error}</li>
                {/each}
            </ul>
        </div>
    {/if}

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
                    for="end_time">Fin</label
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
                    for="poster">Subir Póster (Imagen)</label
                >
                <input
                    id="poster"
                    type="file"
                    accept="image/*"
                    class="mt-1 w-full rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                    onchange={(e) => (data.poster = e.target.files[0])}
                />
                {#if event.poster_url}
                    <p class="text-xs text-muted-foreground mt-2">
                        Ya tiene un póster. Sube otro para reemplazarlo.
                    </p>
                {/if}
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
                disabled={data.space_id === ''}>Guardar cambios</button
            >
            <Link
                href="/admin/events"
                class="rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm hover:bg-muted/50"
                >Cancelar</Link
            >
        </div>
    </form>
</div>
