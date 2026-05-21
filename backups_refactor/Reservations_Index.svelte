<script module>
    export const layout = null;
</script>

<script>
    import { router, page } from '@inertiajs/svelte';
    import Panzoom from '@panzoom/panzoom';
    import { onMount, onDestroy } from 'svelte';

    let { space, event, seats, stripeKey, ticketPrice } = $props();

    // Array para guardar los IDs de los asientos seleccionados
    let selectedSeatId = $state(null);
    let selectedSeatName = $state('');

    // Estado reactivo para el formulario de invitado
    let guestName = $state('');
    let guestEmail = $state('');
    let guestPhone = $state('');

    // Estados reactivos para reservas temporales (Paso 3)
    let currentReservationId = $state(null);
    let expiresAt = $state(null);
    let timeLeft = $state(0); // Segundos restantes
    let timerInterval = null;

    // Estados reactivos para Stripe / Pago (Paso 4)
    let stripeInstance = $state(null);
    let cardElement = $state(null);
    let showPaymentForm = $state(false);
    let paymentProcessing = $state(false);
    let paymentError = $state(null);
    let paymentSuccess = $state(false);
    let confirmedSeatName = $state('');
    let confirmedPaymentId = $state(null);
    let confirmedTicketToken = $state(null);
    let cardMounted = false;

    const auth = $derived(page.props.auth);

    // Derivados reactivos del temporizador (Svelte 5)
    const minutesLeft = $derived(Math.floor(timeLeft / 60));
    const secondsLeft = $derived(timeLeft % 60);
    const formattedTime = $derived(
        `${minutesLeft}:${secondsLeft < 10 ? '0' : ''}${secondsLeft}`
    );

    // --- INGENIERÍA: CÁLCULO DE FILAS (A, B, C...) ---
    const seatW = 32;
    const seatH = 32;
    const paddingLeftForLabels = 40; // Espacio para letras A, B, C a la izquierda

    // --- INGENIERÍA: Funciones para evitar el desfase de zona horaria (UTC-5) ---
    function formatLocalDate(dateStr) {
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

    function formatLocalTime(dateStr) {
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
        if (!seats) return [];
        const letters = [
            ...new Set(seats.map((n) => n.identifier.charAt(0))),
        ].sort((a, b) => a.localeCompare(b));

        return letters.map((letter) => {
            const rowNodes = seats.filter((n) =>
                n.identifier.startsWith(letter),
            );
            const minY = Math.min(...rowNodes.map((n) => n.pos_y));
            return { letter, y: minY + seatH / 2 + 3 };
        });
    });

    // Panzoom
    let panzoomInstance = null;
    let panzoomNode = null;

    function getSeatingBounds() {
        const list = seats;
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

        const padding = 0.85;
        const scaleX = (containerW / bbox.width) * padding;
        const scaleY = (containerH / bbox.height) * padding;
        const initialScale = Math.min(scaleX, scaleY);

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

    // --- LÓGICA DE APARTADO TEMPORAL ---
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    }

    async function apartarSeat(seatId) {
        const response = await fetch('/espacios/reservar/apartar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ seat_id: seatId, event_id: event.id })
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Error al apartar');
        }
        return await response.json();
    }

    async function releaseHold(reservationId) {
        try {
            await fetch('/espacios/reservar/liberar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reservation_id: reservationId }),
                keepalive: true
            });
        } catch (err) {
            console.error('Error al liberar hold:', err);
        }
    }

    function startTimer(expireTimeStr) {
        if (timerInterval) clearInterval(timerInterval);

        const expireTime = new Date(expireTimeStr).getTime();

        const updateTimer = () => {
            const now = new Date().getTime();
            const diff = expireTime - now;

            if (diff <= 0) {
                timeLeft = 0;
                clearInterval(timerInterval);
                alert('Tu tiempo de apartado ha expirado y el asiento ha sido liberado.');
                resetSelection();
                router.reload({ only: ['seats'] });
            } else {
                timeLeft = Math.ceil(diff / 1000);
            }
        };

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
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

    async function selectSeat(seat) {
        if (seat.status !== 'disponible' && selectedSeatId !== seat.id) return;

        // Si es el mismo asiento que ya está seleccionado, lo liberamos y deseleccionamos
        if (selectedSeatId === seat.id) {
            const oldReservationId = currentReservationId;
            resetSelection();
            await releaseHold(oldReservationId);
            router.reload({ only: ['seats'] });
            return;
        }

        // Si ya hay un asiento seleccionado diferente, primero lo liberamos
        if (currentReservationId) {
            const oldReservationId = currentReservationId;
            resetSelection();
            await releaseHold(oldReservationId);
            router.reload({ only: ['seats'] });
        }

        // Intentamos apartar el nuevo asiento
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
        } catch (err) {
            alert(err.message || 'El asiento ya no está disponible o ha sido bloqueado.');
            router.reload({ only: ['seats'] });
        }
    }

    function abrirFormularioPago() {
        if (!currentReservationId) return;

        if (!auth.user) {
            if (!guestName.trim() || !guestEmail.trim() || !guestPhone.trim()) {
                alert('Por favor, completa todos tus datos de contacto (Nombre, Correo y Teléfono).');
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

        // Montar el Card Element de Stripe después de que el DOM esté listo
        setTimeout(() => {
            if (!stripeInstance || cardMounted) return;
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

    async function procesarPago() {
        if (!stripeInstance || !cardElement || !currentReservationId) return;

        paymentProcessing = true;
        paymentError = null;

        try {
            // 1. Crear PaymentMethod de forma segura con Stripe.js
            const { paymentMethod, error: pmError } = await stripeInstance.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (pmError) {
                paymentError = pmError.message;
                paymentProcessing = false;
                return;
            }

            // 2. Enviar al backend para procesar con Laravel Cashier
            const csrf = getCookie('XSRF-TOKEN') || '';
            const payload = {
                reservation_id: currentReservationId,
                payment_method_id: paymentMethod.id,
            };
            if (!auth.user) {
                payload.guest_name  = guestName.trim();
                payload.guest_email = guestEmail.trim();
                payload.guest_phone = guestPhone.trim();
            }

            const response = await fetch('/espacios/reservar/pagar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                paymentError = data.message || 'Error al procesar el pago.';
                paymentProcessing = false;
                return;
            }

            // 3. Pago exitoso
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
            router.reload({ only: ['seats'] });

        } catch (err) {
            paymentError = 'Ocurrió un error inesperado. Por favor intenta de nuevo.';
        } finally {
            paymentProcessing = false;
        }
    }

    // Cargar Stripe.js dinámicamente y registrar keepalive
    onMount(() => {
        // Cargar Stripe.js desde CDN oficial usando la clave pública del servidor
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => {
            stripeInstance = window.Stripe(stripeKey);
        };
        document.head.appendChild(script);

        const handleBeforeUnload = () => {
            if (currentReservationId) {
                const csrf = getCookie('XSRF-TOKEN') || '';
                fetch('/espacios/reservar/liberar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ reservation_id: currentReservationId }),
                    keepalive: true
                });
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            if (currentReservationId) releaseHold(currentReservationId);
            stopTimer();
        };
    });

    let seatingBounds = $state(null);
    $effect(() => {
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

        <!--
        <div style="background: black; color: lime; padding: 10px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; margin-bottom: 10px;">
            DEBUG DE ASIENTOS: 
            {seats ? `Se recibieron ${seats.length} asientos.` : 'LA VARIABLE SEATS ESTÁ INDEFINIDA O NULL.'}
            <br>
            PRIMER ASIENTO:
            {seats && seats.length > 0 ? JSON.stringify(seats[0]) : 'No hay datos'}
        </div>
        -->

        <div
            style="width: 100%; height: 75vh; background-color: white; border-radius: 1rem; overflow: hidden; touch-action: none; border: 1px solid #e2e8f0;"
        >
            <svg width="100%" height="100%" style="display: block;">
                <g
                    use:setupPanZoom={`${event?.id ?? 0}|${seats?.length ?? 0}`}
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

                    {#if seats}
                        {#each seats as seat}
                            <g
                                onclick={() => selectSeat(seat)}
                                style="cursor: {seat.status !== 'disponible' && selectedSeatId !== seat.id
                                    ? 'not-allowed'
                                    : 'pointer'}; pointer-events: all;"
                            >
                                <rect
                                    x={seat.pos_x}
                                    y={seat.pos_y}
                                    width="32"
                                    height="32"
                                    rx="7"
                                    fill={selectedSeatId === seat.id
                                        ? '#3b82f6'
                                        : seat.status !== 'disponible'
                                          ? '#ef4444'
                                          : '#10b981'}
                                    stroke={selectedSeatId === seat.id
                                        ? 'white'
                                        : 'none'}
                                    stroke-width="2"
                                    style="transition: fill 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: {selectedSeatId === seat.id ? '0 0 10px rgba(59,130,246,0.5)' : 'none'};"
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

            <div style="display: flex; align-items: flex-end; gap: 1rem; width: 100%; max-width: 640px; flex-direction: column; box-sizing: border-box;">

                <!-- ✅ BOLETO DE ÉXITO -->
                {#if paymentSuccess}
                    <div class="success-ticket">
                        <div class="ticket-header">
                            <div class="ticket-icon">🎟️</div>
                            <div>
                                <div class="ticket-title">¡Pago Exitoso!</div>
                                <div class="ticket-subtitle">Tu boleto ha sido confirmado</div>
                            </div>
                        </div>
                        <div class="ticket-divider"></div>
                        <div class="ticket-details">
                            <div class="ticket-row">
                                <span class="ticket-label">EVENTO</span>
                                <span class="ticket-value">{event?.name}</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">ASIENTO</span>
                                <span class="ticket-value ticket-seat">{confirmedSeatName}</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">TOTAL PAGADO</span>
                                <span class="ticket-value ticket-amount">${ticketPrice}.00 MXN</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">REF. PAGO</span>
                                <span class="ticket-value ticket-ref">#{confirmedPaymentId}</span>
                            </div>
                        </div>
                        <div class="ticket-footer" style="display: flex; flex-direction: column; gap: 1rem; align-items: center; justify-content: center; padding: 1.25rem;">
                            <span style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem; text-align: center; display: block;">
                                🎭 Presenta este comprobante en taquilla o accede a tu boleto digital:
                            </span>
                            {#if confirmedTicketToken}
                                <a 
                                    href="/boletos/{confirmedTicketToken}" 
                                    class="ticket-btn"
                                    style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4); width: 100%; box-sizing: border-box; text-align: center; transition: all 0.2s ease;"
                                >
                                    🎟️ Ver Mi Boleto Digital (QR)
                                </a>
                            {/if}
                        </div>
                    </div>

                <!-- 🟡 HOLD ACTIVO -->
                {:else if selectedSeatId}
                    <!-- Banner de cuenta regresiva -->
                    <div class="timer-banner">
                        <span class="timer-label">⏳ Tiempo disponible para completar tu compra:</span>
                        <span class="timer-clock" class:timer-urgent={timeLeft < 120}>{formattedTime}</span>
                    </div>

                    <!-- Formulario de Datos de Invitado -->
                    {#if !auth.user}
                        <div class="panel-card">
                            <h3 class="panel-title">👤 Datos de Contacto</h3>
                            <div class="field-group">
                                <label class="field-label" for="guest-name">Nombre Completo *</label>
                                <input id="guest-name" type="text" bind:value={guestName} placeholder="Ej. Juan Pérez" class="field-input" />
                            </div>
                            <div class="fields-grid">
                                <div class="field-group">
                                    <label class="field-label" for="guest-email">Correo Electrónico *</label>
                                    <input id="guest-email" type="email" bind:value={guestEmail} placeholder="juan@correo.com" class="field-input" />
                                </div>
                                <div class="field-group">
                                    <label class="field-label" for="guest-phone">Teléfono *</label>
                                    <input id="guest-phone" type="tel" bind:value={guestPhone} placeholder="9831234567" class="field-input" />
                                </div>
                            </div>
                        </div>
                    {/if}

                    <!-- Resumen del asiento -->
                    <div class="seat-summary">
                        <div class="seat-info">
                            <span class="seat-label">Asiento seleccionado</span>
                            <span class="seat-name">{selectedSeatName}</span>
                        </div>
                        <div class="seat-price">
                            <span class="price-label">Total</span>
                            <span class="price-amount">${ticketPrice}.00 MXN</span>
                        </div>
                    </div>

                    <!-- Formulario de Pago con Stripe -->
                    {#if !showPaymentForm}
                        <button onclick={abrirFormularioPago} class="btn-pay-open">
                            💳 Proceder al Pago
                        </button>
                    {:else}
                        <div class="panel-card stripe-panel">
                            <h3 class="panel-title stripe-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Datos de Tarjeta
                                <span class="stripe-badge">Seguro con Stripe 🔒</span>
                            </h3>

                            <!-- Stripe Card Element montado aquí -->
                            <div class="stripe-element-wrapper">
                                <label class="field-label">Número de tarjeta, fecha y CVV</label>
                                <div id="stripe-card-element" class="stripe-card-box"></div>
                            </div>

                            {#if paymentError}
                                <div class="payment-error">
                                    ⚠️ {paymentError}
                                </div>
                            {/if}

                            <div class="stripe-actions">
                                <button
                                    onclick={() => { showPaymentForm = false; paymentError = null; }}
                                    class="btn-cancel"
                                    disabled={paymentProcessing}
                                >
                                    Cancelar
                                </button>
                                <button
                                    onclick={procesarPago}
                                    class="btn-pay-confirm"
                                    disabled={paymentProcessing}
                                >
                                    {#if paymentProcessing}
                                        <span class="spinner"></span> Procesando...
                                    {:else}
                                        🔐 Pagar ${ticketPrice}.00 MXN
                                    {/if}
                                </button>
                            </div>

                            {#if import.meta.env.DEV}
                                <p class="stripe-note">Modo de prueba. Usa la tarjeta <code>4242 4242 4242 4242</code>, cualquier fecha futura y CVV.</p>
                            {/if}
                        </div>
                    {/if}
                {/if}
            </div>
        </div>
    </div>
</div>

<style>
    /* ── Leyenda de colores ───────────────────────── */
    g rect { pointer-events: all; }

    /* ── Timer Banner ────────────────────────────── */
    .timer-banner {
        width: 100%; background: #fffbeb; border: 1px solid #fde68a;
        padding: 12px 20px; border-radius: 12px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 2px 8px rgba(245,158,11,0.08); box-sizing: border-box;
    }
    .timer-label { font-size: 13px; font-weight: 700; color: #b45309; }
    .timer-clock {
        font-size: 18px; font-weight: 900; color: #d97706;
        font-family: monospace; background: white;
        padding: 4px 12px; border-radius: 6px; border: 1px solid #fde68a;
        transition: color 0.3s;
    }
    .timer-urgent { color: #dc2626 !important; animation: pulse-red 1s infinite; }
    @keyframes pulse-red {
        0%, 100% { opacity: 1; } 50% { opacity: 0.6; }
    }

    /* ── Seat Summary ────────────────────────────── */
    .seat-summary {
        width: 100%; display: flex; align-items: center; justify-content: space-between;
        background: #f0fdf4; border: 1px solid #bbf7d0;
        padding: 14px 20px; border-radius: 12px; box-sizing: border-box;
    }
    .seat-info { display: flex; flex-direction: column; }
    .seat-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; }
    .seat-name { font-size: 22px; font-weight: 900; color: #15803d; }
    .seat-price { display: flex; flex-direction: column; align-items: flex-end; }
    .price-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; }
    .price-amount { font-size: 20px; font-weight: 900; color: #166534; }

    /* ── Panel Card ──────────────────────────────── */
    .panel-card {
        width: 100%; background: #f8fafc; border: 1px solid #e2e8f0;
        padding: 1.25rem; border-radius: 1rem; box-sizing: border-box;
        display: flex; flex-direction: column; gap: 0.75rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .panel-title {
        margin: 0 0 0.25rem; font-size: 14px; font-weight: 800;
        color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;
        display: flex; align-items: center; gap: 8px;
    }
    .field-group { display: flex; flex-direction: column; gap: 4px; }
    .fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .field-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
    .field-input {
        width: 100%; border: 1px solid #cbd5e1; padding: 9px 12px;
        border-radius: 8px; font-size: 14px; outline: none;
        background: white; color: #0f172a; box-sizing: border-box;
        transition: border-color 0.2s;
    }
    .field-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

    /* ── Stripe Panel ────────────────────────────── */
    .stripe-panel { background: #fafbff; border-color: #c7d2fe; }
    .stripe-title { color: #4338ca; }
    .stripe-badge {
        margin-left: auto; font-size: 10px; background: #eef2ff;
        color: #4338ca; padding: 2px 8px; border-radius: 20px;
        font-weight: 700; text-transform: none;
    }
    .stripe-element-wrapper { display: flex; flex-direction: column; gap: 4px; }
    .stripe-card-box {
        background: white; border: 1.5px solid #c7d2fe;
        border-radius: 10px; padding: 14px 14px;
        transition: border-color 0.2s;
        box-shadow: 0 1px 4px rgba(99,102,241,0.06);
    }
    .stripe-card-box:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
    .payment-error {
        background: #fef2f2; border: 1px solid #fecaca;
        color: #dc2626; padding: 10px 14px; border-radius: 8px;
        font-size: 13px; font-weight: 600;
    }
    .stripe-actions { display: flex; gap: 0.75rem; margin-top: 0.25rem; }
    .btn-cancel {
        flex: 1; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;
        padding: 12px 16px; border-radius: 8px; font-weight: 700; cursor: pointer;
        font-size: 13px; transition: background 0.2s;
    }
    .btn-cancel:hover:not(:disabled) { background: #e2e8f0; }
    .btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-pay-confirm {
        flex: 2; background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white; border: none; padding: 12px 20px;
        border-radius: 8px; font-weight: 800; cursor: pointer;
        font-size: 14px; letter-spacing: 0.3px;
        transition: opacity 0.2s, transform 0.1s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-pay-confirm:hover:not(:disabled) { opacity: 0.9; }
    .btn-pay-confirm:active:not(:disabled) { transform: scale(0.97); }
    .btn-pay-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
    .stripe-note { margin: 0; font-size: 11px; color: #94a3b8; text-align: center; }
    .stripe-note code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-weight: 600; }
    .spinner {
        width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4);
        border-top-color: white; border-radius: 50%;
        animation: spin 0.7s linear infinite; display: inline-block;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Botón Proceder al Pago ───────────────────── */
    .btn-pay-open {
        width: 100%; background: linear-gradient(135deg, #10b981, #059669);
        color: white; border: none; padding: 14px 24px;
        border-radius: 10px; font-weight: 800; cursor: pointer;
        font-size: 15px; letter-spacing: 0.3px;
        transition: opacity 0.2s, transform 0.1s;
        box-shadow: 0 4px 14px rgba(16,185,129,0.3);
    }
    .btn-pay-open:hover { opacity: 0.92; transform: translateY(-1px); }
    .btn-pay-open:active { transform: scale(0.97); }

    /* ── Boleto de Éxito ─────────────────────────── */
    .success-ticket {
        width: 100%; background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 2px solid #6ee7b7; border-radius: 16px;
        padding: 1.5rem; box-sizing: border-box;
        box-shadow: 0 8px 24px rgba(16,185,129,0.15);
        animation: slide-in 0.4s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes slide-in {
        from { opacity: 0; transform: translateY(16px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .ticket-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .ticket-icon { font-size: 2.5rem; }
    .ticket-title { font-size: 1.4rem; font-weight: 900; color: #065f46; }
    .ticket-subtitle { font-size: 13px; color: #059669; font-weight: 600; }
    .ticket-divider {
        height: 1px; background: repeating-linear-gradient(90deg, #6ee7b7 0, #6ee7b7 6px, transparent 6px, transparent 12px);
        margin: 0 0 1rem;
    }
    .ticket-details { display: flex; flex-direction: column; gap: 10px; }
    .ticket-row { display: flex; justify-content: space-between; align-items: center; }
    .ticket-label { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; }
    .ticket-value { font-size: 14px; font-weight: 700; color: #1e293b; }
    .ticket-seat { font-size: 22px; font-weight: 900; color: #059669; }
    .ticket-amount { font-size: 18px; font-weight: 900; color: #065f46; }
    .ticket-ref { font-family: monospace; font-size: 12px; color: #6b7280; }
    .ticket-footer {
        margin-top: 1rem; text-align: center; font-size: 12px;
        color: #059669; font-weight: 600; padding-top: 0.75rem;
        border-top: 1px solid #a7f3d0;
    }
</style>
