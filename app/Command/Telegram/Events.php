<?php
declare(strict_types = 1);

namespace App\Command\Telegram;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Telegram Events command.
 */
class Events extends AbstractCommand {
  /**
   * Secure token.
   *
   * @var string
   */
  public $secureToken;
  /**
   * Events payload.
   *
   * @var array
   */
  public $payload;
}
