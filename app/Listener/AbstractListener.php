<?php
declare(strict_types = 1);

namespace App\Listener;

use Interop\Container\ContainerInterface;
use League\Event\AbstractListener as AbstractLeagueListener;

/**
* Abstract Listener Implementation.
*/
abstract class AbstractListener extends AbstractLeagueListener implements ListenerInterface {
  /**
   * Registers the Listeners on the Application Container.
   *
   * @param \Interop\Container\ContainerInterface
   *
   * @return void
   */
  abstract public static function register(ContainerInterface $container) : void;
}
