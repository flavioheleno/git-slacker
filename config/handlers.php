<?php
declare(strict_types = 1);

if (! isset($app)) {
  die('$app is not set!');
}

$container = $app->getContainer();
$settings  = $container->get('settings');

$classList = [];
if ((! empty($settings['boot']['handlersCache'])) && (is_file($settings['boot']['handlersCache']))) {
  $cache = file_get_contents($settings['boot']['handlersCache']);
  if ($cache !== false) {
    $cache = unserialize($cache);
  }

  if ($cache !== false) {
    $classList = $cache;
  }
}

if (empty($classList)) {
  $handlerFiles = new RegexIterator(
    new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        __ROOT__ . '/app/Handler/'
      )
    ),
    '/^.+\.php$/i',
    RecursiveRegexIterator::MATCH
  );

  $pathLen = strlen(__ROOT__ . '/app/Handler/');
  foreach ($handlerFiles as $handlerFile) {
    if (strpos($handlerFile->getBasename(), 'Abstract') !== false) {
      continue;
    }

    if (strpos($handlerFile->getBasename(), 'Interface') !== false) {
      continue;
    }

    $className = str_replace(
      ['/', '.php'],
      ['\\', ''],
      sprintf(
        'App\\Handler\\%s',
        substr(
          $handlerFile->getPathname(),
          $pathLen
        )
      )
    );

    $classList[] = $className;
  }

  if (! empty($settings['boot']['handlersCache'])) {
    file_put_contents($settings['boot']['handlersCache'], serialize($classList));
  }
}

foreach ($classList as $className) {
  if (class_exists($className)) {
    $className::register($container);
  }
}
