<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// CORRECCIÓN: Comprobación segura para evitar el Notice
if ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false) {
    umask(0000);
}

echo "Limpiando la caché de test...\n";
passthru(sprintf(
    'php "%s/../bin/console" cache:clear --env=test --no-warmup', __DIR__
));

echo "Preparando base de datos de test...\n";
passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:drop --env=test --force --if-exists', __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:create --env=test', __DIR__
));

echo "Creando extensión 'vector' en la base de datos de test...\n";
$tempScript = __DIR__ . '/temp_setup.php';
$scriptContent = <<<PHP
<?php
require __DIR__.'/../vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
\$_SERVER['APP_ENV'] = 'test';
\$kernel = new App\Kernel('test', true);
\$kernel->boot();
\$kernel->getContainer()->get('doctrine')->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');
PHP;
file_put_contents($tempScript, $scriptContent);
passthru(sprintf('php "%s"', $tempScript));
unlink($tempScript);

echo "Creando esquema de tablas...\n";
passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:create --env=test', __DIR__
));
echo "Base de datos de test lista.\n";