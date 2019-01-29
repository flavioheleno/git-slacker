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
   *
   * @var \PDO
   */
  private $pdo;

  /**
   * {@inheritdoc}
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : ListenerInterface {
      $log = $container->get('log');

      return new \App\Listener\WebHook($log('WebHook'), $container->get('pdo'));
    };
  }

  /**
   * Class constructor.
   *
   * @param \Monolog\Logger $logger
   * @param \PDO $pdo
   *
   * @return void
   */
  public function __construct(Logger $logger, \PDO $pdo) {
    $this->logger = $logger;
    $this->pdo    = $pdo;
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

      $stmt = $this->pdo->prepare('INSERT INTO "webhook_storage" ("event_name", "provider", "payload") VALUES (?, ?, ?)');
      $stmt->execute(
        [
          $event->trigger,
          $event->providerName,
          json_encode($event->payload)
        ]
      );
    }
  }
}
