<?php
declare(strict_types = 1);

namespace App\Command\Slack;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Slack Commands command.
 */
class Commands extends AbstractCommand {
  /**
   * Commands payload.
   *
   * @var array
   */
  public $payload;
}
