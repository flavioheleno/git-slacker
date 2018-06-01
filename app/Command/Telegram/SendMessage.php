<?php
declare(strict_types = 1);

namespace App\Command\Telegram;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
 * Telegram SendMessage command.
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
