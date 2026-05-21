<script module>
    export const layout = null;
</script>

<script lang="ts">
    import { router, page, Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { fade, slide } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import {
        School,
        ArrowLeft,
        CheckCircle,
        Info,
        Sparkles,
        CreditCard,
        Clock,
        Lock,
        ShieldAlert,
        Award,
    } from 'lucide-svelte';

    // Subcomponentes modulares refactorizados
    import EventInfoCard from './components/EventInfoCard.svelte';
    import SeatingChart from './components/SeatingChart.svelte';
    import PaymentForm from './components/PaymentForm.svelte';
    import SuccessTicket from './components/SuccessTicket.svelte';

    interface SeatProps {
        id: number;
        identifier: string;
        pos_x: number;
        pos_y: number;
        status: string;
    }

    interface SpaceProps {
        name: string;
        layout_objects?: any[];
    }

    interface EventProps {
        id: number;
        name: string;
        description?: string;
        poster_url?: string;
        start_time: string;
        end_time: string;
    }

    let { space, event, seats, stripeKey, ticketPrice } = $props<{
        space: SpaceProps;
        event: EventProps;
        seats: SeatProps[];
        stripeKey: string;
        ticketPrice: number;
    }>();

    // Svelte 5 local reactive state for real-time WebSockets synchronization
    let localSeats = $state<SeatProps[]>([]);
    $effect(() => {
        localSeats = seats;
    });

    // Estado reactivo de selección de asiento
    let selectedSeatId = $state<number | null>(null);
    let selectedSeatName = $state('');

    // Estado reactivo de contacto para invitados
    let guestName = $state('');
    let guestEmail = $state('');
    let guestPhone = $state('');

    // Reservas temporales (Hold timer)
    let currentReservationId = $state<number | null>(null);
    let expiresAt = $state<string | null>(null);
    let timeLeft = $state(0);
    let timerInterval: any = null;

    // Estados de Stripe / Procesamiento de Pago
    let stripeInstance = $state<any>(null);
    let cardElement = $state<any>(null);
    let showPaymentForm = $state(false);
    let paymentProcessing = $state(false);
    let paymentError = $state<string | null>(null);
    let paymentSuccess = $state(false);
    let confirmedSeatName = $state('');
    let confirmedPaymentId = $state<number | null>(null);
    let confirmedTicketToken = $state<string | null>(null);
    let cardMounted = false;

    // Estados para Toast Notification
    let showToast = $state(false);
    let toastMessage = $state('');

    const auth: any = $derived(page.props.auth);

    // Derivados reactivos para el temporizador (Svelte 5)
    const minutesLeft = $derived(Math.floor(timeLeft / 60));
    const secondsLeft = $derived(timeLeft % 60);
    const formattedTime = $derived(
        `${minutesLeft}:${secondsLeft < 10 ? '0' : ''}${secondsLeft}`,
    );

    // Ingeniería: Formateadores locales (UTC-5)
    function formatLocalDate(dateStr: string) {
        if (!dateStr) return 'Por definir';
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    function formatLocalTime(dateStr: string) {
        if (!dateStr) return '--:--';
        const cleanStr = String(dateStr)
            .replace('Z', '')
            .replace('.000000', '');
        return new Date(cleanStr).toLocaleTimeString('es-MX', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    let fechaEvento = $derived(formatLocalDate(event?.start_time));
    let horaInicio = $derived(formatLocalTime(event?.start_time));
    let horaFin = $derived(formatLocalTime(event?.end_time));

    const distinctRowLetters = $derived.by(() => {
        if (!localSeats) return [];
        const letters = [
            ...new Set(localSeats.map((n) => n.identifier.charAt(0))),
        ].sort((a, b) => a.localeCompare(b));

        return letters.map((letter) => {
            const rowNodes = localSeats.filter((n) =>
                n.identifier.startsWith(letter),
            );
            const minY = Math.min(...rowNodes.map((n) => n.pos_y));
            return { letter, y: minY + 19 }; // 32/2 + 3 = 19
        });
    });

    const seatW = 32;
    const seatH = 32;
    const paddingLeftForLabels = 40;

    let seatingBounds = $derived.by(() => {
        const list = localSeats;
        if (!list?.length) return null;

        let minX = Infinity,
            minY = Infinity,
            maxX = -Infinity,
            maxY = -Infinity;

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

        minX -= paddingLeftForLabels;
        minY -= 20;

        return { x: minX, y: minY, width: maxX - minX, height: maxY - minY };
    });

    // --- COOKIES & ACCIONES API ---
    function getCookie(name: string) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2)
            return decodeURIComponent(parts.pop().split(';').shift() || '');
        return '';
    }

    async function apartarSeat(seatId: number) {
        const response = await fetch('/espacios/reservar/apartar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                Accept: 'application/json',
            },
            body: JSON.stringify({ seat_id: seatId, event_id: event.id }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Error al apartar');
        }
        return await response.json();
    }

    async function releaseHold(reservationId: number) {
        try {
            await fetch('/espacios/reservar/liberar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ reservation_id: reservationId }),
                keepalive: true,
            });
        } catch (err) {
            console.error('Error al liberar hold:', err);
        }
    }

    function startTimer(expireTimeStr: string) {
        if (timerInterval) clearInterval(timerInterval);
        const expireTime = new Date(expireTimeStr).getTime();

        const updateTimer = () => {
            const now = new Date().getTime();
            const diff = expireTime - now;

            if (diff <= 0) {
                timeLeft = 0;
                clearInterval(timerInterval);
                alert(
                    'Tu tiempo de apartado ha expirado y el asiento ha sido liberado.',
                );
                resetSelection();
                router.reload({ only: ['seats'] });
            } else {
                timeLeft = Math.ceil(diff / 1000);
            }
        };

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    // Trigger Toast Notification on success
    function triggerSuccessToast(seatName: string) {
        toastMessage = `¡Pago de $${ticketPrice}.00 MXN confirmado exitosamente! Tu asiento "${seatName}" ha sido reservado.`;
        showToast = true;
        setTimeout(() => {
            showToast = false;
        }, 6000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        timeLeft = 0;
    }

    function resetSelection() {
        selectedSeatId = null;
        selectedSeatName = '';
        currentReservationId = null;
        expiresAt = null;
        stopTimer();
    }

    async function handleSeatSelection(seat: SeatProps) {
        if (seat.status !== 'disponible' && selectedSeatId !== seat.id) return;

        // Liberar si vuelve a hacer click en el mismo
        if (selectedSeatId === seat.id) {
            const oldReservationId = currentReservationId;
            resetSelection();
            if (oldReservationId) await releaseHold(oldReservationId);
            router.reload({ only: ['seats'] });
            return;
        }

        // Si ya tenía otro asiento seleccionado, liberarlo primero
        if (currentReservationId) {
            const oldReservationId = currentReservationId;
            resetSelection();
            await releaseHold(oldReservationId);
            router.reload({ only: ['seats'] });
        }

        // Intentar apartar
        try {
            const res = await apartarSeat(seat.id);
            if (res.success) {
                selectedSeatId = seat.id;
                selectedSeatName = seat.identifier;
                currentReservationId = res.reservation_id;
                expiresAt = res.expires_at;
                startTimer(res.expires_at);
                router.reload({ only: ['seats'] });
            }
        } catch (err: any) {
            alert(err.message || 'El asiento ya no está disponible.');
            router.reload({ only: ['seats'] });
        }
    }

    // Stripe checkout mount
    function initializeStripeForm() {
        if (!currentReservationId) return;

        if (!auth.user) {
            if (!guestName.trim() || !guestEmail.trim() || !guestPhone.trim()) {
                alert('Por favor, completa todos tus datos de contacto.');
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(guestEmail.trim())) {
                alert('Por favor, ingresa un correo electrónico válido.');
                return;
            }
        }

        showPaymentForm = true;
        paymentError = null;

        setTimeout(() => {
            // @ts-ignore
            if (!stripeInstance || cardMounted || !window.Stripe) return;
            const elements = stripeInstance.elements();
            cardElement = elements.create('card', {
                style: {
                    base: {
                        fontFamily: '"Inter", system-ui, sans-serif',
                        fontSize: '15px',
                        color: '#1e293b',
                        '::placeholder': { color: '#94a3b8' },
                    },
                    invalid: { color: '#ef4444' },
                },
                hidePostalCode: true,
            });
            cardElement.mount('#stripe-card-element');
            cardMounted = true;
        }, 80);
    }

    async function handlePaymentProcessing() {
        if (!stripeInstance || !cardElement || !currentReservationId) return;

        paymentProcessing = true;
        paymentError = null;

        try {
            const { paymentMethod, error: pmError } =
                await stripeInstance.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                });

            if (pmError) {
                paymentError = pmError.message || 'Error de tarjeta.';
                paymentProcessing = false;
                return;
            }

            const csrf = getCookie('XSRF-TOKEN');
            const payload: any = {
                reservation_id: currentReservationId,
                payment_method_id: paymentMethod.id,
            };
            if (!auth.user) {
                payload.guest_name = guestName.trim();
                payload.guest_email = guestEmail.trim();
                payload.guest_phone = guestPhone.trim();
            }

            const response = await fetch('/espacios/reservar/pagar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                paymentError = data.message || 'Error al procesar el pago.';
                paymentProcessing = false;
                return;
            }

            // Éxito de compra
            stopTimer();
            confirmedSeatName = selectedSeatName;
            confirmedPaymentId = data.payment_id;
            confirmedTicketToken = data.ticket_token;
            paymentSuccess = true;
            showPaymentForm = false;
            currentReservationId = null;
            selectedSeatId = null;
            selectedSeatName = '';
            cardMounted = false;

            // Disparar Toast de éxito animado
            triggerSuccessToast(confirmedSeatName);

            router.reload({ only: ['seats'] });
        } catch (err) {
            paymentError =
                'Ocurrió un error inesperado. Por favor intenta de nuevo.';
        } finally {
            paymentProcessing = false;
        }
    }

    onMount(() => {
        // Cargar Stripe.js desde CDN oficial
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => {
            // @ts-ignore
            stripeInstance = window.Stripe(stripeKey);
        };
        document.head.appendChild(script);

        // Echo Channel Subscription to sync seats status in real-time
        if (window.Echo) {
            window.Echo.channel(`event.${event.id}`).listen(
                '.seat.updated',
                (e: { seatIdentifier: string; status: string }) => {
                    localSeats = localSeats.map((s) =>
                        s.identifier === e.seatIdentifier
                            ? { ...s, status: e.status }
                            : s,
                    );
                },
            );
        }

        const handleBeforeUnload = () => {
            if (currentReservationId) {
                const csrf = getCookie('XSRF-TOKEN');
                fetch('/espacios/reservar/liberar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        reservation_id: currentReservationId,
                    }),
                    keepalive: true,
                });
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            if (currentReservationId) releaseHold(currentReservationId);
            if (window.Echo) {
                window.Echo.leaveChannel(`event.${event.id}`);
            }
            stopTimer();
        };
    });
</script>

<AppHead title={`Reservar Boletos - ${event?.name || 'Evento'}`} />

<!-- Floating Toast Success Notification -->
{#if showToast}
    <div
        class="fixed right-6 top-24 z-[100] flex max-w-md items-center gap-3.5 rounded-2xl border border-emerald-100 bg-white p-4 shadow-2xl dark:border-emerald-950/40 dark:bg-[#0d1310]"
        transition:fade={{ duration: 250 }}
    >
        <div
            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/50"
        >
            <CheckCircle class="h-5 w-5 text-[#005E35] dark:text-emerald-400" />
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-black text-[#005E35] dark:text-emerald-400">
                ¡Compra Confirmada!
            </h4>
            <p
                class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-snug"
            >
                {toastMessage}
            </p>
        </div>
    </div>
{/if}

<div
    class="min-h-screen bg-slate-50 font-sans text-slate-900 transition-colors duration-300 dark:bg-[#070b09] dark:text-[#EDEDEC]"
>
    <!-- Header Universitario Premium -->
    <header
        class="sticky top-0 z-50 flex items-center justify-between border-b border-slate-100 bg-white/80 px-6 py-4 shadow-sm backdrop-blur-md dark:border-emerald-950/60 dark:bg-[#0d1611]/90"
    >
        <div class="flex items-center gap-3">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#005E35] to-[#004024] text-white shadow-md border border-[#D49A15]/30"
            >
                <School class="h-5 w-5 text-amber-400" />
            </div>
            <div class="flex flex-col leading-none">
                <span
                    class="text-lg font-black tracking-tight text-[#005E35] dark:text-emerald-400"
                    >Sistema de Reservas</span
                >
                <span
                    class="text-[10px] font-bold uppercase tracking-widest text-[#D49A15] dark:text-amber-400"
                    >Cultura UQROO</span
                >
            </div>
        </div>

        <Link
            href="/"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition-all hover:bg-slate-50 hover:text-slate-900 dark:border-emerald-950/60 dark:bg-[#0f1c15] dark:text-emerald-300 dark:hover:bg-emerald-900/30"
        >
            <ArrowLeft class="h-3.5 w-3.5" />
            Regresar a Cartelera
        </Link>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8">
        <!-- Event Details Header Card -->
        <EventInfoCard {event} {space} {fechaEvento} {horaInicio} {horaFin} />

        <!-- Layout of Seating Grid and Checkout Panel -->
        <div
            class="overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm dark:border-emerald-950/40 dark:bg-[#0d1310] sm:p-8"
        >
            <div
                class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div>
                    <h2
                        class="text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl"
                    >
                        Selecciona tu Asiento
                    </h2>
                    <p
                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                    >
                        Haz clic en un asiento disponible para iniciar el
                        apartado temporal.
                    </p>
                </div>

                <!-- Seating Legend -->
                <div
                    class="flex flex-wrap gap-4 text-xs font-bold text-slate-600 dark:text-slate-400"
                >
                    <span class="flex items-center gap-1.5">
                        <div
                            class="h-3.5 w-3.5 rounded bg-[#005E35] border border-[#005E35]/30"
                        ></div>
                        Disponible
                    </span>
                    <span class="flex items-center gap-1.5">
                        <div
                            class="h-3.5 w-3.5 rounded bg-[#cbd5e1] border border-slate-300/40 dark:bg-emerald-950/55 dark:border-emerald-900/40"
                        ></div>
                        Ocupado
                    </span>
                    <span class="flex items-center gap-1.5">
                        <div
                            class="h-3.5 w-3.5 rounded bg-[#D49A15] border border-amber-500/20"
                        ></div>
                        Tu Selección
                    </span>
                </div>
            </div>

            <!-- Seating grid chart -->
            <SeatingChart
                seats={localSeats}
                {space}
                {event}
                {selectedSeatId}
                {seatingBounds}
                {distinctRowLetters}
                onselect={handleSeatSelection}
            />

            <!-- Panel block for checkout -->
            <div
                class="mt-6 flex flex-col items-stretch justify-end gap-6 md:flex-row md:items-start"
            >
                <div
                    class="flex-1 rounded-2xl bg-slate-50 p-5 dark:bg-[#121b16] border border-slate-100 dark:border-emerald-950/20"
                >
                    <h3
                        class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2"
                    >
                        Instrucciones de Compra
                    </h3>
                    <ul
                        class="space-y-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed list-disc list-inside"
                    >
                        <li>
                            Los asientos en color <strong
                                class="text-[#005E35] dark:text-emerald-400"
                                >Verde</strong
                            > están disponibles para compra.
                        </li>
                        <li>
                            Al seleccionar un asiento se te otorgan <strong
                                class="text-[#D49A15]">10 minutos</strong
                            > para concretar la transacción segura antes de liberarlo.
                        </li>
                        <li>
                            El boleto digital contiene un código QR dinámico de
                            alta seguridad.
                        </li>
                    </ul>
                </div>

                <div class="w-full max-w-md flex-shrink-0">
                    <!-- Pago exitoso (Boleto) -->
                    {#if paymentSuccess}
                        <SuccessTicket
                            {event}
                            {confirmedSeatName}
                            {ticketPrice}
                            confirmedPaymentId={confirmedPaymentId || 0}
                            confirmedTicketToken={confirmedTicketToken || ''}
                        />

                        <!-- Asiento seleccionado, mostrar temporizador y checkout -->
                    {:else if selectedSeatId}
                        <div
                            class="mb-4 flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50/80 px-4 py-3 dark:border-amber-950/20 dark:bg-amber-950/10"
                            transition:slide
                        >
                            <span
                                class="text-xs font-bold text-amber-800 dark:text-amber-400 flex items-center gap-1.5"
                            >
                                <Clock class="h-4 w-4" />
                                Tiempo disponible para pagar:
                            </span>
                            <span
                                class="rounded-lg bg-amber-500/10 px-2 py-0.5 font-mono text-sm font-black text-amber-800 dark:text-amber-400"
                                class:timer-urgent={timeLeft < 120}
                            >
                                {formattedTime}
                            </span>
                        </div>

                        <PaymentForm
                            {auth}
                            bind:guestName
                            bind:guestEmail
                            bind:guestPhone
                            bind:showPaymentForm
                            {paymentProcessing}
                            {paymentError}
                            {ticketPrice}
                            {selectedSeatName}
                            onpay={handlePaymentProcessing}
                            onopen={initializeStripeForm}
                        />
                    {/if}
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Animaciones personalizadas -->
<style>
    .timer-urgent {
        color: #ef4444;
        background: #fee2e2;
        animation: pulse 1s infinite alternate;
    }

    @keyframes pulse {
        from {
            opacity: 1;
        }
        to {
            opacity: 0.6;
        }
    }
</style>
