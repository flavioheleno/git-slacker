<?php
declare(strict_types = 1);

if (! isset($app)) {
  die('$app is not set!');
}

if (! defined('__ROOT__')) {
  define('__ROOT__', __DIR__ . '/..');
}

/**
 * This file is responsible for initializing the event emitter
 * variable that will be inject through the application.
 **/
$container = $app->getContainer();
$settings  = $container->get('settings');

$classList = [];
if ((! empty($settings['boot']['listenersCache'])) && (is_file($settings['boot']['listenersCache']))) {
  $cache = file_get_contents($settings['boot']['listenersCache']);
  if ($cache !== false) {
    $cache = unserialize($cache);
  }

  if ($cache !== false) {
    $classList = $cache;
  }
}

if (empty($classList)) {
  $listenerFiles = new RegexIterator(
    new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        __ROOT__ . '/app/Listener/'
      )
    ),
    '/^.+\.php$/i',
    RecursiveRegexIterator::MATCH
  );

  $pathLen = strlen(__ROOT__ . '/app/Listener/');
  foreach ($listenerFiles as $listenerFile) {
    if (strpos($listenerFile->getBasename(), 'Abstract') !== false) {
      continue;
    }

    if (strpos($listenerFile->getBasename(), 'Interface') !== false) {
      continue;
    }

    $className = str_replace(
      ['/', '.php'],
      ['\\', ''],
      sprintf(
        'App\\Listener\\%s',
        substr(
          $listenerFile->getPathname(),
          $pathLen
        )
      )
    );

    $classList[] = $className;
  }

  if (! empty($settings['boot']['listenersCache'])) {
    file_put_contents($settings['boot']['listenersCache'], serialize($classList));
  }
}

foreach ($classList as $className) {
  if (class_exists($className)) {
    $className::register($container);
  }
}
