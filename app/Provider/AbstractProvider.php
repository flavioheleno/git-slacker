<?php
declare(strict_types = 1);

namespace App\Provider;

use League\Event\ListenerAcceptorInterface;
use League\Event\ListenerProviderInterface;

/**
* Abstract Listener Provider Implementation.
*/
abstract class AbstractProvider implements ListenerProviderInterface {
  /**
   * Associative array defining events and their listeners
   * initialized on constructor.
   *
   * @example array [ 'event' => [ 'listener1', 'listener2'] ]
   *
   * @var array
   */
  protected $events = [];

  /**
   * {@inheritdoc}
   */
  public function provideListeners(ListenerAcceptorInterface $acceptor) {
    foreach ($this->events as $eventName => $listeners) {
      if (count($listeners)) {
        foreach ($listeners as $listener) {
          $acceptor->addListener($eventName, $listener);
        }
      }
    }
  }
}
