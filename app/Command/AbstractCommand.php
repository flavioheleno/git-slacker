<?php
declare(strict_types = 1);

namespace App\Command;

/**
 * Abstract Command Implementation.
 */
abstract class AbstractCommand implements CommandInterface {
  /**
   * {@inheritdoc}
   */
  public function setParameters(array $parameters) : CommandInterface {
    foreach ($parameters as $name => $value) {
      $this->setParameter($name, $value);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter(string $name, $value) : CommandInterface {
    if (property_exists($this, $name)) {
      $this->{$name} = $value;

      return $this;
    }

    throw new \RuntimeException(sprintf('Invalid property name "%s"', $name));
  }
}
