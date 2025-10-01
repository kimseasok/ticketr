<?php

return [
    'default_token' => env('MONITORING_TOKEN', 'changeme-monitor'),
    'allowed_ips' => explode(',', env('MONITORING_ALLOWED_IPS', '')), // optional IP restriction for health endpoint
];
