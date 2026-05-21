<script lang="ts">
    import { CreditCard, Lock, AlertCircle } from 'lucide-svelte';

    interface AuthProps {
        user: any;
    }

    let {
        auth,
        guestName = $bindable(''),
        guestEmail = $bindable(''),
        guestPhone = $bindable(''),
        showPaymentForm = $bindable(false),
        paymentProcessing,
        paymentError,
        ticketPrice,
        selectedSeatName,
        onpay,
        onopen
    } = $props<{
        auth: AuthProps;
        guestName: string;
        guestEmail: string;
        guestPhone: string;
        showPaymentForm: boolean;
        paymentProcessing: boolean;
        paymentError: string | null;
        ticketPrice: number;
        selectedSeatName: string;
        onpay: () => void;
        onopen: () => void;
    }>();
</script>

<style>
    /* Stripe Card Container */
    .stripe-container {
        padding: 1rem;
        border: 1.5px solid #d1fae5;
        border-radius: 12px;
        background: #f0fdf4;
        margin-bottom: 1.25rem;
        min-height: 20px;
    }
</style>

<!-- Formulario de Datos de Invitado -->
{#if !auth.user}
    <div class="w-full overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-emerald-950/40 dark:bg-[#0d1310]">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-emerald-950/40">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">👤 Datos de Contacto</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Ingresa tus datos para recibir tu boleto electrónico</p>
        </div>
        <div class="space-y-4 p-5">
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400" for="guest-name">Nombre Completo *</label>
                <input
                    type="text"
                    id="guest-name"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-900 transition-all focus:border-[#005E35] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#005E35]/10 disabled:opacity-50 dark:border-emerald-950/40 dark:bg-[#121b16] dark:text-white dark:focus:border-emerald-500"
                    placeholder="Juan Pérez"
                    bind:value={guestName}
                    disabled={showPaymentForm}
                />
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400" for="guest-email">Correo Electrónico *</label>
                <input
                    type="email"
                    id="guest-email"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-900 transition-all focus:border-[#005E35] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#005E35]/10 disabled:opacity-50 dark:border-emerald-950/40 dark:bg-[#121b16] dark:text-white dark:focus:border-emerald-500"
                    placeholder="juan@ejemplo.com"
                    bind:value={guestEmail}
                    disabled={showPaymentForm}
                />
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400" for="guest-phone">Teléfono de Contacto *</label>
                <input
                    type="tel"
                    id="guest-phone"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-900 transition-all focus:border-[#005E35] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#005E35]/10 disabled:opacity-50 dark:border-emerald-950/40 dark:bg-[#121b16] dark:text-white dark:focus:border-emerald-500"
                    placeholder="9831234567"
                    bind:value={guestPhone}
                    disabled={showPaymentForm}
                />
            </div>
        </div>
    </div>
{/if}

<!-- Formulario de Pago con Tarjeta (Stripe) -->
<div class="w-full overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-emerald-950/40 dark:bg-[#0d1310]">
    <!-- Header top strip -->
    <div class="h-1 w-full bg-gradient-to-r from-[#005E35] via-[#D49A15] to-[#005E35]"></div>

    <div class="border-b border-slate-100 px-5 py-4 dark:border-emerald-950/40">
        <div class="flex items-center gap-2">
            <CreditCard class="h-4 w-4 text-[#005E35] dark:text-emerald-400" />
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pago Seguro con Tarjeta</h3>
            <div class="ml-auto flex items-center gap-1 rounded-full border border-emerald-200/60 bg-emerald-50/80 px-2 py-0.5 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                <Lock class="h-3 w-3 text-[#005E35] dark:text-emerald-400" />
                <span class="text-[10px] font-bold text-[#005E35] dark:text-emerald-400">SSL Seguro</span>
            </div>
        </div>
    </div>

    <div class="p-5">
        {#if !showPaymentForm}
            <!-- Info de cobro inicial -->
            <div class="mb-5 rounded-2xl bg-slate-50 p-4 dark:bg-[#121b16]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Asiento Seleccionado</div>
                <div class="mt-1 text-xl font-black text-slate-900 dark:text-white">Fila {selectedSeatName}</div>
                <div class="mt-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total a Pagar</div>
                <div class="mt-1 text-2xl font-black text-[#D49A15] dark:text-amber-400">${ticketPrice}.00 MXN</div>
            </div>

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#005E35] to-[#004d2b] px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition-all hover:brightness-110 hover:shadow-emerald-900/30 active:scale-[0.99]"
                onclick={onopen}
            >
                <CreditCard class="h-4 w-4" />
                Proceder al Pago
            </button>
        {:else}
            <!-- Stripe Card Element -->
            <div class="mb-4">
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Información de Tarjeta</label>
                <div id="stripe-card-element" class="stripe-container dark:border-emerald-800/50 dark:bg-[#0f1d15]"></div>
            </div>

            {#if paymentError}
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-900/40 dark:bg-red-950/20">
                    <AlertCircle class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                    <span class="text-xs font-semibold text-red-700 dark:text-red-400">{paymentError}</span>
                </div>
            {/if}

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#005E35] to-[#004d2b] px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition-all hover:brightness-110 hover:shadow-emerald-900/30 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:brightness-100"
                onclick={onpay}
                disabled={paymentProcessing}
            >
                {#if paymentProcessing}
                    <span class="mr-1 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    Procesando Pago...
                {:else}
                    <CreditCard class="h-4 w-4" />
                    Confirmar y Pagar ${ticketPrice}.00 MXN
                {/if}
            </button>
        {/if}

        <!-- Trust badges -->
        <div class="mt-4 flex items-center justify-center gap-3 text-[10px] text-slate-400 dark:text-slate-500">
            <span class="flex items-center gap-1"><Lock class="h-3 w-3" /> Encriptado</span>
            <span class="opacity-40">•</span>
            <span>Stripe Payments</span>
            <span class="opacity-40">•</span>
            <span>UQROO Taquilla</span>
        </div>
    </div>
</div>
