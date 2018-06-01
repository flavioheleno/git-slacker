<?php
declare(strict_types = 1);

namespace App\Exception;

/**
* Base Application Exception.
*
* @apiEndpointResponse 500 schema/error.json
*/
class AppException extends \Exception {
  /**
   * {@inheritdoc}
   */
  protected $code = 500;
  /**
   * {@inheritdoc}
   */
  protected $message = 'Application Internal Error.';
}
