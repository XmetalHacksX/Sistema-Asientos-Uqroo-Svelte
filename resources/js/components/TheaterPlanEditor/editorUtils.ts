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

export function getSeatColor(
    node: EditorNode,
    selectedItem: { type: 'node' | 'object'; id: number } | null
): string {
    if (selectedItem?.type === 'node' && selectedItem.id === node.id) {
        return '#3b82f6';
    }
    if (node.status === 'maintenance') return '#f59e0b';
    if (node.status === 'blocked') return '#64748b';
    return '#10b981';
}

export function calculateZoomPosition(
    parentWidth: number,
    parentHeight: number,
    bbox: { width: number; height: number; x: number; y: number },
    scale = 0.3
) {
    const x = parentWidth / 2 - (bbox.width * scale) / 2 - bbox.x * scale;
    const y = parentHeight / 2 - (bbox.height * scale) / 2 - bbox.y * scale;
    return { x, y, scale };
}
