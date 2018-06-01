<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Exception\AppException;
use App\Factory\Command;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles requests to /bot/{providerName}
 */
class Bot implements ControllerInterface {
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
   * Class constructor.
   *
   * @param \League\Tactician\CommandBus $commandBus
   * @param \App\Factory\Command $commandFactory
   *
   * @return void
   */
  public function __construct(CommandBus $commandBus, Command $commandFactory) {
    $this->commandBus     = $commandBus;
    $this->commandFactory = $commandFactory;
  }

  /**
   * FIXME: Add a description
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   * @param \Psr\Http\Message\ResponseInterface      $response
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function commands(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
    $providerName = $request->getAttribute('providerName');

    switch ($providerName) {
      case 'slack':
        $command = $this->commandFactory->create('Slack\\Commands');
        $command
          ->setParameter('payload', $request->getParsedBody() ?: []);

        $this->commandBus->handle($command);
        break;
      default:
        throw new AppException('Unknown service provider');
    }

    $command = $this->commandFactory->create('Response\\Success');
    $command
      ->setParameter('request', $request)
      ->setParameter('response', $response);

    return $this->commandBus->handle($command);
  }

  /**
   * FIXME: Add a description
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   * @param \Psr\Http\Message\ResponseInterface      $response
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function events(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
    $providerName = $request->getAttribute('providerName');

    switch ($providerName) {
      case 'slack':
        $command = $this->commandFactory->create('Slack\\Events');
        $command
          ->setParameter('payload', $request->getParsedBody() ?: []);

        $this->commandBus->handle($command);
        break;
      case 'telegram':
        $command = $this->commandFactory->create('Telegram\\Events');
        $command
          ->setParameter('secureToken', $request->getAttribute('secureToken'))
          ->setParameter('payload', $request->getParsedBody() ?: []);

        $this->commandBus->handle($command);
        break;
      default:
        throw new AppException('Unknown service provider');
    }

    $command = $this->commandFactory->create('Response\\Success');
    $command
      ->setParameter('request', $request)
      ->setParameter('response', $response);

    return $this->commandBus->handle($command);
  }
}
