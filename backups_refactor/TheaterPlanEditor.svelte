<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Panzoom from '@panzoom/panzoom';
    import { onMount, untrack } from 'svelte';

    export type EditorNode = {
        id: number;
        identifier: string;
        pos_x: number;
        pos_y: number;
        status: string;
        is_occupied?: boolean;
    };

    export type LayoutObject = {
        id: number;
        type: string;
        properties: {
            width: number;
            height: number;
            label: string;
            color: string;
            pos_x: number;
            pos_y: number;
        };
    };

    export type EditorSpace = {
        id: number;
        name: string;
        nodes: EditorNode[];
        layout_objects?: LayoutObject[];
    };

    let { space }: { space: EditorSpace } = $props();

    let nodes = $state<EditorNode[]>([]);
    let layoutObjects = $state<LayoutObject[]>([]);
    let editMode = $state(false);

    // Cambiamos selectedId para que sepa si es un nodo o un objeto
    let selectedItem = $state<{ type: 'node' | 'object'; id: number } | null>(
        null,
    );
    let hasChanges = $state(false);

    let panzoomInstance: any = null;
    let svgGroup: SVGGElement;

    let draggedNode = $state<EditorNode | null>(null);
    let draggedObject = $state<LayoutObject | null>(null);

    $effect(() => {
        untrack(() => {
            if (space?.nodes) {
                nodes = space.nodes.map((n) => ({ ...n }));
                layoutObjects = (space.layout_objects || []).map((o) => ({
                    ...o,
                    properties: { ...o.properties },
                }));
                hasChanges = false;
            }
        });
    });

    onMount(() => {
        panzoomInstance = Panzoom(svgGroup, {
            maxScale: 10,
            minScale: 0.05,
            canvas: true,
            excludeClass: 'seat-group',
        });

        setTimeout(() => {
            const bbox = svgGroup.getBBox();
            const parent = svgGroup.parentElement;
            if (!parent) return;

            const scale = 0.3;
            const x =
                parent.clientWidth / 2 -
                (bbox.width * scale) / 2 -
                bbox.x * scale;
            const y =
                parent.clientHeight / 2 -
                (bbox.height * scale) / 2 -
                bbox.y * scale;

            panzoomInstance.zoom(scale, { animate: false });
            panzoomInstance.pan(x, y, { animate: false });
        }, 150);

        const parent = svgGroup.parentElement;
        parent?.addEventListener('wheel', panzoomInstance.zoomWithWheel);

        return () => panzoomInstance?.destroy();
    });

    // --- NUEVAS FUNCIONES DE DISEÑO ---
    function addSeat() {
        const newNode: EditorNode = {
            id: Date.now() * -1, // ID temporal negativo
            identifier: `NEW-${nodes.length + 1}`,
            pos_x: 0,
            pos_y: 0,
            status: 'active',
        };
        nodes = [...nodes, newNode];
        selectedItem = { type: 'node', id: newNode.id };
        hasChanges = true;
    }

    function addStage() {
        const newObj: LayoutObject = {
            id: Date.now() * -1,
            type: 'stage',
            properties: {
                width: 300,
                height: 80,
                label: 'ESCENARIO',
                color: '#334155',
                pos_x: 0,
                pos_y: -120,
            },
        };
        layoutObjects = [...layoutObjects, newObj];
        selectedItem = { type: 'object', id: newObj.id };
        hasChanges = true;
    }

    function deleteSelected() {
        if (!selectedItem) return;
        if (selectedItem.type === 'node') {
            nodes = nodes.filter((n) => n.id !== selectedItem?.id);
        } else {
            layoutObjects = layoutObjects.filter(
                (o) => o.id !== selectedItem?.id,
            );
        }
        selectedItem = null;
        hasChanges = true;
    }

    function toggleAccessibility() {
        if (selectedItem?.type !== 'node') return;
        const node = nodes.find((n) => n.id === selectedItem?.id);
        if (node) {
            node.identifier = node.identifier.includes('♿')
                ? node.identifier.replace(' ♿', '')
                : node.identifier + ' ♿';
            hasChanges = true;
        }
    }

    // --- MANEJO DE EVENTOS ---
    function handleNodeDown(e: PointerEvent, node: EditorNode) {
        e.stopPropagation();
        selectedItem = { type: 'node', id: node.id };

        if (editMode) {
            (e.currentTarget as Element).setPointerCapture(e.pointerId);
            draggedNode = node;
        }
    }

    function handleObjectDown(e: PointerEvent, obj: LayoutObject) {
        e.stopPropagation();
        selectedItem = { type: 'object', id: obj.id };

        if (editMode) {
            (e.currentTarget as Element).setPointerCapture(e.pointerId);
            draggedObject = obj;
        }
    }

    function handlePointerMove(e: PointerEvent) {
        if (!editMode || !panzoomInstance) return;
        const scale = panzoomInstance.getScale();

        if (draggedNode) {
            draggedNode.pos_x += e.movementX / scale;
            draggedNode.pos_y += e.movementY / scale;
            hasChanges = true;
        } else if (draggedObject) {
            draggedObject.properties.pos_x += e.movementX / scale;
            draggedObject.properties.pos_y += e.movementY / scale;
            hasChanges = true;
        }
    }

    function handlePointerUp(e: PointerEvent) {
        draggedNode = null;
        draggedObject = null;
    }

    function saveLayout() {
        const payloadNodes = nodes.map((n) => ({
            id: n.id,
            pos_x: Math.round(n.pos_x),
            pos_y: Math.round(n.pos_y),
            identifier: n.identifier,
            status: n.status,
        }));

        router.put(
            `/admin/teatro/spaces/${space.id}/layout`,
            { nodes: payloadNodes, layout_objects: layoutObjects },
            {
                preserveState: true,
                onSuccess: () => (hasChanges = false),
            },
        );
    }

    function getSeatColor(node: EditorNode) {
        if (selectedItem?.type === 'node' && selectedItem.id === node.id)
            return '#3b82f6';
        if (node.status === 'maintenance') return '#f59e0b';
        if (node.status === 'blocked') return '#64748b';
        return '#10b981';
    }
</script>

<div class="flex h-[85vh] gap-4 font-sans select-none">
    <div
        class="relative flex-1 rounded-2xl border border-zinc-700 bg-[#0f172a] overflow-hidden shadow-2xl"
    >
        <div class="absolute top-4 left-4 z-20 flex gap-2">
            <button
                class="px-4 py-2 rounded-lg font-bold text-sm transition-colors {editMode
                    ? 'bg-indigo-600 text-white'
                    : 'bg-zinc-800 text-zinc-400'}"
                onclick={() => (editMode = !editMode)}
            >
                {editMode ? '🔓 Modo Edición' : '🔒 Modo Navegación'}
            </button>

            {#if editMode}
                <button
                    class="px-3 py-2 bg-zinc-700 text-white rounded-lg text-sm font-bold hover:bg-zinc-600"
                    onclick={addSeat}>➕ Asiento</button
                >
                <button
                    class="px-3 py-2 bg-zinc-700 text-white rounded-lg text-sm font-bold hover:bg-zinc-600"
                    onclick={addStage}>⬛ Escenario</button
                >
            {/if}

            <button
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold text-sm disabled:opacity-30 hover:bg-emerald-500"
                disabled={!hasChanges}
                onclick={saveLayout}
            >
                Guardar Cambios
            </button>
        </div>

        <svg
            width="100%"
            height="100%"
            style="touch-action: none; pointer-events: all;"
            role="application"
            aria-label="Editor de asientos"
            onpointermove={handlePointerMove}
            onpointerup={handlePointerUp}
        >
            <g bind:this={svgGroup}>
                {#each layoutObjects as obj (obj.id)}
                    <g
                        class="seat-group"
                        role="button"
                        tabindex="0"
                        onpointerdown={(e) => handleObjectDown(e, obj)}
                        style="cursor: {editMode
                            ? 'move'
                            : 'pointer'}; pointer-events: all;"
                    >
                        <rect
                            x={obj.properties.pos_x}
                            y={obj.properties.pos_y}
                            width={obj.properties.width}
                            height={obj.properties.height}
                            rx="8"
                            fill={obj.properties.color}
                            stroke={selectedItem?.type === 'object' &&
                            selectedItem.id === obj.id
                                ? 'white'
                                : 'none'}
                            stroke-width="3"
                        />
                        <text
                            x={obj.properties.pos_x + obj.properties.width / 2}
                            y={obj.properties.pos_y +
                                obj.properties.height / 2 +
                                5}
                            text-anchor="middle"
                            fill="white"
                            class="text-sm font-black pointer-events-none uppercase tracking-widest"
                        >
                            {obj.properties.label}
                        </text>
                    </g>
                {/each}

                {#each nodes as node (node.id)}
                    <g
                        class="seat-group"
                        role="button"
                        tabindex="0"
                        aria-label={`Asiento ${node.identifier}`}
                        onpointerdown={(e) => handleNodeDown(e, node)}
                        style="cursor: {editMode
                            ? 'move'
                            : 'pointer'}; pointer-events: all;"
                    >
                        <rect
                            x={node.pos_x}
                            y={node.pos_y}
                            width="32"
                            height="32"
                            rx="7"
                            fill={getSeatColor(node)}
                            stroke={selectedItem?.type === 'node' &&
                            selectedItem.id === node.id
                                ? 'white'
                                : node.identifier.includes('♿')
                                  ? '#fbbf24'
                                  : 'none'}
                            stroke-width="2"
                        />
                        <text
                            x={node.pos_x + 16}
                            y={node.pos_y + 20}
                            text-anchor="middle"
                            fill="white"
                            class="text-[8px] font-bold pointer-events-none"
                        >
                            {node.identifier}
                        </text>
                    </g>
                {/each}
            </g>
        </svg>
    </div>

    <aside
        class="w-80 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 flex flex-col shadow-sm"
    >
        <h2 class="text-xl font-bold mb-6 text-zinc-900 dark:text-zinc-50">
            Propiedades
        </h2>

        {#if selectedItem}
            {#if selectedItem.type === 'node'}
                {@const sn = nodes.find((n) => n.id === selectedItem?.id)}
                {#if sn}
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-zinc-500 mb-1"
                                >ID Asiento</label
                            >
                            <input
                                bind:value={sn.identifier}
                                oninput={() => (hasChanges = true)}
                                class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-zinc-500 mb-1"
                                >Estado</label
                            >
                            <select
                                bind:value={sn.status}
                                onchange={() => (hasChanges = true)}
                                class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm"
                            >
                                <option value="active">Activo</option>
                                <option value="maintenance"
                                    >Mantenimiento</option
                                >
                                <option value="blocked">Bloqueado</option>
                            </select>
                        </div>
                        <button
                            class="w-full py-2 bg-zinc-100 dark:bg-zinc-800 rounded-xl text-sm font-bold transition-colors"
                            onclick={toggleAccessibility}
                        >
                            {sn.identifier.includes('♿')
                                ? 'Quitar ♿'
                                : 'Marcar como ♿'}
                        </button>
                        <button
                            class="w-full py-2 mt-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold uppercase"
                            onclick={deleteSelected}
                        >
                            🗑️ Eliminar Asiento
                        </button>
                        <div class="text-xs font-mono text-zinc-400 mt-2">
                            Coord: ({Math.round(sn.pos_x)}, {Math.round(
                                sn.pos_y,
                            )})
                        </div>
                    </div>
                {/if}
            {:else if selectedItem.type === 'object'}
                {@const so = layoutObjects.find(
                    (o) => o.id === selectedItem?.id,
                )}
                {#if so}
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-zinc-500 mb-1"
                                >Etiqueta</label
                            >
                            <input
                                bind:value={so.properties.label}
                                oninput={() => (hasChanges = true)}
                                class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="block text-xs font-bold text-zinc-500 mb-1"
                                    >Ancho</label
                                >
                                <input
                                    type="number"
                                    bind:value={so.properties.width}
                                    oninput={() => (hasChanges = true)}
                                    class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-2 text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-zinc-500 mb-1"
                                    >Alto</label
                                >
                                <input
                                    type="number"
                                    bind:value={so.properties.height}
                                    oninput={() => (hasChanges = true)}
                                    class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-2 text-sm"
                                />
                            </div>
                        </div>
                        <button
                            class="w-full py-2 mt-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold uppercase"
                            onclick={deleteSelected}
                        >
                            🗑️ Eliminar Escenario
                        </button>
                    </div>
                {/if}
            {/if}
        {:else}
            <div
                class="flex-1 flex items-center justify-center border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-2xl"
            >
                <p class="text-zinc-400 text-sm italic text-center px-4">
                    Selecciona un elemento para editarlo.
                </p>
            </div>
        {/if}
    </aside>
</div>
