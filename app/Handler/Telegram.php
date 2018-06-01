<?php
declare(strict_types = 1);

namespace App\Handler;

use App\Command\Telegram\Events;
use App\Command\Telegram\SendMessage;
use App\Exception\AppException;
use App\Factory\Command;
use App\Factory\Event;
use App\Helper\LoggerTrait;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\TelegramDriver;
use Interop\Container\ContainerInterface;
use League\Event\Emitter;
use League\Tactician\CommandBus;

/**
 * Handles Telegram interactions.
 */
class Telegram implements HandlerInterface {
  use LoggerTrait;

  /**
   * Command Bus instance.
   *
   * @var \League\Tactician\CommandBus
   */
  private $commandBus;
  /**
   * Command Factory instance.
   *
   * @var \App\Factory\Command
   */
  private $commandFactory;
  /**
   * Application secrets.
   *
   * @var array
   */
  private $secrets;
  /**
   * BotMan instance.
   *
   * @var \BotMan\BotMan\BotMan
   */
  private $botman;
  /**
   * Event factory instance.
   *
   * @var \App\Factory\Event
   */
  private $eventFactory;
  /**
   * Event emitter instance.
   *
   * @var \League\Event\Emitter
   */
  private $emitter;

  /**
   * Validates the command secure token.
   *
   * Checks for the presence and value of a shared token.
   *
   * @param string $secureToken
   *
   * @return void
   */
  private function validateCommandToken(string $secureToken) : void {
    if (empty($secureToken)) {
      $this->logger(
        'Log\\Warning',
        'Telegram:BOT: Empty secure token'
      );

      throw new AppException('Empty secure token');
    }

    if (! hash_equals($this->secrets['TELEGRAM_WEBHOOK_TOKEN'], $secureToken)) {
      $this->logger(
        'Log\\Warning',
        'Telegram:BOT: Invalid secure token',
        ['secureToken' => $secureToken]
      );

      throw new AppException('Invalid secure token');
    }
  }

  /**
   * Dependency Container registration.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : HandlerInterface {
      return new \App\Handler\Telegram(
        $container->get('commandBus'),
        $container->get('commandFactory'),
        $container->get('secrets'),
        $container->get('botman'),
        $container->get('eventFactory'),
        $container->get('eventEmitter')
      );
    };
  }

  /**
   * Class constructor.
   *
   * @param \League\Tactician\CommandBus $commandBus
   * @param \App\Factory\Command $commandFactory
   * @param array $secrets
   * @param \BotMan\BotMan\BotMan $botman
   * @param \App\Factory\Event $eventFactory
   * @param \League\Event\Emitter $emitter
   *
   * @return void
   */
  public function __construct(
    CommandBus $commandBus,
    Command $commandFactory,
    array $secrets,
    BotMan $botman,
    Event $eventFactory,
    Emitter $emitter
  ) {
    $this->commandBus     = $commandBus;
    $this->commandFactory = $commandFactory;
    $this->secrets        = $secrets;
    $this->botman         = $botman;
    $this->eventFactory   = $eventFactory;
    $this->emitter        = $emitter;
  }

  /**
   * Handles sending message to Telegram.
   *
   * @param \App\Command\Telegram\SendMessage $command
   *
   * @return void
   */
  public function handleSendMessage(SendMessage $command) : void {
    $this->botman->say(
      $command->message,
      $command->recipient,
      TelegramDriver::class
    );
  }

  /**
   * Handles Telegram events.
   *
   * @param \App\Command\Telegram\Events $command
   *
   * @return void
   */
  public function handleEvents(Events $command) : void {
    $this->validateCommandToken($command->secureToken);

    $this->botman->hears('ping', function ($bot) use ($command) {
      $bot->reply(print_r($command, true));
    });

    $this->botman->hears('x', function ($bot) {
      $bot->reply('y');
    });

    $this->botman->listen();

    $this->logger(
      'Log\\Debug',
      'events',
      $command->payload
    );
  }
}
