<script lang="ts">
    import type { EditorNode, LayoutObject } from './editorUtils';

    // Props using Svelte 5 runes with bindable parameters
    let {
        selectedItem = $bindable(),
        nodes = $bindable(),
        layoutObjects = $bindable(),
        hasChanges = $bindable()
    }: {
        selectedItem: { type: 'node' | 'object'; id: number } | null;
        nodes: EditorNode[];
        layoutObjects: LayoutObject[];
        hasChanges: boolean;
    } = $props();

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
</script>

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
                            class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm text-zinc-900 dark:text-zinc-50"
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
                            class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm text-zinc-900 dark:text-zinc-50"
                        >
                            <option value="active">Activo</option>
                            <option value="maintenance">Mantenimiento</option>
                            <option value="blocked">Bloqueado</option>
                        </select>
                    </div>
                    <button
                        class="w-full py-2 bg-zinc-100 dark:bg-zinc-800 rounded-xl text-sm font-bold transition-colors text-zinc-900 dark:text-zinc-50 hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        onclick={toggleAccessibility}
                    >
                        {sn.identifier.includes('♿')
                            ? 'Quitar ♿'
                            : 'Marcar como ♿'}
                    </button>
                    <button
                        class="w-full py-2 mt-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold uppercase hover:bg-red-100 dark:hover:bg-red-900/40"
                        onclick={deleteSelected}
                    >
                        🗑️ Eliminar Asiento
                    </button>
                    <div class="text-xs font-mono text-zinc-400 mt-2">
                        Coord: ({Math.round(sn.pos_x)}, {Math.round(sn.pos_y)})
                    </div>
                </div>
            {/if}
        {:else if selectedItem.type === 'object'}
            {@const so = layoutObjects.find((o) => o.id === selectedItem?.id)}
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
                            class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm text-zinc-900 dark:text-zinc-50"
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
                                class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-2 text-sm text-zinc-900 dark:text-zinc-50"
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
                                class="w-full bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl p-2 text-sm text-zinc-900 dark:text-zinc-50"
                            />
                        </div>
                    </div>
                    <button
                        class="w-full py-2 mt-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold uppercase hover:bg-red-100 dark:hover:bg-red-900/40"
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
