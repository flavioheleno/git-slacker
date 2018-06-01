<?php
declare(strict_types = 1);

namespace App\Command\Slack;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Slack SendMessage command.
 */
class SendMessage extends AbstractCommand {
  /**
   * Message content.
   *
   * @var string
   */
  public $message;
  /**
   * Message recipient.
   *
   * @var string
   */
  public $recipient;
}
