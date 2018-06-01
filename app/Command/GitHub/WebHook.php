<?php
declare(strict_types = 1);

namespace App\Command\GitHub;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * GitHub WebHook Command.
 */
class WebHook extends AbstractCommand {
  /**
   * GitHub Event name.
   *
   * @var string
   */
  public $eventName;
  /**
   * GitHub Request signature.
   *
   * @var string
   */
  public $requestSignature;
  /**
   * WebHook payload.
   *
   * @var array
   */
  public $payload;
  /**
   * WebHook raw body.
   *
   * @var string
   */
  public $raw;
}
