<?php
declare(strict_types = 1);

use Slim\HttpCache\Cache;

if (! isset($app)) {
  die('$app is not set!');
}

$app->add(new Cache('private, no-cache, no-store', 0, true));
