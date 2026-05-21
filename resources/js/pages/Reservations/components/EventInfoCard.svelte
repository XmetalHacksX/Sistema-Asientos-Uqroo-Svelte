<script lang="ts">
    import { Theater, GraduationCap, Music, BookOpen, Calendar, Clock, MapPin, Building2 } from 'lucide-svelte';

    interface EventProps {
        name: string;
        description?: string;
        poster_url?: string;
        start_time: string;
        end_time: string;
    }

    interface SpaceProps {
        name: string;
    }

    let { 
        event, 
        space,
        fechaEvento,
        horaInicio,
        horaFin
    } = $props<{
        event: EventProps;
        space: SpaceProps;
        fechaEvento: string;
        horaInicio: string;
        horaFin: string;
    }>();

    // Verifica si la imagen es un screenshot o placeholder genérico
    function isTeamsOrPlaceholder(url: string | null | undefined) {
        if (!url) return true;
        const low = url.toLowerCase();
        return low.includes('teams') || low.includes('screenshot') || low.includes('not-signed-in') || low.includes('microsoft') || low.includes('default');
    }

    // Retorna el icono ideal según el nombre del evento o el espacio
    function getEventIcon(eventName: string, spaceName: string) {
        const nameLow = (eventName || '').toLowerCase();
        const spaceLow = (spaceName || '').toLowerCase();
        if (nameLow.includes('obra') || nameLow.includes('teatro') || nameLow.includes('clonación') || spaceLow.includes('teatro')) return Theater;
        if (nameLow.includes('música') || nameLow.includes('concierto') || nameLow.includes('recital') || nameLow.includes('baile') || nameLow.includes('danza')) return Music;
        if (nameLow.includes('conferencia') || nameLow.includes('foro') || nameLow.includes('clase') || nameLow.includes('académic') || nameLow.includes('taller')) return GraduationCap;
        return BookOpen;
    }

    const showFallback = $derived(isTeamsOrPlaceholder(event?.poster_url));
    const EventIcon = $derived(getEventIcon(event?.name || '', space?.name || ''));
</script>

<div class="mb-6 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm dark:border-emerald-950/40 dark:bg-[#0d1310]">
    <!-- Top accent strip -->
    <div class="h-1.5 w-full bg-gradient-to-r from-[#005E35] via-[#D49A15] to-[#005E35]"></div>

    <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start">
        <!-- Poster / Fallback Cover -->
        <div class="relative h-52 w-full flex-shrink-0 overflow-hidden rounded-2xl sm:h-44 sm:w-36">
            {#if showFallback}
                <!-- Premium UQROO fallback — identical to Welcome page style -->
                <div class="relative flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-[#005E35] via-[#062417] to-[#010905] p-4 text-center border-t-4 border-[#D49A15]">
                    <!-- Grid mesh background -->
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/5 via-transparent to-transparent"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff03_1px,transparent_1px),_linear-gradient(to_bottom,#ffffff03_1px,transparent_1px)] bg-[size:20px_20px]"></div>
                    <!-- Icon -->
                    <div class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/20 mb-2">
                        <svelte:component this={EventIcon} class="h-7 w-7 text-amber-400" />
                    </div>
                    <span class="relative z-10 text-[9px] font-black uppercase tracking-widest text-emerald-300/80">Espacio UQROO</span>
                </div>
            {:else}
                <img
                    src={event?.poster_url}
                    alt={event?.name}
                    class="h-full w-full object-cover"
                />
            {/if}
        </div>

        <!-- Event Info -->
        <div class="flex flex-1 flex-col justify-between">
            <!-- Title & description -->
            <div>
                <div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-0.5 text-[10px] font-bold uppercase tracking-widest text-[#005E35] dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400">
                    Evento Académico · UQROO
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    {event?.name || 'Evento sin nombre'}
                </h1>
                {#if event?.description}
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">
                        {event.description}
                    </p>
                {/if}
            </div>

            <!-- Meta details grid -->
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <!-- Fecha -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 dark:bg-emerald-500/10">
                        <Calendar class="h-4.5 w-4.5 text-[#005E35] dark:text-emerald-400" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">FECHA</div>
                        <div class="text-sm font-bold capitalize text-slate-800 dark:text-white leading-tight">{fechaEvento}</div>
                    </div>
                </div>

                <!-- Inicio -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 dark:bg-emerald-500/10">
                        <Clock class="h-4.5 w-4.5 text-[#005E35] dark:text-emerald-400" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">INICIO</div>
                        <div class="text-sm font-bold text-slate-800 dark:text-white leading-tight">{horaInicio}</div>
                    </div>
                </div>

                <!-- Fin -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 dark:bg-amber-500/10">
                        <Clock class="h-4.5 w-4.5 text-[#D49A15] dark:text-amber-400" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">FIN</div>
                        <div class="text-sm font-bold text-slate-800 dark:text-white leading-tight">{horaFin}</div>
                    </div>
                </div>

                <!-- Sala -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 dark:bg-amber-500/10">
                        <Building2 class="h-4.5 w-4.5 text-[#D49A15] dark:text-amber-400" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">SALA</div>
                        <div class="text-sm font-bold text-slate-800 dark:text-white leading-tight">{space?.name || 'No asignada'}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
