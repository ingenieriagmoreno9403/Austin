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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AutinApi (SQL Server / catálogos SAP)
    |--------------------------------------------------------------------------
    | El ERP sigue usando MySQL local (DB_*). Estas credenciales solo se usan
    | en pantallas que consultan centros de costo, cuentas SAP, etc.
    */
    'autin_api' => [
        'base_url' => env('AUTIN_API_BASE_URL', 'http://187.237.178.149/api'),
        'username' => env('AUTIN_API_USER'),
        'password' => env('AUTIN_API_PASSWORD'),
        'default_db' => env('AUTIN_API_DEFAULT_DB', 'austin'),
        'timeout' => env('AUTIN_API_TIMEOUT', 30),
        'connect_timeout' => env('AUTIN_API_CONNECT_TIMEOUT', 10),
    ],

];
