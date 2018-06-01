<?php
declare(strict_types = 1);

namespace App\Handler;

use App\Command\Slack\Commands;
use App\Command\Slack\Events;
use App\Command\Slack\SendMessage;
use App\Exception\AppException;
use App\Factory\Command;
use App\Factory\Event;
use App\Helper\LoggerTrait;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Slack\SlackDriver;
use Interop\Container\ContainerInterface;
use League\Event\Emitter;
use League\Tactician\CommandBus;

/**
 * Handles Slack interactions.
 */
class Slack implements HandlerInterface {
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
   * Validates the command payload.
   *
   * Checks for the presence and value of a shared token.
   *
   * @param string $type
   * @param array $payload
   *
   * @return void
   */
  private function validateCommandPayload(string $type, array $payload) : void {
    if (! isset($payload['token'])) {
      $this->logger(
        'Log\\Warning',
        sprintf('Slack:BOT->%s: Invalid request format', $type),
        $payload
      );

      throw new AppException('Invalid request format');
    }

    if (! hash_equals($this->secrets['SLACK_WEBHOOK_TOKEN'], $payload['token'])) {
      $this->logger(
        'Log\\Warning',
        sprintf('Slack:BOT->%s: Invalid request signature: %s', $type, $payload['token']),
        $payload
      );

      throw new AppException('Invalid request signature');
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
      return new \App\Handler\Slack(
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
   * Handles sending message to Slack.
   *
   * @param \App\Command\Slack\SendMessage $command
   *
   * @return void
   */
  public function handleSendMessage(SendMessage $command) : void {
    $this->botman->say(
      $command->message,
      $command->recipient,
      SlackDriver::class
    );
  }

  /**
   * Handles Slack commands.
   *
   * @param \App\Command\Slack\Commands $command
   *
   * @return void
   */
  public function handleCommands(Commands $command) : void {
    $this->validateCommandPayload('Commands', $command->payload);

    $this->logger(
      'Log\\Debug',
      'commands',
      $command->payload
    );
  }

  /**
   * Handles Slack events.
   *
   * @param \App\Command\Slack\Events $command
   *
   * @return void
   */
  public function handleEvents(Events $command) : void {
    $this->validateCommandPayload('Events', $command->payload);

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
