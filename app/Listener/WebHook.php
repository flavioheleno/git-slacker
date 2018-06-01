<?php
declare(strict_types = 1);

namespace App\Listener;

use App\Event\WebHook\Received;
use Interop\Container\ContainerInterface;
use League\Event\EventInterface;
use Monolog\Logger;

class WebHook extends AbstractListener {
  /**
   * Logger instance.
   *
   * @var \Monolog\Logger
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : ListenerInterface {
      $log = $container->get('log');

      return new \App\Listener\WebHook($log('WebHook'));
    };
  }

  /**
   * Class constructor.
   *
   * @param \Monolog\Logger $logger
   *
   * @return void
   */
  public function __construct(Logger $logger) {
    $this->logger = $logger;
  }

  /**
   * Handles the event.
   *
   * @param \League\Event\EventInterface $event
   *
   * @return void
   */
  public function handle(EventInterface $event) {
    if (is_a($event, Received::class)) {
      $this->logger->debug(
        'WebHook received',
        [
          'provider' => $event->providerName,
          'trigger'  => $event->trigger,
          'payload'  => $event->payload
        ]
      );
    }
  }
}
