<?php
declare(strict_types = 1);

namespace App\Factory;

/**
 * Command Factory Implementation.
 */
class Command extends AbstractFactory {
  /**
   * {@inheritdoc}
   */
  protected function getNamespace() : string {
    return '\\App\\Command\\';
  }
}
