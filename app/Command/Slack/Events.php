<?php
declare(strict_types = 1);

namespace App\Command\Slack;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Slack Events command.
 */
class Events extends AbstractCommand {
  /**
   * Events payload.
   *
   * @var array
   */
  public $payload;
}
