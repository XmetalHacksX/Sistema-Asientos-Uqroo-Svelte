<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Panzoom from '@panzoom/panzoom';
    import { onMount, untrack } from 'svelte';
    import { getSeatColor, calculateZoomPosition } from './TheaterPlanEditor/editorUtils';
    import type { EditorNode, LayoutObject, EditorSpace } from './TheaterPlanEditor/editorUtils';
    import EditorSidebar from './TheaterPlanEditor/EditorSidebar.svelte';

    let { space }: { space: EditorSpace } = $props();

    let nodes = $state<EditorNode[]>([]);
    let layoutObjects = $state<LayoutObject[]>([]);
    let editMode = $state(false);

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
            const pos = calculateZoomPosition(parent.clientWidth, parent.clientHeight, bbox, scale);

            panzoomInstance.zoom(pos.scale, { animate: false });
            panzoomInstance.pan(pos.x, pos.y, { animate: false });
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
                            fill={getSeatColor(node, selectedItem)}
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

    <!-- Properties panel componentized -->
    <EditorSidebar
        bind:selectedItem={selectedItem}
        bind:nodes={nodes}
        bind:layoutObjects={layoutObjects}
        bind:hasChanges={hasChanges}
    />
</div>
