<?php
declare(strict_types = 1);

namespace App\Command\Response;

use App\Command\AbstractCommand;
use App\Command\CommandInterface;

/**
* Success Response Command.
*/
class Success extends AbstractCommand {
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

    if (isset($parameters['body'])) {
      $this->body = array_merge(
        $parameters['body'],
        ['status' => true]
      );
    }

    if (isset($parameters['statusCode'])) {
      $this->statusCode = $parameters['statusCode'];
    }

    return $this;
  }
}
