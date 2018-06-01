<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Factory\Command;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles requests to /setup/{providerName}
 */
class Setup implements ControllerInterface {
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
   * @param \App\Factory\Command
   *
   * @return void
   */
  public function __construct(
    CommandBus $commandBus,
    Command $commandFactory
  ) {
    $this->commandBus = $commandBus;
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
  public function Index(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
    $command = $this->commandFactory->create('Log\\Info');
    $command
      ->setParameter('message', 'SETUP->Index')
      ->setParameter('context', $request->getParsedBody() ?: []);

    $this->commandBus->handle($command);

    $command = $this->commandFactory->create('Response\\Success');
    $command
      ->setParameter('request', $request)
      ->setParameter('response', $response)
      ->setParameter('body', ['message' => 'oi']);

    return $this->commandBus->handle($command);
  }
}
