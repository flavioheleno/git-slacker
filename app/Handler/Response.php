<?php
declare(strict_types = 1);

namespace App\Handler;

use App\Command\CommandInterface;
use App\Command\Response\Error;
use App\Command\Response\Success;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator;
use Slim\HttpCache\CacheProvider;

/**
 * Handles HTTP Responses.
 */
class Response implements HandlerInterface {
  private $httpCache;
  private $validator;

  /**
   * Handles JSON-encoded Responses.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param array                               $body
   * @param int                                 $statusCode
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function jsonResponse(
      ResponseInterface $response,
      array $body,
      int $statusCode = 200
  ) : ResponseInterface {
    $encodedBody = json_encode($body, \JSON_PRESERVE_ZERO_FRACTION);
    $response    = $this->httpCache->withEtag($response, sha1($encodedBody), 'weak');

    return $response
      ->withStatus($statusCode)
      ->withHeader('Content-Type', 'application/json; charset=utf-8')
      ->write($encodedBody);
  }

  /**
   * Handles JavaScript/JSONP-encoded Responses.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param array                               $body
   * @param int                                 $statusCode
   * @param string                              $callback
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function javascriptResponse(
      ResponseInterface $response,
      array $body,
      int $statusCode = 200,
      string $callback = 'jsonp'
  ) : ResponseInterface {
    $encodedBody = sprintf(
      '/**/%s(%s)',
      $callback,
      json_encode($body, \JSON_PRESERVE_ZERO_FRACTION)
    );
    $response    = $this->httpCache->withEtag($response, sha1($encodedBody), 'weak');

    return $response
      ->withStatus($statusCode)
      ->withHeader('Content-Type', 'application/javascript')
      ->write($encodedBody);
  }

  /**
   * Handles XML-encoded Responses.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param array                               $body
   * @param int                                 $statusCode
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function xmlResponse(
      ResponseInterface $response,
      array $body,
      int $statusCode = 200
  ) : ResponseInterface {
    $xml = new \SimpleXMLElement('<veridu/>');
    array_walk_recursive(
      $body,
      function ($value, $key) use ($xml) {
        if (is_bool($value)) {
          $xml->addChild($key, ($value ? 'true' : 'false'));
        } else {
          $xml->addChild($key, $value);
        }
      }
    );
    $encodedBody = $xml->asXML();
    $response    = $this->httpCache->withEtag($response, sha1($encodedBody), 'weak');

    return $response
      ->withStatus($statusCode)
      ->withHeader('Content-Type', 'application/xml; charset=utf-8')
      ->write($encodedBody);
  }

  /**
   * Handles Text/Plain Responses.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param array                               $body
   * @param int                                 $statusCode
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function textResponse(
    ResponseInterface $response,
    array $body,
    int $statusCode = 200
  ) : ResponseInterface {
    $encodedBody = http_build_query($body);
    $response    = $this->httpCache->withEtag($response, sha1($encodedBody), 'weak');

    return $response
      ->withStatus($statusCode)
      ->withHeader('Content-Type', 'text/plain')
      ->write($encodedBody);
  }

  /**
   * Handles a response dispatch, parsing multiple request parameters.
   *
   * Parameters:
   *  - failSilently: forces 200 HTTP Status for 4xx and 5xx responses
   *  - forceOutput: overrides HTTP's Accept header
   *
   * @param \App\Command\ResponseInterface $command
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function responseDispatch(CommandInterface $command) : ResponseInterface {
    $request    = $command->request;
    $response   = $command->response;
    $body       = $command->body;
    $statusCode = $command->statusCode;

    if (! isset($body['status'])) {
      $body = array_merge(['status' => true], $body);
    }

    $queryParams = $request->getQueryParams();

    // Forces HTTP errors (4xx and 5xx) to be suppressed
    if (($statusCode >= 400)
      && (isset($queryParams['failSilently']))
      && ($this->validator::trueVal()->validate($queryParams['failSilently']))
    ) {
      $statusCode = 200;
    }

    // Overrides HTTP's Accept header
    if (! empty($queryParams['forceOutput'])) {
      switch (strtolower($queryParams['forceOutput'])) {
        // case 'plain':
        //  $accept = ['text/plain'];
        //  break;
        case 'xml':
          $accept = ['application/xml'];
          break;
        case 'javascript':
          $accept = ['application/javascript'];
          break;
        case 'json':
        default:
          $accept = ['application/json'];
      }
    } else {
      // Extracts HTTP's Accept header
      $accept = $request->getHeaderLine('Accept');

      if (preg_match_all('/([^\/]+\/[^;,]+)[^,]*,?/', $accept, $matches)) {
        $accept = $matches[1];
      } else {
        $accept = ['application/json'];
      }
    }

    // Last Modified Cache Header
    if (isset($body['updated'])) {
      $response = $this
        ->httpCache
        ->withLastModified($response, $body['updated']);
    } elseif (isset($body['data']['updated'])) {
      $response = $this
        ->httpCache
        ->withLastModified($response, $body['data']['updated']);
    }

    // Force Content-Type to be used
    $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

    // if ((in_array('text/html', $accept)) || (in_array('text/plain', $accept)))
    //  return $this->textResponse($response, $body, $statusCode);

    // if (in_array('application/xml', $accept))
    //  return $this->xmlResponse($response, $body, $statusCode);

    if (in_array('application/javascript', $accept)) {
      $callback = 'jsonp';
      if ((! empty($queryParams['callback'])) && (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $queryParams['callback']))) {
        $callback = $queryParams['callback'];
      }

      return $this->javascriptResponse($response, $body, $statusCode, $callback);
    }

    return $this->jsonResponse($response, $body, $statusCode);
  }

  /**
   * Dependency Container registration.
   *
   * @param \Interop\Container\ContainerInterface $container
   *
   * @return void
   */
  public static function register(ContainerInterface $container) : void {
    $container[self::class] = function (ContainerInterface $container) : HandlerInterface {
      return new \App\Handler\Response(
        $container->get('httpCache'),
        $container->get('validator')
      );
    };
  }

  /**
   * Class constructor.
   *
   * @param \Slim\HttpCache\CacheProvider $httpCache
   * @param \Respect\Validation\Validator $validator
   *
   * @return void
   */
  public function __construct(CacheProvider $httpCache, Validator $validator) {
    $this->httpCache = $httpCache;
    $this->validator = $validator;
  }

  /**
   * Handles a success response.
   *
   * @param \App\Command\Response\Success $command
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function handleSuccess(Success $command) : ResponseInterface {
    if (empty($command->body)) {
      $command->body = [];
    }

    return $this->responseDispatch($command);
  }

  /**
   * Handles an error response.
   *
   * @param \App\Command\Response\Error $command
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function handleError(Error $command) : ResponseInterface {
    return $this->responseDispatch($command);
  }
}
