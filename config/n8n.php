<?php

return [
    'api_key' => env('N8N_API_KEY'),
    'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
    'unified_webhook_url' => env('N8N_UNIFIED_WEBHOOK_URL'),
    'chat_message_webhook_url' => env('N8N_CHAT_MESSAGE_WEBHOOK_URL'),
    'hitl_webhook_url' => env('N8N_HITL_WEBHOOK_URL'),
    'timeout' => env('N8N_WEBHOOK_TIMEOUT', 30),
    'retry_attempts' => env('N8N_WEBHOOK_RETRY_ATTEMPTS', 3),
    'verify_signatures' => true,
];