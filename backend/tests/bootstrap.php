<?php

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';

// Asegurar que las variables de entorno de test prevalezcan sobre las del contenedor.
putenv('MESSENGER_TRANSPORT_DSN=sync://');
$_SERVER['MESSENGER_TRANSPORT_DSN'] = $_ENV['MESSENGER_TRANSPORT_DSN'] = 'sync://';
putenv('MAILER_DSN=null://null');
$_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';
putenv('BIB_EMAIL_DELAY_SECONDS=0');
$_SERVER['BIB_EMAIL_DELAY_SECONDS'] = $_ENV['BIB_EMAIL_DELAY_SECONDS'] = '0';
