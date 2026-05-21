<script module>
    // Apagamos el layout del admin para esta vista pública
    export const layout = null;
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';
    import { 
        GraduationCap, Music, Theater, Calendar, 
        MapPin, Search, Ticket, ArrowRight, 
        BookOpen, Award, Sparkles, School,
        CheckCircle, Globe, Shield, HelpCircle
    } from 'lucide-svelte';

    // Recibimos los eventos que mandamos desde web.php
    let {
        events = [],
        canRegister = true,
    }: {
        events: any[];
        canRegister: boolean;
    } = $props();

    const auth = $derived(page.props.auth);

    // Estados reactivos para la barra de búsqueda y el filtro de campus
    let searchQuery = $state('');
    let selectedCampus = $state('Todos');

    // Lista de campus para los filtros
    const campusOptions = ['Todos', 'Chetumal', 'Cancún', 'Cozumel', 'Playa del Carmen'];

    // Filtro dinámico reactivo usando $derived
    let filteredEvents = $derived(
        events.filter((event: any) => {
            const name = (event.name || '').toLowerCase();
            const spaceName = (event.space?.name || '').toLowerCase();
            const description = (event.description || '').toLowerCase();
            const query = searchQuery.toLowerCase();
            
            const matchesSearch = name.includes(query) || spaceName.includes(query) || description.includes(query);
            
            if (selectedCampus === 'Todos') return matchesSearch;
            
            // Obtener el nombre del campus desde el espacio -> edificio -> campus
            const campusName = (event.space?.building?.campus?.name || '').toLowerCase();
            
            // Comparación inteligente (si el campus de la base de datos contiene la opción seleccionada)
            const targetCampus = selectedCampus.toLowerCase();
            const matchesCampus = campusName.includes(targetCampus) || 
                                 (targetCampus === 'playa del carmen' && campusName.includes('playa'));
            
            return matchesSearch && matchesCampus;
        })
    );

    // Función para verificar si la imagen del poster es un screenshot genérico o Teams
    function isTeamsOrPlaceholder(url: string | null | undefined) {
        if (!url) return true;
        const low = url.toLowerCase();
        return low.includes('teams') || low.includes('screenshot') || low.includes('not-signed-in') || low.includes('microsoft') || low.includes('default');
    }

    // Retorna el icono ideal según el nombre del evento o el espacio
    function getEventIcon(eventName: string, spaceName: string) {
        const nameLow = (eventName || '').toLowerCase();
        const spaceLow = (spaceName || '').toLowerCase();
        
        if (nameLow.includes('obra') || nameLow.includes('teatro') || nameLow.includes('clonación') || spaceLow.includes('teatro')) {
            return Theater;
        }
        if (nameLow.includes('música') || nameLow.includes('concierto') || nameLow.includes('recital') || nameLow.includes('baile') || nameLow.includes('danza')) {
            return Music;
        }
        if (nameLow.includes('conferencia') || nameLow.includes('foro') || nameLow.includes('clase') || nameLow.includes('académic') || nameLow.includes('examen') || nameLow.includes('taller')) {
            return GraduationCap;
        }
        return BookOpen;
    }

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

<AppHead title="Cartelera de Eventos - UQROO" />

<div
    class="min-h-screen bg-slate-50 font-sans text-slate-900 transition-colors duration-300 dark:bg-[#070b09] dark:text-[#EDEDEC]"
>
    <!-- Header Universitario Premium -->
    <header
        class="sticky top-0 z-50 flex items-center justify-between border-b border-slate-100 bg-white/80 px-6 py-4 shadow-sm backdrop-blur-md dark:border-emerald-950/60 dark:bg-[#0d1611]/90"
    >
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#005E35] to-[#004024] text-white shadow-md border border-[#D49A15]/30">
                <School class="h-5 w-5 text-amber-400" />
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-lg font-black tracking-tight text-[#005E35] dark:text-emerald-400">Sistema de Reservas</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#D49A15] dark:text-amber-400">Cultura UQROO</span>
            </div>
        </div>
        
        <nav class="flex items-center gap-3">
            {#if auth.user}
                <Link
                    href={toUrl(dashboard())}
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#005E35] to-[#004d2b] px-5 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-950/10 hover:shadow-emerald-900/20 hover:brightness-105 transition-all dark:from-emerald-600 dark:to-emerald-500"
                >
                    Dashboard
                    <ArrowRight class="h-4 w-4" />
                </Link>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-[#005E35] hover:bg-slate-100 dark:text-emerald-400 dark:hover:bg-emerald-950/30 transition-all"
                >
                    Iniciar Sesión
                </Link>
                {#if canRegister}
                    <Link
                        href={toUrl(register())}
                        class="rounded-xl border border-[#005E35]/20 bg-white px-4 py-2 text-sm font-semibold text-[#005E35] hover:bg-[#005E35] hover:text-white transition-all dark:border-emerald-500/20 dark:bg-[#0f1d15] dark:text-emerald-300 dark:hover:bg-emerald-500 dark:hover:text-white"
                    >
                        Registrarse
                    </Link>
                {/if}
            {/if}
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8">
        <!-- Hero Section Académico Premium -->
        <section
            class="relative mb-12 overflow-hidden rounded-3xl bg-gradient-to-br from-[#005E35] via-[#092216] to-[#020b06] px-8 py-14 text-center text-white shadow-xl border border-emerald-800/20"
        >
            <!-- Luces y efectos decorativos de fondo -->
            <div class="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-amber-500/10 blur-3xl"></div>
            <div class="absolute -left-20 -bottom-20 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"></div>
            
            <div class="relative z-10">
                <span
                    class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 border border-amber-500/30 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#D49A15] dark:text-amber-400 uppercase"
                >
                    <Sparkles class="h-3.5 w-3.5 text-amber-400 animate-pulse" />
                    Universidad Autónoma del Estado de Quintana Roo
                </span>
                
                <h1
                    class="mx-auto max-w-3xl text-3xl sm:text-5xl font-black leading-tight tracking-tight drop-shadow-sm"
                >
                    Cartelera Universitaria de
                    <span class="bg-gradient-to-r from-amber-400 via-amber-200 to-amber-400 bg-clip-text text-transparent">Eventos y Reservas</span>
                </h1>
                
                <p class="mx-auto mt-5 max-w-2xl text-base text-emerald-100/90 leading-relaxed font-light">
                    Portal institucional para la consulta de aforos y reservas de asientos. Asegura tu lugar en las mejores puestas teatrales, conferencias científicas y conciertos académicos.
                </p>

                <!-- Facility Stats / Metrics -->
                <div class="mx-auto mt-10 grid max-w-4xl grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl bg-white/5 border border-white/10 p-4 backdrop-blur-sm text-center">
                        <span class="block text-2xl font-black text-amber-400">4</span>
                        <span class="text-xs text-emerald-200/80 font-medium">Campus Universitarios</span>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/10 p-4 backdrop-blur-sm text-center">
                        <span class="block text-2xl font-black text-amber-400">12+</span>
                        <span class="text-xs text-emerald-200/80 font-medium">Salas y Auditorios</span>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/10 p-4 backdrop-blur-sm text-center">
                        <span class="block text-2xl font-black text-amber-400">100%</span>
                        <span class="text-xs text-emerald-200/80 font-medium">Acceso Digital</span>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/10 p-4 backdrop-blur-sm text-center">
                        <span class="block text-2xl font-black text-amber-400">Real-Time</span>
                        <span class="text-xs text-emerald-200/80 font-medium">Aforo Sincronizado</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Barra de Búsqueda y Filtros de Campus -->
        <section class="mb-10 rounded-2xl bg-white p-6 shadow-sm border border-slate-100 dark:bg-[#0c130f] dark:border-emerald-950/40">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <!-- Buscador -->
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <Search class="h-5 w-5 text-slate-400 dark:text-emerald-700" />
                    </div>
                    <input
                        type="text"
                        placeholder="Buscar por título, temática o auditorio..."
                        bind:value={searchQuery}
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm font-medium transition-all focus:border-[#D49A15] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D49A15]/10 dark:border-emerald-950/50 dark:bg-[#121b16] dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500/10"
                    />
                </div>

                <!-- Tabs de Campus -->
                <div class="flex flex-wrap gap-1.5 border-b border-slate-100 pb-2 md:border-none md:pb-0">
                    {#each campusOptions as campus}
                        <button
                            onclick={() => selectedCampus = campus}
                            class={`rounded-xl px-4 py-2 text-xs font-bold tracking-wide uppercase transition-all ${
                                selectedCampus === campus
                                    ? 'bg-[#005E35] text-white shadow-md shadow-emerald-900/10 dark:bg-emerald-600 dark:shadow-emerald-500/10'
                                    : 'border border-slate-100 hover:bg-slate-100 text-slate-600 dark:border-emerald-950/30 dark:hover:bg-emerald-950/40 dark:text-slate-400'
                            }`}
                        >
                            {campus}
                        </button>
                    {/each}
                </div>
            </div>
        </section>

        <!-- Cartelera de Eventos Activa -->
        <h2 class="mb-6 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
            <Calendar class="h-5 w-5 text-[#005E35] dark:text-emerald-400" />
            Cartelera Cultural y Académica
        </h2>

        {#if filteredEvents.length === 0}
            <div
                class="rounded-3xl border border-dashed border-slate-200 bg-white p-16 text-center shadow-sm dark:border-emerald-950/40 dark:bg-[#0c130f]"
            >
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-50 text-slate-400 dark:bg-[#121b16] dark:text-emerald-800">
                    <Calendar class="h-7 w-7" />
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No se encontraron eventos</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Intenta modificando los filtros o la palabra de búsqueda.
                </p>
                {#if searchQuery || selectedCampus !== 'Todos'}
                    <button
                        onclick={() => { searchQuery = ''; selectedCampus = 'Todos'; }}
                        class="mt-6 rounded-xl bg-slate-100 px-5 py-2 text-xs font-bold text-[#005E35] hover:bg-slate-200 transition-all dark:bg-emerald-950/60 dark:text-emerald-400 dark:hover:bg-emerald-900/40"
                    >
                        Limpiar Filtros
                    </button>
                {/if}
            </div>
        {:else}
            <!-- Grid de Tarjetas Premium -->
            <div class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                {#each filteredEvents as event}
                    <Link
                        href={`/espacios/${event.space_id}/eventos/${event.id}/reservar`}
                        class="group relative flex flex-col overflow-hidden rounded-3xl bg-white border border-slate-100 shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-xl dark:bg-[#0d1310] dark:border-emerald-950/40"
                    >
                        <!-- Poster o Fallback CSS Geométrico -->
                        <div class="relative h-[340px] w-full overflow-hidden bg-slate-100 dark:bg-[#101a14]">
                            {#if isTeamsOrPlaceholder(event.poster_url)}
                                <!-- Placeholder dinámico UQROO premium sin capturas feas -->
                                <div class="relative flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-[#005E35] via-[#062417] to-[#010905] p-6 text-center border-t-4 border-[#D49A15] group-hover:scale-105 transition-transform duration-500">
                                    <!-- Rejilla geométrica fina de fondo -->
                                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/5 via-transparent to-transparent"></div>
                                    <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff03_1px,transparent_1px),_linear-gradient(to_bottom,#ffffff03_1px,transparent_1px)] bg-[size:24px_24px]"></div>
                                    
                                    <!-- Insignia y Icono Principal -->
                                    <div class="relative z-10 flex h-20 w-20 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/20 text-[#D49A15] shadow-[0_0_20px_rgba(212,154,21,0.15)] mb-4">
                                        <svelte:component this={getEventIcon(event.name, event.space?.name)} class="h-9 w-9 text-amber-400" />
                                    </div>
                                    
                                    <span class="relative z-10 text-[10px] font-black uppercase tracking-widest text-emerald-300/80">
                                        Espacio Cultural UQROO
                                    </span>
                                    <span class="relative z-10 mt-1 text-sm font-bold text-slate-300 max-w-[200px] leading-snug drop-shadow-sm">
                                        {event.space?.name || 'SALA ACADÉMICA'}
                                    </span>
                                </div>
                            {:else}
                                <img
                                    src={event.poster_url}
                                    alt={event.name}
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                            {/if}

                            <!-- Degradado para legibilidad del texto superior -->
                            <div class="absolute inset-0 bg-gradient-to-t from-[#020b06]/95 via-[#020b06]/40 to-transparent"></div>

                            <!-- Texto e información encima del poster -->
                            <div class="absolute bottom-0 left-0 w-full p-5 text-white flex flex-col justify-end">
                                <!-- Badge de Campus & Sala -->
                                <div class="mb-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 border border-emerald-400/30 px-3 py-1 text-[11px] font-bold tracking-wider text-emerald-300 backdrop-blur-md">
                                        <MapPin class="h-3 w-3 text-amber-400" />
                                        {event.space?.building?.campus?.name || 'UQROO'} • {event.space?.name || 'Sala'}
                                    </span>
                                </div>

                                <h3 class="text-xl font-extrabold leading-snug tracking-tight text-white drop-shadow-md group-hover:text-amber-300 transition-colors">
                                    {event.name}
                                </h3>

                                <p class="mt-2 text-xs text-slate-300/90 font-medium flex items-center gap-1.5 drop-shadow-sm">
                                    <Calendar class="h-3.5 w-3.5 text-amber-400" />
                                    {formatLocalDate(event.start_time)}
                                </p>
                            </div>
                        </div>

                        <!-- Footer de la tarjeta con acción -->
                        <div
                            class="flex items-center justify-between border-t border-slate-50 bg-slate-50/50 px-5 py-4 dark:border-emerald-950/30 dark:bg-[#0c1410]/40"
                        >
                            <span class="text-xs font-extrabold uppercase tracking-widest text-[#005E35] dark:text-emerald-400 group-hover:text-[#D49A15] dark:group-hover:text-amber-400 transition-colors">
                                Reservar Asientos
                            </span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-[#005E35] group-hover:bg-[#005E35] group-hover:text-white transition-all dark:bg-emerald-950/60 dark:text-emerald-400 dark:group-hover:bg-emerald-500 dark:group-hover:text-white">
                                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </Link>
                {/each}
            </div>
        {/if}

        <!-- Sección de Infraestructura de la Universidad (Educacional) -->
        <section class="mt-20 border-t border-slate-200/60 pt-16 dark:border-emerald-950/40">
            <div class="text-center mb-12">
                <span class="text-xs font-black uppercase tracking-widest text-[#D49A15] dark:text-amber-400">Nuestros Espacios</span>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-800 dark:text-white">Infraestructura y Recintos de Excelencia</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                    La UQROO cuenta con múltiples auditorios y salas diseñados para albergar actividades académicas, científicas, artísticas y de fomento a la lectura.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-emerald-950/40 dark:bg-[#0c130f]">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-[#D49A15] dark:bg-amber-400/5">
                        <Theater class="h-5 w-5 text-amber-500" />
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Teatro Chetumal Bahía</h4>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Sede insignia de la expresión dancística, festivales cinematográficos y grandes galas teatrales estudiantiles de la zona sur.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-emerald-950/40 dark:bg-[#0c130f]">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/5 dark:text-emerald-400">
                        <GraduationCap class="h-5 w-5 text-[#005E35] dark:text-emerald-400" />
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Auditorio Yuri Knórozov</h4>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Recinto académico solemne ideal para coloquios de investigación internacional, debates magnos y cátedras universitarias.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-emerald-950/40 dark:bg-[#0c130f]">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-[#D49A15] dark:bg-amber-400/5">
                        <BookOpen class="h-5 w-5 text-amber-500" />
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Sala de Rectores</h4>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Espacio de debate reservado para seminarios de titulación, simposios de investigación aplicada y ceremonias protocolares.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-emerald-950/40 dark:bg-[#0c130f]">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/5 dark:text-emerald-400">
                        <Music class="h-5 w-5 text-[#005E35] dark:text-emerald-400" />
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Auditorios Regionales</h4>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Infraestructura cultural adaptada en Cancún, Playa del Carmen y Cozumel para la difusión científica y conciertos musicales.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Universitario -->
    <footer class="mt-24 border-t border-slate-200 bg-white py-10 dark:border-emerald-950/50 dark:bg-[#0a0f0d]">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                © 2026 Universidad Autónoma del Estado de Quintana Roo. Todos los derechos reservados.
            </span>
            <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
                Fomento a la Cultura, las Artes y el Conocimiento Científico • Chetumal • Cancún • Cozumel • Playa del Carmen.
            </p>
        </div>
    </footer>
</div>
