<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Actor Configuration for Fee Invoice Generation
    |--------------------------------------------------------------------------
    |
    | Used by FeeGenerationService when running unattended in console or
    | background schedules where no authenticated web session exists.
    |
    */
    'system_actor_id'    => env('FEE_SYSTEM_ACTOR_ID', null),
    'system_actor_email' => env('FEE_SYSTEM_ACTOR_EMAIL', 'admin@macsschool.edu.bd'),

    /*
    |--------------------------------------------------------------------------
    | Authorized Administrative Roles for Financial Invoice Generation
    |--------------------------------------------------------------------------
    |
    | Only active users holding at least one of these roles can be resolved
    | as the audit actor for fee invoice creation. Teachers, Students,
    | and ordinary staff are strictly prohibited.
    |
    */
    'authorized_roles' => [
        'Super Admin',
        'Admin',
        'super-admin',
        'admin',
    ],
];

