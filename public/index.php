<?php
declare(strict_types = 1);

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');

define('__ROOT__', __DIR__ . '/..');

require_once __ROOT__ . '/vendor/autoload.php';

// Loads .env if available
if (is_file(__ROOT__ . '/.env')) {
    $dotEnv = new Dotenv\Dotenv(__ROOT__);
    $dotEnv->load();
}

// Load application settings
require_once __ROOT__ . '/config/settings.php';

// Increase error reporting
if ($appSettings['debug']) {
    ini_set('display_errors', 'On');
    error_reporting(-1);
}

// Application Setup
$app = new Slim\App(
    ['settings' => $appSettings]
);

// Dependency Injection
require_once __ROOT__ . '/config/dependencies.php';

// Application Middleware
require_once __ROOT__ . '/config/middleware.php';

// Command Handlers
require_once __ROOT__ . '/config/handlers.php';

// Route definition
require_once __ROOT__ . '/config/routes.php';

// Event Listeners
require_once __ROOT__ . '/config/listeners.php';

// Listener Providers
require_once __ROOT__ . '/config/providers.php';

// Application Execution
$app->run();
// var_dump($_GET, $_SERVER, $app);
