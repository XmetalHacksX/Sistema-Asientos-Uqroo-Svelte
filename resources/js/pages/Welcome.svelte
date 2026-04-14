<script module>
    // Apagamos el layout del admin para esta vista pública
    export const layout = null;
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';

    // Recibimos los eventos que mandamos desde web.php
    let {
        events = [],
        canRegister = true,
    }: {
        events: any[];
        canRegister: boolean;
    } = $props();

    const auth = $derived(page.props.auth);

    // Función para formatear la fecha del carrusel
    function formatLocalDate(dateStr: string) {
        if (!dateStr) return '';
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }
</script>

<AppHead title="Cartelera - Inicio" />

<div
    class="min-h-screen bg-slate-50 font-sans text-slate-900 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
>
    <header
        class="flex items-center justify-between bg-white px-8 py-4 shadow-sm dark:bg-[#161615] dark:border-b dark:border-[#3E3E3A]"
    >
        <div class="text-2xl font-black text-slate-800 dark:text-white">
            🎭 Sistema de <span class="text-emerald-600">Reservas</span>
        </div>
        <nav class="flex items-center gap-4">
            {#if auth.user}
                <Link
                    href={toUrl(dashboard())}
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                >
                    Log in
                </Link>
                {#if canRegister}
                    <Link
                        href={toUrl(register())}
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                {/if}
            {/if}
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-8 py-12">
        <h1
            class="mb-2 text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white"
        >
            Cartelera de Eventos
        </h1>
        <p class="mb-10 text-lg text-slate-500 dark:text-slate-400">
            Descubre los próximos estrenos y reserva tus asientos.
        </p>

        {#if events.length === 0}
            <div
                class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-800/50"
            >
                Aún no hay eventos programados.
            </div>
        {:else}
            <div
                class="flex snap-x snap-mandatory gap-6 overflow-x-auto pb-8"
                style="scrollbar-width: thin;"
            >
                {#each events as event}
                    <Link
                        href={`/espacios/${event.space_id}/eventos/${event.id}/reservar`}
                        class="group flex w-[300px] shrink-0 snap-start flex-col overflow-hidden rounded-2xl bg-white shadow-md transition-all hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-[#161615] dark:border dark:border-[#3E3E3A]"
                    >
                        <div
                            class="relative h-[400px] w-full bg-slate-200 dark:bg-slate-800"
                        >
                            {#if event.poster_url}
                                <img
                                    src={event.poster_url}
                                    alt={event.name}
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                            {:else}
                                <div
                                    class="flex h-full w-full items-center justify-center font-bold tracking-widest text-slate-400 dark:text-slate-600"
                                >
                                    POSTER
                                </div>
                            {/if}
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent opacity-90"
                            ></div>

                            <div
                                class="absolute bottom-0 left-0 w-full p-5 text-white"
                            >
                                <span
                                    class="mb-2 inline-block rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold tracking-wider text-white shadow-sm"
                                >
                                    {event.space?.name || 'SALA'}
                                </span>
                                <h3
                                    class="text-2xl font-bold leading-tight drop-shadow-md"
                                >
                                    {event.name}
                                </h3>
                                <p
                                    class="mt-1 text-sm text-slate-200 drop-shadow-md"
                                >
                                    📅 {formatLocalDate(event.start_time)}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between bg-white px-5 py-4 dark:bg-[#161615]"
                        >
                            <span class="text-sm font-semibold text-emerald-600"
                                >Comprar Boletos</span
                            >
                            <svg
                                class="h-5 w-5 text-emerald-600 transition-transform group-hover:translate-x-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                ><path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"
                                ></path></svg
                            >
                        </div>
                    </Link>
                {/each}
            </div>
        {/if}
    </main>
</div>
