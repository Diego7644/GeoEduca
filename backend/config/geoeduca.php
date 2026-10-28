<?php

return [
    'recovery' => [
        'ttl_minutes' => (int) env('GEO_RECOVERY_TTL', 15),
        'max_attempts' => (int) env('GEO_RECOVERY_MAX_ATTEMPTS', 5),
        'dev_reveal' => (bool) env('GEO_RECOVERY_DEV_REVEAL', false),
    ],
];
