<?php
declare(strict_types = 1);

namespace App\Command\Response;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
* Error Response Command.
*/
class Error extends AbstractCommand {
  /**
   * Request instance.
   *
   * @var \Psr\Http\Message\ServerRequestInterface
   */
  public $request;
  /**
   * Response instance.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  public $response;
  /**
   * Response Body.
   *
   * @var array
   */
  public $body;
  /**
   * Response error.
   *
   * @var array
   */
  public $error;
  /**
   * HTTP Status Code.
   *
   * @var int
   */
  public $statusCode = 200;

  /**
   * {@inheritdoc}
   */
  public function setParameters(array $parameters) : CommandInterface {
    if (isset($parameters['request'])) {
      $this->request = $parameters['request'];
    }

    if (isset($parameters['response'])) {
      $this->response = $parameters['response'];
    }

    if (isset($parameters['error'])) {
      $this->error = $parameters['error'];
      $this->body  = [
        'status' => false,
        'error'  => $parameters['error']
      ];
    }

    if (isset($parameters['statusCode'])) {
      $this->statusCode = $parameters['statusCode'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter(string $name, $value) : CommandInterface {
    if (property_exists($this, $name)) {
      $this->{$name} = $value;

      if ($name === 'error') {
        $this->body = [
          'status' => false,
          'error'  => $value
        ];
      }

      return $this;
    }

    throw new \RuntimeException(sprintf('Invalid property name "%s"', $name));
  }
}
