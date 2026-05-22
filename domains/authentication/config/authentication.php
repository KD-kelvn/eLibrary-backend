<?php

return [
    'otp' => [
        'expiry_minutes' => (int) env('AUTH_OTP_EXPIRY_MINUTES', 10),
        'length' => (int) env('AUTH_OTP_LENGTH', 6),
        // Show OTP in API response (useful for local dev). Set false in production.
        'expose_in_response' => env('AUTH_OTP_EXPOSE_IN_RESPONSE', true),
    ],
];
