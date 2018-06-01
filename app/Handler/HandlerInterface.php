<?php
declare(strict_types = 1);

namespace App\Handler;

use Interop\Container\ContainerInterface;

/**
* Handler Interface.
*/
interface HandlerInterface {
  /**
   * Registers the Handler on the Dependency Container.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public static function register(ContainerInterface $container) : void;
}
