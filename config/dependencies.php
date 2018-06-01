<?php
declare(strict_types = 1);

use App\Command;
use App\Exception\AppException;
use App\Factory;
use App\Handler;
use App\Helper;
use App\Middleware;
use App\Middleware\Auth;
use App\Middleware\TransactionMiddleware;
use App\Repository;
use Aws\S3\S3Client;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use BotMan\Drivers\Telegram\TelegramDriver;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Interop\Container\ContainerInterface;
use Jenssegers\Optimus\Optimus;
use Lcobucci\JWT;
use League\Event\Emitter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Stash as Cache;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\OAuth2\Client\Provider\GenericProvider;
use League\Tactician\CommandBus;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Logger\Formatter\ClassNameFormatter;
use League\Tactician\Logger\Formatter\ClassPropertiesFormatter;
use League\Tactician\Logger\LoggerMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Memory;
use OAuth\ServiceFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Validator;
use Slim\HttpCache\CacheProvider;
use Stash\Driver\Apc;
use Stash\Driver\Composite;
use Stash\Driver\Ephemeral;
use Stash\Driver\FileSystem as FileSystemCache;
use Stash\Driver\Memcache;
use Stash\Driver\Redis;
use Stash\Driver\Sqlite;
use Stash\Pool;
use Whoops\Handler\PrettyPageHandler;

if (! isset($app)) {
    die('$app is not set!');
}

$container = $app->getContainer();

// Slim Error Handling
$container['errorHandler'] = function (ContainerInterface $container) : callable {
  return function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    \Throwable $exception
  ) use ($container) : ResponseInterface {
    $settings = $container->get('settings');
    $response = $container
      ->get('httpCache')
      ->denyCache($response);

    if ($exception instanceof AppException) {
      $error = [
        'id'      => $container->get('logUidProcessor')->getUid(),
        'code'    => $exception->getCode(),
        'message' => $exception->getMessage()
      ];

      if ($settings['debug']) {
        $error['trace'] = $exception->getTrace();
      }

      $command = $container
        ->get('commandFactory')
        ->create('Response\\Error');
      $command
        ->setParameter('request', $request)
        ->setParameter('response', $response)
        ->setParameter('error', $error)
        ->setParameter('statusCode', $exception->getCode());

      return $container->get('commandBus')->handle($command);
    }

    $log = $container->get('log');
    $log('Foundation')->error(
      sprintf(
        '%s [%s:%d]',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
      )
    );
    $log('Foundation')->debug($exception->getTraceAsString());

    $previousException = $exception->getPrevious();
    if ($previousException) {
      $log('Foundation')->error(
        sprintf(
          '%s [%s:%d]',
          $previousException->getMessage(),
          $previousException->getFile(),
          $previousException->getLine()
        )
      );
      $log('Foundation')->debug($previousException->getTraceAsString());
    }

    if ($settings['debug']) {
      $prettyPageHandler = new PrettyPageHandler();
      // Add more information to the PrettyPageHandler
      $prettyPageHandler->addDataTable(
        'Request',
        [
          'Accept Charset'  => $request->getHeader('ACCEPT_CHARSET') ?: '<none>',
          'Content Charset' => $request->getContentCharset() ?: '<none>',
          'Path'            => $request->getUri()->getPath(),
          'Query String'    => $request->getUri()->getQuery() ?: '<none>',
          'HTTP Method'     => $request->getMethod(),
          'Base URL'        => (string) $request->getUri(),
          'Scheme'          => $request->getUri()->getScheme(),
          'Port'            => $request->getUri()->getPort(),
          'Host'            => $request->getUri()->getHost()
        ]
      );

      $whoops = new Whoops\Run();
      $whoops->pushHandler($prettyPageHandler);

      return $response
        ->withStatus(500)
        ->write($whoops->handleException($exception));
    }

    $error = [
      'id'      => $container->get('logUidProcessor')->getUid(),
      'code'    => 500,
      'message' => 'Internal Application Error'
    ];

    $command = $container
      ->get('commandFactory')
      ->create('Response\\Error');
    $command
      ->setParameter('request', $request)
      ->setParameter('response', $response)
      ->setParameter('error', $error)
      ->setParameter('statusCode', 500);

    return $container->get('commandBus')->handle($command);
  };
};

// PHP Error Handler
$container['phpErrorHandler'] = function (ContainerInterface $container) : callable {
  return $container->errorHandler;
};

// Slim Not Found Handler
$container['notFoundHandler'] = function (ContainerInterface $container) : callable {
  return function (
    ServerRequestInterface $request,
    ResponseInterface $response
  ) use ($container) : ResponseInterface {
    throw new AppException('Whoopsies! Route not found!', 404);
  };
};

// Slim Not Allowed Handler
$container['notAllowedHandler'] = function (ContainerInterface $container) : callable {
  return function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $methods
  ) use ($container) : ResponseInterface {
    if ($request->isOptions()) {
      return $response->withStatus(204);
    }

    throw new AppException('Whoopsies! Method not allowed for this route!', 400);
  };
};

// BotMan
$container['botman'] = function (ContainerInterface $container) : BotMan {
  $config = [];
  if (isset($container['secrets']['SLACK_BOT_TOKEN'])) {
    $config['slack']['token'] = $container['secrets']['SLACK_BOT_TOKEN'];
    DriverManager::loadDriver(SlackDriver::class);
  }

  if (isset($container['secrets']['TELEGRAM_TOKEN'])) {
    $config['telegram']['token'] = $container['secrets']['TELEGRAM_TOKEN'];
    DriverManager::loadDriver(TelegramDriver::class);
  }

  return BotManFactory::create($config);
};

// Monolog Request UID Processor
$container['logUidProcessor'] = function (ContainerInterface $container) : callable {
  return new UidProcessor();
};

// Monolog Request Processor
$container['logWebProcessor'] = function (ContainerInterface $container) : callable {
  return new WebProcessor();
};

// Monolog Logger
$container['log'] = function (ContainerInterface $container) : callable {
  return function ($channel = 'API') use ($container) : Logger {
    $settings = $container->get('settings');
    $logger   = new Logger($channel);
    $logger
      ->pushProcessor($container->get('logUidProcessor'))
      ->pushProcessor($container->get('logWebProcessor'))
      ->pushHandler(new StreamHandler($settings['log']['path'], $settings['log']['level']));

    return $logger;
  };
};

// Slim HTTP Cache
$container['httpCache'] = function (ContainerInterface $container) : CacheProvider {
  return new CacheProvider();
};

// Tactician Command Bus
$container['commandBus'] = function (ContainerInterface $container) : CommandBus {
  $settings = $container->get('settings');
  $log      = $container->get('log');

  $commandList = [];
  if ((! empty($settings['boot']['commandsCache'])) && (is_file($settings['boot']['commandsCache']))) {
    $cache = file_get_contents($settings['boot']['commandsCache']);
    if ($cache !== false) {
      $cache = unserialize($cache);
    }

    if ($cache !== false) {
      $commandList = $cache;
    }
  }

  if (empty($commandList)) {
    $commandFiles = new RegexIterator(
      new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
          __ROOT__ . '/app/Command/'
        )
      ),
      '/^.+\.php$/i',
      RecursiveRegexIterator::MATCH
    );

    foreach ($commandFiles as $commandFile) {
      if (strpos($commandFile->getBasename(), 'Abstract') !== false) {
        continue;
      }

      if (strpos($commandFile->getBasename(), 'Interface') !== false) {
        continue;
      }

      if (preg_match('/Command\/(.*)\/(.*).php$/', $commandFile->getPathname(), $matches) == 1) {
        $resource = str_replace('/', '\\', $matches[1]);
        $command  = sprintf('App\\Command\\%s\\%s', $resource, $matches[2]);
        $handler  = sprintf('App\\Handler\\%s', $resource);

        $commandList[$command] = $handler;
      }
    }

    // $commandList[Command\SuccessResponse::class] = Handler\Response::class;
    // $commandList[Command\ErrorResponse::class] = Handler\Response::class;

    if (! empty($settings['boot']['commandsCache'])) {
      file_put_contents($settings['boot']['commandsCache'], serialize($commandList));
    }
  }

  $handlerMiddleware = new CommandHandlerMiddleware(
    new ClassNameExtractor(),
    new ContainerLocator(
      $container,
      $commandList
    ),
    new HandleClassNameInflector()
  );

  if ($settings['debug']) {
    $formatter = new ClassPropertiesFormatter();
  } else {
    $formatter = new ClassNameFormatter();
  }

  return new CommandBus(
    [
      // new LoggerMiddleware(
      //   $formatter,
      //   $log('CommandBus')
      // ),
      $handlerMiddleware
    ]
  );
};

// App Command Factory
$container['commandFactory'] = function (ContainerInterface $container) : Factory\Command {
  return new Factory\Command();
};

// Validator Factory
$container['validatorFactory'] = function (ContainerInterface $container) : Factory\Validator {
  return new Factory\Validator();
};

// App Entity Factory
$container['entityFactory'] = function (ContainerInterface $container) : Factory\Entity {
  return new Factory\Entity($container->get('optimus'), $container->get('vault'));
};

// App Event Factory
$container['eventFactory'] = function (ContainerInterface $container) : Factory\Event {
  return new Factory\Event();
};

// FlySystem
$container['fileSystem'] = function (ContainerInterface $container) : callable {
  return function (string $bucketName) use ($container) : Filesystem {
    $settings = $container->get('settings');

    if (empty($settings['fileSystem']['adapter'])) {
      throw new \RuntimeException('fileSystem:adapter is not set');
    }

    switch ($settings['fileSystem']['adapter']) {
      case 's3':
        $adapter = new AwsS3Adapter(
          $container->get('S3Client'),
          sprintf('git-slacker-%s', $bucketName)
        );
        break;
      case 'local':
        $adapter = new Local(
          sprintf(
            '%s/git-slacker-%s',
            rtrim(
              $settings['fileSystem']['path'] ?? '/tmp',
              '/'
            ),
            $bucketName
          )
        );
        break;
      case 'null':
        $adapter = new NullAdapter();
        break;
      default:
        throw new \RuntimeException('Invalid fileSystem:adapter');
    }

    if (! empty($settings['fileSystem']['cached'])) {
      $adapter = new CachedAdapter(
        $adapter,
        new Cache(
          $container->get('cache'),
          sprintf('git-slacker-%s', $bucketName),
          300
        )
      );
    }

    $fileSystem = new Filesystem(
      $adapter,
      [
        'visibility' => AdapterInterface::VISIBILITY_PRIVATE
      ]
    );

    return $fileSystem->addPlugin(new ListFiles());
  };
};

// Respect Validator
$container['validator'] = function (ContainerInterface $container) : Validator {
  return Validator::create();
};

// OAuth2 Client Factory
$container['oauth'] = function (ContainerInterface $container) : callable {
  return function ($providerName) use ($container) : GenericProvider {
    $secrets = $container->get('secrets');
    $urls    = $container->get('urls');

    switch ($providerName) {
      case 'github':
        $settings = [
          'clientId'                => $secrets['GITHUB_CLIENT_ID'],
          'clientSecret'              => $secrets['GITHUB_CLIENT_SECRET'],
          'redirectUri'             => 'https://git-slacker.flavioheleno.com' . $container->get('router')->pathFor('oauth:callback', ['providerName' => 'github']),
          'urlAuthorize'            => $urls['GITHUB_AUTHORIZE_URL'],
          'urlAccessToken'          => $urls['GITHUB_ACCESS_TOKEN_URL'],
          'urlResourceOwnerDetails' => ''
        ];
        break;
      case 'slack':
        $settings = [
          'clientId'                => $secrets['SLACK_CLIENT_ID'],
          'clientSecret'            => $secrets['SLACK_CLIENT_SECRET'],
          'redirectUri'             => 'https://git-slacker.flavioheleno.com' . $container->get('router')->pathFor('oauth:callback', ['providerName' => 'slack']),
          'urlAuthorize'            => $urls['SLACK_AUTHORIZE_URL'],
          'urlAccessToken'          => $urls['SLACK_ACCESS_TOKEN_URL'],
          'urlResourceOwnerDetails' => ''
        ];
        break;
      default:
        throw new \RuntimeException(sprintf('Invalid oAuth Provider "%s"', $providerName));
    }

    return new GenericProvider($settings);
  };
};

// App files
$container['globFiles'] = function () : array {
  return [
    'routes' => array_merge(
      glob(__ROOT__ . '/app/Route/*.php'),
      glob(__ROOT__ . '/app/Route/*/*.php')
    ),
    'handlers' => array_merge(
      glob(__ROOT__ . '/app/Handler/*.php'),
      glob(__ROOT__ . '/app/Handler/*/*.php')
    ),
    'eventListeners' => array_merge(
      glob(__ROOT__ . '/app/Listener/*Listener.php'),
      glob(__ROOT__ . '/app/Listener/*/*Listener.php')
    ),
    'listenerProviders' => array_merge(
      glob(__ROOT__ . '/app/Listener/*Provider.php'),
      glob(__ROOT__ . '/app/Listener/*/*Provider.php')
    )
  ];
};

// HTTP Client
$container['httpClient'] = function (ContainerInterface $container) : HttpClient {
  return new HttpClient();
};

// Registering Event Emitter
$container['eventEmitter'] = function (ContainerInterface $container) : Emitter {
  return new Emitter();
};

// Application Secrets
$container['secrets'] = function (ContainerInterface $container) : array {
  return [
    'GITHUB_WEBHOOK_SECRET'  => Helper\Env::asString('GITHUB_WEBHOOK_SECRET', ''),
    'GITHUB_OAUTH_KEY'       => Helper\Env::asString('GITHUB_OAUTH_KEY', ''),
    'GITHUB_OAUTH_SECRET'    => Helper\Env::asString('GITHUB_OAUTH_SECRET', ''),
    'GITHUB_CLIENT_ID'       => Helper\Env::asString('GITHUB_CLIENT_ID', ''),
    'GITHUB_CLIENT_SECRET'   => Helper\Env::asString('GITHUB_CLIENT_SECRET', ''),
    'SLACK_BOT_TOKEN'        => Helper\Env::asString('SLACK_BOT_TOKEN', ''),
    'SLACK_OAUTH_TOKEN'      => Helper\Env::asString('SLACK_OAUTH_TOKEN', ''),
    'SLACK_WEBHOOK_TOKEN'    => Helper\Env::asString('SLACK_WEBHOOK_TOKEN', ''),
    'SLACK_CLIENT_ID'        => Helper\Env::asString('SLACK_CLIENT_ID', ''),
    'SLACK_CLIENT_SECRET'    => Helper\Env::asString('SLACK_CLIENT_SECRET', ''),
    'TELEGRAM_TOKEN'         => Helper\Env::asString('TELEGRAM_TOKEN', ''),
    'TELEGRAM_WEBHOOK_TOKEN' => Helper\Env::asString('TELEGRAM_WEBHOOK_TOKEN', '')
  ];
};

// OAuth Scopes
$container['scopes'] = function (ContainerInterface $container) : array {
  return [
    'GITHUB_SCOPES' => Helper\Env::asString('GITHUB_SCOPES', ''),
    'SLACK_SCOPES'  => Helper\Env::asString('SLACK_SCOPES', '')
  ];
};

// OAuth URLs
$container['urls'] = function (ContainerInterface $container) : array {
  return [
    'GITHUB_AUTHORIZE_URL'    => Helper\Env::asString('GITHUB_AUTHORIZE_URL', ''),
    'GITHUB_ACCESS_TOKEN_URL' => Helper\Env::asString('GITHUB_ACCESS_TOKEN_URL', ''),
    'SLACK_AUTHORIZE_URL'     => Helper\Env::asString('SLACK_AUTHORIZE_URL', ''),
    'SLACK_ACCESS_TOKEN_URL'  => Helper\Env::asString('SLACK_ACCESS_TOKEN_URL', '')
  ];
};

// User mapper
$container['mapper'] = function (ContainerInterface $container) : Helper\Mapper {
  return new Helper\Mapper();
};
