<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Exception\AppException;
use App\Factory\Command;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles requests to /webhook/{providerName}
 */
class WebHook implements ControllerInterface {
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
  public function callback(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
    $providerName = $request->getAttribute('providerName');

    switch ($providerName) {
      case 'github':
        $command = $this->commandFactory->create('GitHub\\WebHook');
        $command
          ->setParameter('eventName', $request->getHeaderLine('HTTP_X_GITHUB_EVENT'))
          ->setParameter('requestSignature', $request->getHeaderLine('HTTP_X_HUB_SIGNATURE'))
          ->setParameter('payload', $request->getParsedBody() ?: [])
          ->setParameter('raw', $request->getBody()->getContents());

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
