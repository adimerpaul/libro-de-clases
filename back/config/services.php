<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Servidor Socket.IO compartido (proyecto socket/): POST {url}/notify { event, data }
    'socket' => [
        'url' => env('SOCKET_URL', 'http://localhost:3000'),
        'evento' => env('SOCKET_EVENTO_RFID', 'libro-clases-rfid'),
    ],

    // Motor de propuesta de la asistencia RFID (App\Support\AsistenciaRfid)
    'rfid' => [
        'hora_inicio' => env('RFID_HORA_INICIO', '08:00'),
        'tolerancia' => (int) env('RFID_TOLERANCIA_MIN', 10), // minutos tras el inicio que aún cuentan como presente
        'rebote' => (int) env('RFID_REBOTE_SEG', 30),          // pasadas repetidas de la misma tarjeta que se ignoran
        'simulador_get' => (bool) env('RFID_SIMULADOR_GET', env('APP_ENV') === 'local'), // GET /api/rfid/simular sin sesión
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
