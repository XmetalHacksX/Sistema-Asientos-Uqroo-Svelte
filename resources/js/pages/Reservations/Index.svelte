<script module>
    export const layout = null;
</script>

<script>
    import { router } from '@inertiajs/svelte';
    import Panzoom from '@panzoom/panzoom';

    let { space, event } = $props();

    // Array para guardar los IDs de los asientos seleccionados
    let selectedSeatId = $state(null);
    let selectedSeatName = $state('');

    // --- INGENIERÍA: CÁLCULO DE FILAS (A, B, C...) ---
    const seatW = 32;
    const seatH = 32;
    const paddingLeftForLabels = 40; // Espacio para letras A, B, C a la izquierda

    // --- INGENIERÍA: Funciones para evitar el desfase de zona horaria (UTC-5) ---
    function formatLocalDate(dateStr) {
        if (!dateStr) return 'Por definir';
        // Quitamos la Z final para que JS no reste las horas automáticamente
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    function formatLocalTime(dateStr) {
        if (!dateStr) return '--:--';
        // Quitamos la Z final para forzar la hora exacta de la base de datos
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleTimeString('es-MX', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    // Leemos directo del evento usando nuestras funciones corregidas
    let fechaEvento = $derived(formatLocalDate(event?.start_time));
    let horaInicio = $derived(formatLocalTime(event?.start_time));
    let horaFin = $derived(formatLocalTime(event?.end_time));

    // Calculamos las letras de fila únicas y su posición Y centrada
    const distinctRowLetters = $derived.by(() => {
        if (!space?.nodes) return [];
        // Obtenemos la primera letra del identifier (asumiendo patrón A-1)
        const letters = [
            ...new Set(space.nodes.map((n) => n.identifier.charAt(0))),
        ].sort((a, b) => a.localeCompare(b)); // Ordenamos A, B, C... downwards

        // Para cada letra, encontramos el pos_y mínimo (el inicio de la fila)
        return letters.map((letter) => {
            const rowNodes = space.nodes.filter((n) =>
                n.identifier.startsWith(letter),
            );
            const minY = Math.min(...rowNodes.map((n) => n.pos_y));
            return { letter, y: minY + seatH / 2 + 3 }; // Posición Y centrada para el texto
        });
    });

    // Panzoom
    let panzoomInstance = null;
    let panzoomNode = null;

    // --- INGENIERÍA: CÁLCULO DE LÍMITES ROBUSTO (Incluye Escenarios y Etiquetas) ---
    function getSeatingBounds() {
        const list = space?.nodes;
        if (!list?.length) return null;

        let minX = Infinity,
            minY = Infinity,
            maxX = -Infinity,
            maxY = -Infinity;

        // Asientos
        for (const n of list) {
            minX = Math.min(minX, n.pos_x);
            minY = Math.min(minY, n.pos_y);
            maxX = Math.max(maxX, n.pos_x + seatW);
            maxY = Math.max(maxY, n.pos_y + seatH);
        }

        // Objetos (Escenarios)
        if (space.layout_objects) {
            for (const obj of space.layout_objects) {
                minX = Math.min(minX, obj.properties.pos_x);
                minY = Math.min(minY, obj.properties.pos_y);
                maxX = Math.max(
                    maxX,
                    obj.properties.pos_x + obj.properties.width,
                );
                maxY = Math.max(
                    maxY,
                    obj.properties.pos_y + obj.properties.height,
                );
            }
        }

        // Ajustes para Row Labels (Ya quitamos el espacio extra de arriba porque quitamos el SCREEN indicator)
        minX -= paddingLeftForLabels; // Espacio para etiquetas a la izquierda
        minY -= 20; // Un poco de margen superior nada más

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

        const padding = 0.85; // Factor de ajuste
        const scaleX = (containerW / bbox.width) * padding;
        const scaleY = (containerH / bbox.height) * padding;
        const initialScale = Math.min(scaleX, scaleY);

        // Centrado dinámico
        const x =
            containerW / 2 -
            (bbox.width * initialScale) / 2 -
            bbox.x * initialScale;
        const y =
            containerH / 2 -
            (bbox.height * initialScale) / 2 -
            bbox.y * initialScale;

        panzoomInstance.zoom(initialScale, { animate: false });
        panzoomInstance.pan(x, y, { animate: false });
    }

    // setupPanZoom (Respetando tu lógica original)
    function setupPanZoom(node, initialKey) {
        let wheelTarget = null;
        function teardown() {
            if (panzoomInstance) {
                wheelTarget?.removeEventListener(
                    'wheel',
                    panzoomInstance.zoomWithWheel,
                );
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
            wheelTarget?.addEventListener(
                'wheel',
                panzoomInstance.zoomWithWheel,
            );
        }
        function apply(key) {
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

    // Funciones públicas
    function selectSeat(seat) {
        if (seat.is_occupied || seat.status !== 'active') return;
        selectedSeatId = selectedSeatId === seat.id ? null : seat.id;
        selectedSeatName = selectedSeatId ? seat.identifier : '';
    }

    function confirmarReserva() {
        if (!selectedSeatId) return;
        // Ahora usamos la ruta dinámica para guardar
        router.post('/espacios/reservar', {
            node_id: selectedSeatId,
            event_id: event.id,
        });
    }

    // Reactive bound reference (Svelte 5)
    let seatingBounds = $state(null);
    $effect(() => {
        // Recalculate bounds whenever space changes
        if (space) seatingBounds = getSeatingBounds();
    });
</script>

<div
    class="public-view-wrapper"
    style="font-family: system-ui, sans-serif; background-color: white; color: black; min-height: 100vh; padding: 2rem;"
>
    <div
        class="event-card"
        style="background: white; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2rem; display: flex; gap: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;"
    >
        <div
            style="width: 150px; height: 220px; background: #cbd5e1; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: bold; border: 1px solid #e2e8f0; overflow: hidden; background-size: cover; background-position: center; background-image: url('{event?.poster_url ||
                ''}');"
        >
            {#if !event?.poster_url}
                POSTER
            {/if}
        </div>

        <div
            style="flex: 1; display: flex; flex-direction: column; justify-content: center;"
        >
            <h1
                style="margin: 0 0 0.5rem; font-size: 2.2rem; font-weight: 800; color: #0f172a;"
            >
                {event?.name || 'Evento sin nombre'}
            </h1>

            {#if event?.description}
                <p
                    style="margin: 0 0 2rem; font-size: 15px; color: #475569; max-width: 800px; line-height: 1.5;"
                >
                    {event.description}
                </p>
            {:else}
                <div style="margin-bottom: 2rem;"></div>
            {/if}

            <div
                style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;"
            >
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div
                        style="width: 40px; height: 40px; background: #e0f2fe; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #0369a1; font-size: 1.2rem;"
                    >
                        📅
                    </div>
                    <div>
                        <div
                            style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;"
                        >
                            FECHA
                        </div>
                        <div
                            style="font-size: 15px; font-weight: bold; color: #0f172a; text-transform: capitalize;"
                        >
                            {fechaEvento}
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div
                        style="width: 40px; height: 40px; background: #f0fdf4; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #166534; font-size: 1.2rem;"
                    >
                        🕒
                    </div>
                    <div>
                        <div
                            style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;"
                        >
                            INICIO
                        </div>
                        <div
                            style="font-size: 15px; font-weight: bold; color: #0f172a;"
                        >
                            {horaInicio}
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div
                        style="width: 40px; height: 40px; background: #faf5ff; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #6b21a8; font-size: 1.2rem;"
                    >
                        🏁
                    </div>
                    <div>
                        <div
                            style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;"
                        >
                            FIN
                        </div>
                        <div
                            style="font-size: 15px; font-weight: bold; color: #0f172a;"
                        >
                            {horaFin}
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div
                        style="width: 40px; height: 40px; background: #fef2f2; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #991b1b; font-size: 1.2rem;"
                    >
                        🏢
                    </div>
                    <div>
                        <div
                            style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;"
                        >
                            SALA
                        </div>
                        <div
                            style="font-size: 15px; font-weight: bold; color: #0f172a;"
                        >
                            {space?.name || 'No asignada'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        style="background: white; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"
    >
        <h2
            style="margin: 0 0 2rem; font-size: 1.8rem; font-weight: 700; color: #0f172a;"
        >
            Selecciona tus Asientos
        </h2>

        <div
            style="width: 100%; height: 75vh; background-color: white; border-radius: 1rem; overflow: hidden; touch-action: none; border: 1px solid #e2e8f0;"
        >
            <svg width="100%" height="100%" style="display: block;">
                <g
                    use:setupPanZoom={`${space?.id ?? 0}|${space?.nodes?.length ?? 0}`}
                >
                    <rect
                        x="-5000"
                        y="-5000"
                        width="10000"
                        height="10000"
                        fill="transparent"
                        pointer-events="all"
                    />

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
                                    x={obj.properties.pos_x +
                                        obj.properties.width / 2}
                                    y={obj.properties.pos_y +
                                        obj.properties.height / 2 +
                                        5}
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

                    {#if space && space.nodes}
                        {#each space.nodes as seat}
                            <g
                                onclick={() => selectSeat(seat)}
                                style="cursor: {seat.is_occupied ||
                                seat.status !== 'active'
                                    ? 'not-allowed'
                                    : 'pointer'}; pointer-events: all;"
                            >
                                <rect
                                    x={seat.pos_x}
                                    y={seat.pos_y}
                                    width="32"
                                    height="32"
                                    rx="7"
                                    fill={seat.is_occupied ||
                                    seat.status !== 'active'
                                        ? '#ef4444' // Ocupado o Bloqueado
                                        : selectedSeatId === seat.id
                                          ? '#3b82f6' // Selección del usuario
                                          : '#10b981'}
                                    // Libre
                                    stroke={selectedSeatId === seat.id
                                        ? 'white'
                                        : 'none'}
                                    stroke-width="2"
                                    style="transition: fill 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: {selectedSeatId ===
                                    seat.id
                                        ? '0 0 10px rgba(59,130,246,0.5)'
                                        : 'none'};"
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

        <div
            style="margin-top: 1.5rem; padding: 1rem 0 0; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;"
        >
            <div style="display: flex; gap: 2rem; font-size: 13px;">
                <span
                    style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #475569;"
                >
                    <div
                        style="width: 14px; height: 14px; background: #10b981; border-radius: 4px;"
                    ></div>
                    Disponible
                </span>
                <span
                    style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #475569;"
                >
                    <div
                        style="width: 14px; height: 14px; background: #ef4444; border-radius: 4px;"
                    ></div>
                    Ocupado
                </span>
                <span
                    style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #475569;"
                >
                    <div
                        style="width: 14px; height: 14px; background: #3b82f6; border-radius: 4px;"
                    ></div>
                    Tu Selección
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                {#if selectedSeatId}
                    <div
                        style="display: flex; align-items: center; gap: 1rem; background: #f0fdf4; padding: 8px 16px; border-radius: 12px; border: 1px solid #bbf7d0;"
                    >
                        <p
                            style="margin: 0; font-weight: bold; color: #166534; font-size: 15px;"
                        >
                            Asiento: {selectedSeatName}
                        </p>
                        <button
                            onclick={confirmarReserva}
                            style="background-color: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; transition: background 0.2s;"
                        >
                            Confirmar Reserva
                        </button>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

<style>
    button:active {
        transform: scale(0.96);
    }
    button:hover:not(:disabled) {
        background-color: #059669; /* emerald-600 */
    }
    g[onclick='selectSeat'] rect {
        pointer-events: all;
    }
</style>
