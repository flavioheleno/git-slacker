<?php
declare(strict_types = 1);

namespace App\Route;

use App\Controller\ControllerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * WebHook Route
 *
 * Used to handle WebHook calls.
 *
 * @see App\Controller\WebHook
 */
class WebHook implements RouteInterface {
  /**
   * {@inheritdoc}
   */
  public static function register(App $app) : void {
    $app->getContainer()[\App\Controller\WebHook::class] = function (ContainerInterface $container) : ControllerInterface {
      return new \App\Controller\WebHook(
        $container->get('commandBus'),
        $container->get('commandFactory')
      );
    };

    self::callback($app);
  }

  /**
   * Callback handling
   *
   * Handles WebHook callback.
   *
   * @param \Slim\App $app
   *
   * @return void
   *
   * @see App\Controller\WebHook::callback
   */
  private static function callback(App $app) : void {
    $app
      ->post(
        '/webhook/{providerName:[a-z]+}',
        'App\Controller\WebHook:callback'
      )
      ->setName('webhook:callback');
  }
}
