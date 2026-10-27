<?php

return [
    'otp' => [
        'login_sms' => 'Your login code is :code. It expires in :minutes minutes.',
        'reset_sms' => 'Your password reset code is :code. It expires in :minutes minutes.',
        'login_email_subject' => ':app sign-in code',
        'reset_email_subject' => ':app password reset code',
        'login_title' => 'Your sign-in code',
        'reset_title' => 'Reset your password',
        'login_intro' => 'Use the one-time code below to finish signing in to your :app account.',
        'reset_intro' => 'We received a request to reset the password for your :app account. Use the one-time code below to continue.',
        'login_outro' => 'If you did not try to sign in, you can safely ignore this email.',
        'reset_outro' => 'If you did not request a password reset, you can safely ignore this email. Your password will stay the same.',
        'login_action' => 'Open :app',
        'reset_action' => 'Continue password reset',
        'code_label' => 'One-time code',
    ],

    'library_alert' => [
        'email_preheader' => ':title — :body',
        'sms' => ':title: :body',
    ],
];
