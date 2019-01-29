<?php
declare(strict_types = 1);

namespace App\Handler;

use App\Command\GitHub\WebHook;
use App\Exception\AppException;
use App\Factory\Command;
use App\Factory\Event;
use App\Helper\LoggerTrait;
use App\Helper\Mapper;
use App\Helper\MessengerTrait;
use Interop\Container\ContainerInterface;
use League\Event\Emitter;
use League\Tactician\CommandBus;

/**
 * Handles GitHub commands.
 */
class GitHub implements HandlerInterface {
  use LoggerTrait, MessengerTrait;

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
   * User mapper.
   *
   * @var \App\Helper\Mapper
   */
  private $mapper;
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
   * Dependency Container registration.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : HandlerInterface {
      return new \App\Handler\GitHub(
        $container->get('commandBus'),
        $container->get('commandFactory'),
        $container->get('secrets'),
        $container->get('mapper'),
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
   * @param \App\Helper\Mapper $mapper
   * @param \App\Factory\Event $eventFactory
   * @param \League\Event\Emitter $emitter
   *
   * @return void
   */
  public function __construct(
    CommandBus $commandBus,
    Command $commandFactory,
    array $secrets,
    Mapper $mapper,
    Event $eventFactory,
    Emitter $emitter
  ) {
    $this->commandBus     = $commandBus;
    $this->commandFactory = $commandFactory;
    $this->secrets        = $secrets;
    $this->mapper         = $mapper;
    $this->eventFactory   = $eventFactory;
    $this->emitter        = $emitter;
  }

  /**
   * Handles WebHook command.
   *
   * @param \App\Command\GitHub\WebHook $command
   *
   * @return void
   */
  public function handleWebHook(WebHook $command) : void {
    if ($command->eventName === null || $command->requestSignature === null) {
      $this->logger(
        'Log\\Warning',
        'GitHub:WebHook->Callback: Invalid request format',
        $request->getParsedBody() ?: []
      );

      throw new AppException('Invalid request format');
    }

    $signature = hash_hmac('sha1', $command->raw, $this->secrets['GITHUB_WEBHOOK_SECRET'], false);
    if (! hash_equals(sprintf('sha1=%s', $signature), $command->requestSignature)) {
      $this->logger(
        'Log\\Warning',
        'GitHub:WebHook->Callback: Invalid request signature',
        [
          'signature'        => $signature,
          'requestSignature' => $command->requestSignature,
          'payload'          => $command->payload
        ]
      );

      throw new AppException('Invalid request signature');
    }

/*

XXX: DISABLED FOR NOW, MUST REVIEW LATER

    switch ($command->eventName) {
      case 'pull_request':
        switch ($command->payload['action']) {
          case 'review_requested':
            $message = sprintf(
              'Hey, %s acabou de pedir a sua revisão em um <%s|Pull Request #%d>!',
              $this->mapper->githubOrSlackQuote(
                $command->payload['sender']['login'],
                $command->payload['sender']['html_url']
              ),
              $command->payload['pull_request']['html_url'],
              $command->payload['pull_request']['number']
            );

            $recipient = $this->mapper->githubToSlack($command->payload['requested_reviewer']['login']);
            $this->sendMessageOnSlack($message, $recipient);
            break;
          case 'synchronize':
            // $message = sprintf(
            //   'Hey, %s acabou de atualizar o <%s|Pull Request #%d>!',
            //   $this->mapper->githubOrSlackQuote(
            //     $command->payload['sender']['login'],
            //     $command->payload['sender']['html_url']
            //   ),
            //   $command->payload['pull_request']['html_url'],
            //   $command->payload['pull_request']['number']
            // );

            // $recipient = $this->mapper->githubToSlack($command->payload['requested_reviewer']['login']);
            // $this->sendMessageOnSlack($message, $recipient);
            break;
          case 'closed':
            if ($command->payload['pull_request']['merged_at'] === null) {
              $message = sprintf(
                'Hey, %s acabou de fechar o <%s|Pull Request #%d>!',
                $this->mapper->githubOrSlackQuote(
                  $command->payload['sender']['login'],
                  $command->payload['sender']['html_url']
                ),
                $command->payload['pull_request']['html_url'],
                $command->payload['pull_request']['number']
              );
            } else {
              $message = sprintf(
                'Hey, %s acabou de dar merge no <%s|Pull Request #%d>!',
                $this->mapper->githubOrSlackQuote(
                  $command->payload['sender']['login'],
                  $command->payload['sender']['html_url']
                ),
                $command->payload['pull_request']['html_url'],
                $command->payload['pull_request']['number']
              );
            }

            $recipient = $this->mapper->githubToSlack($command->payload['pull_request']['user']['login']);
            $this->sendMessageOnSlack($message, $recipient);
            break;
        }

        break;
      case 'pull_request_review':
        switch ($command->payload['action']) {
          case 'submitted':
            switch ($command->payload['review']['state']) {
              case 'commented':
                $message = sprintf(
                  'Hey, %s acabou de comentar no seu <%s|Pull Request #%d>!',
                  $this->mapper->githubOrSlackQuote(
                    $command->payload['sender']['login'],
                    $command->payload['sender']['html_url']
                  ),
                  $command->payload['pull_request']['html_url'],
                  $command->payload['pull_request']['number']
                );
                break;
              case 'approved':
                $message = sprintf(
                  'Hey, %s acabou de aprovar o seu <%s|Pull Request #%d>!',
                  $this->mapper->githubOrSlackQuote(
                    $command->payload['sender']['login'],
                    $command->payload['sender']['html_url']
                  ),
                  $command->payload['pull_request']['html_url'],
              $command->payload['pull_request']['number']
                );
                break;
              case 'changes_requested':
                $message = sprintf(
                  'Hey, %s acabou de pedir alterações no seu <%s|Pull Request #%d>!',
                  $this->mapper->githubOrSlackQuote(
                    $command->payload['sender']['login'],
                    $command->payload['sender']['html_url']
                  ),
                  $command->payload['pull_request']['html_url'],
                  $command->payload['pull_request']['number']
                );
                break;
            }

            $recipient = $this->mapper->githubToSlack($command->payload['pull_request']['user']['login']);
            $this->sendMessageOnSlack($message, $recipient);
            break;
        }

        break;
    }
*/

    $event = $this->eventFactory->create(
      'WebHook\\Received',
      'GitHub',
      $command->eventName,
      $command->payload
    );

    $this->emitter->emit($event);
  }
}
