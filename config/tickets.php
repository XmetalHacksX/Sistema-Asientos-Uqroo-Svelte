<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Precio del Boleto
    |--------------------------------------------------------------------------
    | Precio base por asiento en MXN (pesos mexicanos).
    | Se utiliza tanto en el backend (controlador de pagos con Stripe)
    | como se expone al frontend a través de la prop del controlador de reservas.
    | Para cambiar el precio, solo modifica TICKET_PRICE_MXN en el archivo .env.
    */
    'ticket_price_mxn' => (int) env('TICKET_PRICE_MXN', 150),
];
