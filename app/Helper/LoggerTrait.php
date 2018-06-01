<?php
declare(strict_types = 1);

namespace App\Helper;

trait LoggerTrait {
  /**
   * Runs a logger command.
   *
   * @param string $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  private function logger(string $level, string $message, array $context = []) : void {
    $command = $this->commandFactory->create($level);
    $command
      ->setParameter('message', $message)
      ->setParameter('context', $context);

    $this->commandBus->handle($command);
  }
}
