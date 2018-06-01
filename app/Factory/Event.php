<?php
declare(strict_types = 1);

namespace App\Factory;

use League\Event\EventInterface;

/**
 * Event Factory Implementation.
 */
class Event extends AbstractFactory {
  /**
   * {@inheritdoc}
   */
  protected function getNamespace() : string {
    return '\\App\\Event\\';
  }

  /**
   * Creates new event instances.
   *
   * @param string $name
   * @param array  $params
   *
   * @throws \RuntimeException
   *
   * @return \League\Event\EventInterface
   */
  public function create(string $name, ...$params) : EventInterface {
    $class = $this->getClassName($name);

    if (class_exists($class)) {
      return new $class(...$params);
    }

    throw new \RuntimeException(sprintf('Class (%s) not found.', $class));
  }
}
