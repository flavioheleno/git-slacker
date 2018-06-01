<?php
declare(strict_types = 1);

namespace App\Route;

use App\Controller\ControllerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * Setup Route
 *
 * Used to setup additional items after user has installed the application.
 *
 * @see App\Controller\Setup
 */
class Setup implements RouteInterface {
  /**
   * {@inheritdoc}
   */
  public static function register(App $app) : void {
    $app->getContainer()[\App\Controller\Setup::class] = function (ContainerInterface $container) : ControllerInterface {
      return new \App\Controller\Setup(
        $container->get('commandBus'),
        $container->get('commandFactory')
      );
    };

    self::index($app);
  }

  /**
   * Index screen
   *
   * Handles setup index screen.
   *
   * @param \Slim\App $app
   *
   * @return void
   *
   * @see App\Controller\Setup::setup
   */
  private static function index(App $app) : void {
    $app
      ->get(
        '/setup',
        'App\Controller\Setup:index'
      )
      ->setName('setup:index');
  }
}
