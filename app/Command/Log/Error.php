<?php
declare(strict_types = 1);

namespace App\Command\Log;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Log Error Command.
 */
class Error extends AbstractCommand {
  /**
   * Log message.
   *
   * @var string
   */
  public $message;
  /**
   * Log context.
   *
   * @var array
   */
  public $context;
}
