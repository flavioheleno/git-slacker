<?php
declare(strict_types = 1);

namespace App\Handler;

use App\Command\Log\Alert;
use App\Command\Log\Critical;
use App\Command\Log\Debug;
use App\Command\Log\Emergency;
use App\Command\Log\Error;
use App\Command\Log\Info;
use App\Command\Log\Notice;
use App\Command\Log\Warning;
use Monolog\Logger;
use Interop\Container\ContainerInterface;

/**
 * Handles Log writing.
 */
class Log implements HandlerInterface {
  private $logger;

  /**
   * Dependency Container registration.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : HandlerInterface {
      $logger = $container->get('log');
      return new \App\Handler\Log(
        $logger('Application')
      );
    };
  }

  /**
   * Class constructor.
   *
   * @param \Monolog\Logger $logger
   *
   * @return void
   */
  public function __construct(Logger $logger) {
    $this->logger = $logger;
  }

  /**
   * Handles an Alert message.
   *
   * @param \App\Command\Log\Alert $command
   *
   * @return void
   */
  public function handleAlert(Alert $command) : void {
    $this->logger->alert($command->message, $command->context ?: []);
  }

  /**
   * Handles a Critical message.
   *
   * @param \App\Command\Log\Critical $command
   *
   * @return void
   */
  public function handleCritical(Critical $command) : void {
    $this->logger->critical($command->message, $command->context ?: []);
  }

  /**
   * Handles a Debug message.
   *
   * @param \App\Command\Log\Debug $command
   *
   * @return void
   */
  public function handleDebug(Debug $command) : void {
    $this->logger->debug($command->message, $command->context ?: []);
  }

  /**
   * Handles an Emergency message.
   *
   * @param \App\Command\Log\Emergency $command
   *
   * @return void
   */
  public function handleEmergency(Emergency $command) : void {
    $this->logger->emergency($command->message, $command->context ?: []);
  }

  /**
   * Handles an Error message.
   *
   * @param \App\Command\Log\Error $command
   *
   * @return void
   */
  public function handleError(Error $command) : void {
    $this->logger->error($command->message, $command->context ?: []);
  }

  /**
   * Handles an Info message.
   *
   * @param \App\Command\Log\Info $command
   *
   * @return void
   */
  public function handleInfo(Info $command) : void {
    $this->logger->info($command->message, $command->context ?: []);
  }

  /**
   * Handles a Notice message.
   *
   * @param \App\Command\Log\Notice $command
   *
   * @return void
   */
  public function handleNotice(Notice $command) : void {
    $this->logger->notice($command->message, $command->context ?: []);
  }

  /**
   * Handles a Warning message.
   *
   * @param \App\Command\Log\Warning $command
   *
   * @return void
   */
  public function handleWarning(Warning $command) : void {
    $this->logger->warning($command->message, $command->context ?: []);
  }
}
