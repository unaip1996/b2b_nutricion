<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Ejecuta comandos para preparar la base de datos de test
echo "Preparando base de datos de test...\n";
passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:drop --env=test --force --if-exists', __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:create --env=test', __DIR__
));
echo "Creando extensión 'vector' en la base de datos de test...\n";
passthru(sprintf(
    'php "%s/../bin/console" doctrine:dbal:run-sql "CREATE EXTENSION IF NOT EXISTS vector" --env=test', __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:create --env=test', __DIR__
));
echo "Base de datos de test lista.\n";
