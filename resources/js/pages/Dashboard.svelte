<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
</script>

<AppHead title="Dashboard" />

<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
    <div
        class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-muted/30 p-4 dark:border-sidebar-border md:flex-row md:items-center md:justify-between"
    >
        <div>
            <h2 class="text-base font-semibold text-foreground">
                Sistema de Teatro y Reservas
            </h2>
            <p class="text-sm text-muted-foreground">
                Administra tus campus, edificios, salas y eventos desde este
                panel central.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Link
                href="/admin/spaces"
                class="inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-background px-4 py-2 text-sm font-medium hover:bg-muted/50"
            >
                Diseñar Planos de Asientos
            </Link>
        </div>
    </div>

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
            class="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Gestión de Campus
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea y administra campus para organizar edificios.
                    </p>
                </div>
                <Link
                    href="/admin/campuses"
                    class="inline-flex w-fit items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                >
                    Ir a Campus
                </Link>
            </div>
        </div>
        <div
            class="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Gestión de Edificios
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Administra edificios y asígnalos a un campus.
                    </p>
                </div>
                <Link
                    href="/admin/buildings"
                    class="inline-flex w-fit items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                >
                    Ir a Edificios
                </Link>
            </div>
        </div>
        <div
            class="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Gestión de Eventos
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea eventos y selecciona el espacio del teatro.
                    </p>
                </div>
                <Link
                    href="/admin/events"
                    class="inline-flex w-fit items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                >
                    Ir a Eventos
                </Link>
            </div>
        </div>
    </div>
    <div
        class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
    >
        <div class="h-full rounded-xl bg-background p-5">
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Gestión de Espacios / Salas
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea las salas para los edificios y genera la cuadrícula
                        de asientos automáticamente.
                    </p>
                </div>
                <Link
                    href="/admin/spaces"
                    class="inline-flex w-fit items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                >
                    Ir a Espacios
                </Link>
            </div>
        </div>
    </div>
</div>
