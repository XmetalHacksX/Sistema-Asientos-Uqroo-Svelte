<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gestión de Eventos', href: '/admin/events' },
            { title: 'Taquilla / Protocolo', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Panzoom from '@panzoom/panzoom';
    import TaquillaCheckout from './components/TaquillaCheckout.svelte';

    let { space, event, seats, users } = $props();

    let selectedSeat = $state(null);
    let selectedUserId = $state('');

    // --- PANZOOM AND SVG SEAT LOGIC ---
    const seatW = 32;
    const seatH = 32;
    const paddingLeftForLabels = 40;

    const distinctRowLetters = $derived.by(() => {
        if (!seats) return [];
        const letters = [
            ...new Set(seats.map((n: any) => n.identifier.charAt(0))),
        ].sort((a: any, b: any) => a.localeCompare(b));
        return letters.map((letter) => {
            const rowNodes = seats.filter((n: any) =>
                n.identifier.startsWith(letter),
            );
            const minY = Math.min(...rowNodes.map((n: any) => n.pos_y));
            return { letter, y: minY + seatH / 2 + 3 };
        });
    });

    let panzoomInstance: any = null;
    let panzoomNode: any = null;

    function getSeatingBounds() {
        const list = seats;
        if (!list?.length) return null;

        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

        for (const n of list) {
            minX = Math.min(minX, n.pos_x);
            minY = Math.min(minY, n.pos_y);
            maxX = Math.max(maxX, n.pos_x + seatW);
            maxY = Math.max(maxY, n.pos_y + seatH);
        }

        if (space.layout_objects) {
            for (const obj of space.layout_objects) {
                minX = Math.min(minX, obj.properties.pos_x);
                minY = Math.min(minY, obj.properties.pos_y);
                maxX = Math.max(maxX, obj.properties.pos_x + obj.properties.width);
                maxY = Math.max(maxY, obj.properties.pos_y + obj.properties.height);
            }
        }

        minX -= paddingLeftForLabels;
        minY -= 20;

        if (!Number.isFinite(minX) || maxX <= minX || maxY <= minY) return null;
        return { x: minX, y: minY, width: maxX - minX, height: maxY - minY };
    }

    function centerAndFit() {
        if (!panzoomInstance || !panzoomNode) return;
        const bbox = getSeatingBounds();
        if (!bbox || bbox.width <= 0 || bbox.height <= 0) return;

        const parent = panzoomNode.parentElement;
        if (!parent) return;

        const containerW = parent.clientWidth || 1;
        const containerH = parent.clientHeight || 1;

        const padding = 0.85;
        const scaleX = (containerW / bbox.width) * padding;
        const scaleY = (containerH / bbox.height) * padding;
        const initialScale = Math.min(scaleX, scaleY);

        const x = containerW / 2 - (bbox.width * initialScale) / 2 - bbox.x * initialScale;
        const y = containerH / 2 - (bbox.height * initialScale) / 2 - bbox.y * initialScale;

        panzoomInstance.zoom(initialScale, { animate: false });
        panzoomInstance.pan(x, y, { animate: false });
    }

    function setupPanZoom(node: any, initialKey: any) {
        let wheelTarget: any = null;
        function teardown() {
            if (panzoomInstance) {
                wheelTarget?.removeEventListener('wheel', panzoomInstance.zoomWithWheel);
                panzoomInstance.destroy();
                panzoomInstance = null;
                panzoomNode = null;
            }
            wheelTarget = null;
        }
        function ensure() {
            if (panzoomInstance) return;
            panzoomNode = node;
            panzoomInstance = Panzoom(node, { maxScale: 5, minScale: 0.05 });
            wheelTarget = node.parentElement;
            wheelTarget?.addEventListener('wheel', panzoomInstance.zoomWithWheel);
        }
        function apply(key: any) {
            const count = Number(String(key).split('|')[1] ?? '0');
            if (count < 1) {
                teardown();
                return;
            }
            ensure();
            requestAnimationFrame(() => centerAndFit());
        }
        apply(initialKey);
        return { update: apply, destroy: teardown };
    }

    let seatingBounds = $state<any>(null);
    $effect(() => {
        if (space) seatingBounds = getSeatingBounds();
    });

    function selectSeat(seat: any) {
        selectedSeat = seat;
        selectedUserId = seat.user_id ? String(seat.user_id) : '';
    }

    function handleSuccessUpdate(seatId: number) {
        if (seats) {
            const freshSeat = seats.find((s: any) => s.id === seatId);
            if (freshSeat) {
                selectedSeat = freshSeat;
                selectedUserId = freshSeat.user_id ? String(freshSeat.user_id) : '';
            }
        }
    }

    function getSeatColor(seat: any) {
        if (selectedSeat && (selectedSeat as any).id === seat.id) return '#3b82f6'; // Azul selección
        if (seat.status === 'disponible') return '#10b981'; // Verde
        if (seat.status === 'bloqueado') return '#f97316'; // Naranja/Bloqueado
        if (seat.status === 'reservado') return '#ef4444'; // Rojo/Ocupado
        return '#cbd5e1'; // Gris opaco
    }
</script>

<AppHead title="Taquilla - {event?.name}" />

<div class="px-6 py-4 flex gap-6 h-[calc(100vh-80px)] min-h-[600px]">
    <!-- COLUMNA IZQUIERDA: MAPA SVG (70%) -->
    <div class="w-2/3 bg-white border border-sidebar-border/70 rounded-xl shadow-sm overflow-hidden flex flex-col relative">
        <div class="p-4 border-b border-sidebar-border bg-muted/20">
            <h2 class="text-lg font-bold text-foreground">Taquilla: {event?.name}</h2>
            <p class="text-sm text-muted-foreground">Da clic en cualquier asiento para gestionarlo.</p>
        </div>
        
        <div class="flex-1 w-full bg-slate-50 relative overflow-hidden touch-none p-4">
            <svg width="100%" height="100%" style="display: block;">
                <g use:setupPanZoom={`${event?.id ?? 0}|${seats?.length ?? 0}`}>
                    <rect x="-5000" y="-5000" width="10000" height="10000" fill="transparent" pointer-events="all" />

                    {#if space && space.layout_objects}
                        {#each space.layout_objects as obj}
                            <g>
                                <rect
                                    x={obj.properties.pos_x}
                                    y={obj.properties.pos_y}
                                    width={obj.properties.width}
                                    height={obj.properties.height}
                                    fill={obj.properties.color}
                                    rx="8"
                                    opacity="0.6"
                                />
                                <text
                                    x={obj.properties.pos_x + obj.properties.width / 2}
                                    y={obj.properties.pos_y + obj.properties.height / 2 + 5}
                                    text-anchor="middle"
                                    fill="white"
                                    style="font-family: system-ui, sans-serif; font-size: 14px; font-weight: 800; pointer-events: none; text-transform: uppercase; letter-spacing: 0.1em;"
                                >
                                    {obj.properties.label}
                                </text>
                            </g>
                        {/each}
                    {/if}

                    {#each distinctRowLetters as row}
                        <text
                            x={seatingBounds ? seatingBounds.x + 20 : 0}
                            y={row.y}
                            text-anchor="middle"
                            fill="#64748b"
                            style="font-family: system-ui, sans-serif; font-size: 14px; font-weight: 800; pointer-events: none;"
                        >
                            {row.letter}
                        </text>
                    {/each}

                    {#if seats}
                        {#each seats as seat}
                            <g
                                onclick={() => selectSeat(seat)}
                                style="cursor: pointer; pointer-events: all;"
                            >
                                <rect
                                    x={seat.pos_x}
                                    y={seat.pos_y}
                                    width="32"
                                    height="32"
                                    rx="7"
                                    fill={getSeatColor(seat)}
                                    stroke={selectedSeat && (selectedSeat as any).id === seat.id ? 'white' : 'none'}
                                    stroke-width="2"
                                    style="transition: fill 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: {selectedSeat && (selectedSeat as any).id === seat.id ? '0 0 10px rgba(59,130,246,0.5)' : 'none'};"
                                />
                                <text
                                    x={seat.pos_x + 16}
                                    y={seat.pos_y + 20}
                                    text-anchor="middle"
                                    fill="white"
                                    style="font-family: system-ui, sans-serif; font-size: 9px; font-weight: 800; pointer-events: none;"
                                >
                                    {seat.identifier}
                                </text>
                            </g>
                        {/each}
                    {/if}
                </g>
            </svg>
        </div>

        <!-- LEYENDA BASICA -->
        <div class="h-12 border-t border-sidebar-border bg-white flex items-center justify-center gap-6 text-xs font-medium text-slate-600">
            <div class="flex items-center gap-2"><div class="w-3.5 h-3.5 bg-emerald-500 rounded-sm"></div> Disponible</div>
            <div class="flex items-center gap-2"><div class="w-3.5 h-3.5 bg-red-500 rounded-sm"></div> Reservado</div>
            <div class="flex items-center gap-2"><div class="w-3.5 h-3.5 bg-orange-500 rounded-sm"></div> Protocolo</div>
            <div class="flex items-center gap-2"><div class="w-3.5 h-3.5 bg-blue-500 rounded-sm border border-blue-600"></div> Tu Selección</div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: SIDEBAR DE GESTIÓN (30%) componentizada -->
    <div class="w-1/3 flex flex-col gap-4">
        <TaquillaCheckout
            bind:selectedSeat={selectedSeat}
            bind:selectedUserId={selectedUserId}
            {users}
            {event}
            onSuccessUpdate={handleSuccessUpdate}
        />
    </div>
</div>

<style>
    g text {
        user-select: none;
    }
</style>
