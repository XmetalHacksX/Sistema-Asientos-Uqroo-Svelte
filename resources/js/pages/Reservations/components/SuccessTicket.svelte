<script lang="ts">
    import { CheckCircle, Ticket, ArrowRight } from 'lucide-svelte';

    interface EventProps {
        name: string;
    }

    let {
        event,
        confirmedSeatName,
        ticketPrice,
        confirmedPaymentId,
        confirmedTicketToken
    } = $props<{
        event: EventProps;
        confirmedSeatName: string;
        ticketPrice: number;
        confirmedPaymentId: number;
        confirmedTicketToken: string;
    }>();
</script>

<style>
    .success-ticket {
        width: 100%;
        box-sizing: border-box;
        margin-top: 1rem;
        animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes scaleIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<div class="success-ticket overflow-hidden rounded-3xl border border-emerald-200/60 bg-white shadow-xl dark:border-emerald-900/40 dark:bg-[#0d1310]">
    <!-- Accent strip top -->
    <div class="h-1.5 w-full bg-gradient-to-r from-[#005E35] via-[#D49A15] to-[#005E35]"></div>

    <!-- Header -->
    <div class="flex items-center gap-4 bg-gradient-to-r from-emerald-500/10 to-amber-500/5 px-6 py-5">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500/20 border border-emerald-500/30">
            <CheckCircle class="h-6 w-6 text-[#005E35] dark:text-emerald-400" />
        </div>
        <div>
            <div class="text-lg font-black text-[#005E35] dark:text-emerald-400">¡Pago Exitoso!</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Tu boleto ha sido confirmado · Universidad Autónoma del Estado de Quintana Roo</div>
        </div>
    </div>

    <!-- Dashed divider simulating ticket perforation -->
    <div class="relative">
        <div class="border-t-2 border-dashed border-slate-200 dark:border-emerald-950/50 mx-4"></div>
        <div class="absolute -left-2 top-1/2 -translate-y-1/2 h-4 w-4 rounded-full bg-slate-100 dark:bg-[#070b09]"></div>
        <div class="absolute -right-2 top-1/2 -translate-y-1/2 h-4 w-4 rounded-full bg-slate-100 dark:bg-[#070b09]"></div>
    </div>

    <!-- Ticket body details -->
    <div class="grid grid-cols-2 gap-4 px-6 py-5">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Evento</div>
            <div class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white leading-snug">{event?.name}</div>
        </div>
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Asiento</div>
            <div class="mt-0.5 inline-flex items-center rounded-lg bg-[#005E35]/10 dark:bg-emerald-950/60 px-2.5 py-1 text-sm font-black text-[#005E35] dark:text-emerald-400">
                {confirmedSeatName}
            </div>
        </div>
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Pagado</div>
            <div class="mt-0.5 text-base font-black text-[#D49A15] dark:text-amber-400">${ticketPrice}.00 MXN</div>
        </div>
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Ref. Pago</div>
            <div class="mt-0.5 font-mono text-sm font-bold text-slate-500 dark:text-slate-400">#{confirmedPaymentId}</div>
        </div>
    </div>

    <!-- Footer CTA -->
    <div class="bg-slate-50/80 px-6 py-4 dark:bg-emerald-950/20">
        <p class="mb-3 text-center text-[11px] text-slate-500 dark:text-slate-400">
            Presenta este comprobante en taquilla o accede a tu boleto digital con código QR:
        </p>
        {#if confirmedTicketToken}
            <a
                href="/boletos/{confirmedTicketToken}"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#005E35] to-[#004d2b] px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition-all hover:brightness-105 hover:shadow-emerald-900/30"
            >
                <Ticket class="h-4 w-4" />
                Ver Mi Boleto Digital (QR)
                <ArrowRight class="h-4 w-4" />
            </a>
        {/if}
    </div>
</div>
