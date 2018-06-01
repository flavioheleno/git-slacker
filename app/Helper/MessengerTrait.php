<?php
declare(strict_types = 1);

namespace App\Helper;

trait MessengerTrait {
  /**
   * Sends a message to Slack.
   *
   * @param string $message
   * @param string $recipient
   *
   * @return void
   */
  private function sendMessageOnSlack(string $message, string $recipient) : void {
    $command = $this->commandFactory->create('Slack\\SendMessage');
    $command
      ->setParameter('message', $message)
      ->setParameter('recipient', $recipient);

    $this->commandBus->handle($command);
  }

  /**
   * Sends a message to Telegram.
   *
   * @param string $message
   * @param string $recipient
   *
   * @return void
   */
  private function sendMessageOnTelegram(string $message, string $recipient) : void {
    $command = $this->commandFactory->create('Telegram\\SendMessage');
    $command
      ->setParameter('message', $message)
      ->setParameter('recipient', $recipient);

    $this->commandBus->handle($command);
  }
}
