<?php
declare(strict_types = 1);

namespace App\Event\WebHook;

use App\Event\AbstractEvent;

/**
 * WebHook Received event.
 */
class Received extends AbstractEvent {
  /**
   * Provider name.
   *
   * @var string
   */
  public $providerName;
  /**
   * Trigger name.
   *
   * @var string
   */
  public $trigger;
  /**
   * WebHook Payload.
   *
   * @var array
   */
  public $payload;

  /**
   * Class constructor.
   *
   * @param string $providerName
   * @param string $trigger
   * @param array $payload
   *
   * @return void
   */
  public function __construct(string $providerName, string $trigger, array $payload) {
    $this->providerName = $providerName;
    $this->trigger      = $trigger;
    $this->payload      = $payload;
  }
}
