<?php

declare(strict_types=1);

/**
 * Configuración central del sistema.
 *
 * Este archivo evita repetir valores como las credenciales de MySQL,
 * el nombre de la sesión y las reglas para cargar imágenes
 *
 * En un servidor real las credenciales deberían almacenarse en variables
 * de entorno y nunca publicarse en GitHub.
 */
return [
    'app' => [
        'name' => 'Mecario - Sistema de Inventario Automotriz',
        'environment' => getenv('APP_ENV') ?: 'development',
        'timezone' => 'America/Panama',
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'mecario',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => 'MECARIO_SESSION',
        'lifetime' => 3600,
        'secure' => false, // En localhost debe permanecer false porque no se usa HTTPS.
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'security' => [
        'max_login_attempts' => 3,
        'lockout_minutes' => 15,
        'rsa_bits' => 2048,
        'openssl_config' => 'D:/AplicacioneSoftware/Xamp/apache/conf/openssl.cnf', 
        // En producción defina MECARIO_KEY_SECRET como variable de entorno.
        'key_encryption_secret' => getenv('MECARIO_KEY_SECRET') ?: 'mecario-local-key-secret-2026',
        'private_keys_directory' => dirname(__DIR__) . '/storage/keys/',
    ],

    'commerce' => [
        // Tarifa fija utilizada por el checkout cuando el cliente elige delivery.
        'delivery_fee' => 5.00,
        // ITBMS aplicado a las partes vendidas.
        'itbms_rate' => 0.07,
    ],

    'business' => [
        'legal_name' => 'Mecario S.A.',
        'address' => 'Panamá, República de Panamá',
        'phone' => '+507 0000-0000',
        'email' => 'ventas@mecario.local',
    ],

    'invoices' => [
        'directory' => dirname(__DIR__) . '/storage/facturas/',
        'certificate' => dirname(__DIR__) . '/storage/certificates/mecario_factura.crt',
        'private_key' => dirname(__DIR__) . '/storage/certificates/mecario_factura_private.pem',
        'private_key_password' => getenv('MECARIO_INVOICE_KEY_PASSWORD') ?: 'mecario-factura-2026',
    ],

    'uploads' => [
        'max_size' => 2 * 1024 * 1024, // 2 MB.
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'thumbnail_directory' => dirname(__DIR__) . '/uploads/thumbnails/',
        'large_directory' => dirname(__DIR__) . '/uploads/grandes/',
    ],

    'errors' => [
        // En development se muestran detalles; en production se ocultan.
        'show_details' => (getenv('APP_ENV') ?: 'development') === 'development',
        'log_file' => dirname(__DIR__) . '/storage/logs/errors.log',
    ],
];
