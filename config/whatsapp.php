<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Server URL
    |--------------------------------------------------------------------------
    |
    | The URL of the WhatsApp server that handles message sending.
    |
    */
    'server_url' => env('WHATSAPP_SERVER_URL', 'http://localhost:3300'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Access Token
    |--------------------------------------------------------------------------
    |
    | The access token for authenticating with the WhatsApp server.
    |
    */
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Message Rate Limit
    |--------------------------------------------------------------------------
    |
    | Delay between sending messages in seconds to avoid getting banned.
    |
    */
    'message_delay' => env('WHATSAPP_MESSAGE_DELAY', 2),
];
