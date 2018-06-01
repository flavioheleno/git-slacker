<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Factory\Command;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles requests to /oauth/{providerName}
 */
class OAuth implements ControllerInterface {
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
   * OAuth Client Factory.
   *
   * @var callable
   */
  private $oauthFactory;
  /**
   * OAuth Scope List.
   *
   * @var array
   */
  private $scopeList;

  /**
   * Class constructor.
   *
   * @param \League\Tactician\CommandBus $commandBus
   * @param \App\Factory\Command $commandFactory
   * @param callable $oauthFactory
   * @param array $scopeList
   *
   * @return void
   */
  public function __construct(
    CommandBus $commandBus,
    Command $commandFactory,
    callable $oauthFactory,
    array $scopeList
  ) {
    $this->commandBus = $commandBus;
    $this->commandFactory = $commandFactory;
    $this->oauthFactory = $oauthFactory;
    $this->scopeList = $scopeList;
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
    $command = $this->commandFactory->create('Log\\Info');
    $command
      ->setParameter('message', 'OAUTH->Callback')
      ->setParameter('context', $request->getParsedBody() ?: []);

    $this->commandBus->handle($command);

    $providerName = strtolower($request->getAttribute('providerName'));

    // workaround
    $oauthFactory = $this->oauthFactory;
    $provider = $oauthFactory($providerName);

    $queryParams = $request->getQueryParams();
    if (! isset($queryParams['code'])) {
      switch ($providerName) {
        case 'github':
          $scopes = explode(',', trim($this->scopeList['GITHUB_SCOPES'], ','));
          break;
        case 'slack':
          $scopes = explode(',', trim($this->scopeList['SLACK_SCOPES'], ','));
          break;
        default:
          throw new \RuntimeException(sprintf('Invalid Provider "%s"', $providerName));
      }
        
      $authorizationUrl = $provider->getAuthorizationUrl(['scope' => implode(' ', array_map('trim', $scopes))]);
      $_SESSION['oauth2state'] = $provider->getState();
      return $response->withHeader('Location', $authorizationUrl);
    }

    if (empty($queryParams['state']) || (isset($_SESSION['oauth2state']) && $queryParams['state'] !== $_SESSION['oauth2state'])) {
      if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
      }

      throw new \RuntimeException('Invalid oAuth State');
    }

    try {
      $accessToken = $provider->getAccessToken(
        'authorization_code',
        [
          'code' => $queryParams['code']
        ]
      );

      $body = [
        'accessToken' => $accessToken->getToken(),
        'expiresAt'   => $accessToken->getExpires()
      ];

      $command = $this->commandFactory->create('Response\\Success');
      $command
        ->setParameter('request', $request)
        ->setParameter('response', $response)
        ->setParameter('body', $body);

      return $this->commandBus->handle($command);
    } catch (IdentityProviderException $exception) {
      throw new \RuntimeException('');
    }
  }
}
