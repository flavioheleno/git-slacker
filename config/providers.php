<?php
declare(strict_types = 1);

if (! isset($app)) {
    die('$app is not set!');
}

/**
 * This file is responsible for initializing the event emitter
 * variable that will be inject through the application.
 **/
$container = $app->getContainer();
$settings  = $container->get('settings');

$classList = [];
if ((! empty($settings['boot']['providersCache'])) && (is_file($settings['boot']['providersCache']))) {
  $cache = file_get_contents($settings['boot']['providersCache']);
  if ($cache !== false) {
    $cache = unserialize($cache);
  }

  if ($cache !== false) {
    $classList = $cache;
  }
}

if (empty($classList)) {
  $providerFiles = new RegexIterator(
    new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        __ROOT__ . '/app/Provider/'
      )
    ),
    '/^.+\.php$/i',
    RecursiveRegexIterator::MATCH
  );

  $pathLen = strlen(__ROOT__ . '/app/Provider/');
  foreach ($providerFiles as $providerFile) {
    if (strpos($providerFile->getBasename(), 'Abstract') !== false) {
      continue;
    }

    if (strpos($providerFile->getBasename(), 'Interface') !== false) {
      continue;
    }

    $className = str_replace(
      ['/', '.php'],
      ['\\', ''],
      sprintf(
        'App\\Provider\\%s',
        substr(
          $providerFile->getPathname(),
          $pathLen
        )
      )
    );

    $classList[] = $className;
  }

  if (! empty($settings['boot']['providersCache'])) {
    file_put_contents($settings['boot']['providersCache'], serialize($classList));
  }
}

$emitter = $container->get('eventEmitter');
if (empty($emitter->listeners)) {
  foreach ($classList as $className) {
    $emitter->useListenerProvider(new $className($container));
  }
}
