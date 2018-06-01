<?php
declare(strict_types = 1);

namespace App\Route;

use App\Controller\ControllerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * OAuth Route
 *
 * Used to retrieve oAuth Tokens after user authentication.
 *
 * @see App\Controller\OAuth
 */
class OAuth implements RouteInterface {
  /**
   * {@inheritdoc}
   */
  public static function register(App $app) : void {
    $app->getContainer()[\App\Controller\OAuth::class] = function (ContainerInterface $container) : ControllerInterface {
      return new \App\Controller\OAuth(
        $container->get('commandBus'),
        $container->get('commandFactory'),
        $container->get('oauth'),
        $container->get('scopes')
      );
    };

    self::callback($app);
  }

  /**
   * Callback handling
   *
   * Handles oAuth callback.
   *
   * @param \Slim\App $app
   *
   * @return void
   *
   * @see App\Controller\OAuth::callback
   */
  private static function callback(App $app) : void {
    $app
      ->get(
        '/oauth/{providerName:[a-z]+}',
        'App\Controller\OAuth:callback'
      )
      ->setName('oauth:callback');
  }
}
