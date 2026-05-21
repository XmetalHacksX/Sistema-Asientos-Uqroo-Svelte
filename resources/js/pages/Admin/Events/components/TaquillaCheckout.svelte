<script lang="ts">
    import { router } from '@inertiajs/svelte';

    interface Seat {
        id: number;
        identifier: string;
        status: string;
        user_id?: number | null;
        user?: {
            name: string;
            email: string;
        } | null;
    }

    interface User {
        id: number;
        name: string;
        email: string;
    }

    // Props using Svelte 5 runes
    let {
        selectedSeat = $bindable(),
        selectedUserId = $bindable(),
        users,
        event,
        onSuccessUpdate
    }: {
        selectedSeat: Seat | null;
        selectedUserId: string;
        users: User[];
        event: { id: number; name: string };
        onSuccessUpdate: (seatId: number) => void;
    } = $props();

    function updateSeatStatus(status: string) {
        if (!selectedSeat) return;
        router.put(`/admin/events/${event.id}/taquilla/${selectedSeat.id}`, {
            status: status,
            user_id: status === 'reservado' ? (selectedUserId || null) : null
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedSeat) {
                    onSuccessUpdate(selectedSeat.id);
                }
            }
        });
    }

    function assignUser() {
        if (!selectedSeat) return;
        router.put(`/admin/events/${event.id}/taquilla/${selectedSeat.id}`, {
            status: 'reservado',
            user_id: selectedUserId || null
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedSeat) {
                    onSuccessUpdate(selectedSeat.id);
                }
            }
        });
    }
</script>

<div class="bg-white border border-sidebar-border/70 rounded-xl shadow-sm flex flex-col p-6">
    <h3 class="text-xl font-bold text-foreground mb-6">Panel de Asiento</h3>
    
    {#if selectedSeat}
        <div class="flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-sidebar-border mb-5">
            <div class="flex flex-col">
                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">Identificador</span>
                <span class="text-3xl font-black text-slate-800">{selectedSeat.identifier}</span>
            </div>
            <div class="flex flex-col text-right">
                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">Estado Visual</span>
                <span class="font-black uppercase text-sm
                    {selectedSeat.status === 'disponible' ? 'text-emerald-600' : ''}
                    {selectedSeat.status === 'reservado' ? 'text-red-600' : ''}
                    {selectedSeat.status === 'bloqueado' ? 'text-orange-600' : ''}
                ">{selectedSeat.status}</span>
            </div>
        </div>

        {#if selectedSeat.user}
            <div class="mb-5 text-sm bg-blue-50 p-4 rounded-xl border border-blue-100 text-blue-900 shadow-sm">
                <div class="font-bold text-xs uppercase tracking-wider text-blue-500 mb-1">ASIGNADO A:</div>
                <div class="font-bold">{selectedSeat.user.name}</div>
                <div class="text-blue-700/80">{selectedSeat.user.email}</div>
            </div>
        {/if}

        <div class="flex flex-col gap-3 mt-2 pt-5 border-t border-sidebar-border/50">
            <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Acciones Rápidas</span>
            
            <button 
                onclick={() => updateSeatStatus('disponible')}
                class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold py-2.5 rounded-lg text-sm transition-colors shadow-sm"
            >
                ✓ Liberar Asiento
            </button>
            
            <button 
                onclick={() => updateSeatStatus('bloqueado')}
                class="w-full bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 font-bold py-2.5 rounded-lg text-sm transition-colors shadow-sm"
            >
                Bloquear (Protocolo / Staff)
            </button>
        </div>

        <!-- SECCIÓN DE ASIGNACIÓN A USUARIO -->
        <div class="mt-6 pt-5 border-t border-sidebar-border/50 flex flex-col gap-3">
            <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Asignación Manual</span>
            <select 
                bind:value={selectedUserId}
                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2.5 text-foreground focus:ring-2 focus:ring-primary outline-none"
            >
                <option value="">-- Buscar / Seleccionar Usuario --</option>
                {#each users as user}
                    <option value={user.id}>{user.name} ({user.email})</option>
                {/each}
            </select>

            <button 
                onclick={assignUser}
                disabled={!selectedUserId}
                class="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-bold py-2.5 rounded-lg text-sm transition-opacity disabled:opacity-50 mt-1 shadow-sm"
            >
                Asignar y Reservar
            </button>
        </div>
    {:else}
        <div class="flex flex-col items-center justify-center text-center py-12 text-slate-400">
            <svg class="h-16 w-16 text-slate-200 outline-none mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
            <p class="text-sm font-medium">Selecciona un punto en el mapa de la izquierda para acceder al panel de control de asientos.</p>
        </div>
    {/if}
</div>
