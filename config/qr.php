<?php

return ['public_base_url' => rtrim(env('PUBLIC_BASE_URL', 'http://localhost:5173'), '/'), 'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'), 'max_links' => (int) env('QR_MAX_LINKS', 20), 'event_retention_days' => (int) env('QR_EVENT_RETENTION_DAYS', 90), 'analytics_hash_key' => env('QR_ANALYTICS_HASH_KEY')];
