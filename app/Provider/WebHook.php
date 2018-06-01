<?php
declare(strict_types = 1);

namespace App\Provider;

use App\Event\WebHook\Received;
use App\Listener\WebHook as WebHookListener;
use Interop\Container\ContainerInterface;
use Refinery29\Event\LazyListener;

class WebHook extends AbstractProvider {
  /**
   * Class constructor.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public function __construct(ContainerInterface $container) {
    $this->events = [
      Received::class => [
        LazyListener::fromAlias(
          WebHookListener::class,
          $container
        )
      ]
    ];
  }
}
