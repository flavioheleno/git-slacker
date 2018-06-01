<?php
declare(strict_types = 1);

namespace App\Route;

use App\Controller\ControllerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * Bot Route
 *
 * Used to handle Bot calls.
 *
 * @see App\Controller\Bot
 */
class Bot implements RouteInterface {
  /**
   * {@inheritdoc}
   */
  public static function register(App $app) : void {
    $app->getContainer()[\App\Controller\Bot::class] = function (ContainerInterface $container) : ControllerInterface {
      return new \App\Controller\Bot(
        $container->get('commandBus'),
        $container->get('commandFactory'),
        $container->get('secrets')
      );
    };

    self::commands($app);
    self::events($app);
  }

  /**
   * Command handling
   *
   * Handle Bot commands.
   *
   * @param \Slim\App $app
   *
   * @return void
   *
   * @see \App\Controller\Bot::commands
   */
  private static function commands(App $app) : void {
    $app
      ->post(
        '/bot/{providerName:[a-z]+}/commands',
        'App\Controller\Bot:commands'
      )
      ->setName('bot:commands');
  }

  /**
   * Event handling
   *
   * Handles Bot events.
   *
   * @param \Slim\App $app
   *
   * @return void
   *
   * @see \App\Controller\Bot::events
   */
  private static function events(App $app) : void {
    $app
      ->post(
        '/bot/{providerName:[a-z]+}/events[/{secureToken:[a-zA-Z0-9]+}]',
        'App\Controller\Bot:events'
      )
      ->setName('bot:events');
  }
}
