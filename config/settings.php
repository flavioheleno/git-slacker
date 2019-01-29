<?php
declare(strict_types = 1);

use App\Helper\Env;

if (! defined('__VERSION__')) {
  define('__VERSION__', Env::asString('GIT_SLACKER_VERSION', '1.0'));
}

if (! defined('__ROOT__')) {
  define('__ROOT__', __DIR__ . '/..');
}

$appSettings = [
  'debug'                             => Env::asBool('SLIM_DEBUG', false),
  'displayErrorDetails'               => Env::asBool('SLIM_DEBUG', false),
  'routerCacheFile'                   => Env::asString('SLIM_ROUTER_CACHE', '') ?: false,
  'determineRouteBeforeAppMiddleware' => true,
  'boot'                              => [
    'commandsCache'  => Env::asString('SLIM_COMMANDS_CACHE', ''),
    'handlersCache'  => Env::asString('SLIM_HANDLERS_CACHE', ''),
    'listenersCache' => Env::asString('SLIM_LISTENERS_CACHE', ''),
    'providersCache' => Env::asString('SLIM_PROVIDERS_CACHE', ''),
    'routesCache'    => Env::asString('SLIM_ROUTES_CACHE', '')
  ],
  'log' => [
    'path' => Env::asString(
      'SLIM_LOG_FILE',
      sprintf(
        '%s/log/application.log',
        __ROOT__
      )
    ),
    'level' => Env::asBool('SLIM_DEBUG', false) ? Monolog\Logger::DEBUG : Monolog\Logger::INFO
  ],
  'fileSystem' => [
    'adapter' => Env::asString('SLIM_FS_ADAPTER', ''),
    'cached'  => Env::asBool('SLIM_FS_CACHED', false),
    'path'    => Env::asString('SLIM_FS_PATH', '/tmp')
  ]
];
