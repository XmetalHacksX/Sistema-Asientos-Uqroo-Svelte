<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Salas/Teatros', href: '/admin/spaces' },
            { title: 'Diseñador de Plano', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import TheaterPlanEditor, {
        type EditorSpace,
    } from '@/components/TheaterPlanEditor.svelte';

    let { space }: { space: EditorSpace } = $props();
</script>

<AppHead title="Editor · {space.name}" />

<div class="flex flex-col gap-4 p-4">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">
            {space.name}
        </h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Arrastra los asientos en modo edición. El movimiento respeta el zoom (Panzoom).
        </p>
    </div>

    <TheaterPlanEditor {space} />
</div>
