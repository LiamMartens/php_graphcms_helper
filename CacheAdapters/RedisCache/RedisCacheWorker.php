<?php
    namespace GraphCMS\CacheAdapters\RedisCache;
    use Redis;
    use Tomaj\Hermes\Driver\RedisSetDriver;
    use Tomaj\Hermes\Dispatcher;
    use Tomaj\Hermes\Handler\HandlerInterface;
    use Tomaj\Hermes\MessageInterface;
    use GraphCMS\Query;
    use GraphCMS\CacheAdapters\RedisCache\RedisCache;

    /**
     * Don't forget to include the correct files
     *
     * require __DIR__.'/vendor/autoload.php';
     * require __DIR__.'/../../GraphCMS.php';
     * require __DIR__.'/RedisCache.php';
     */

    // set default values
    $host = '127.0.0.1';
    $port = 6379;
    // check argv for overrides
    foreach($argv as $index => $value) {
        switch($value) {
            case '--host':
                $host = $argv[$index+1];
                $index++;
                break;
            case '--port':
                $port = intval($argv[$index+1]);
                $index++;
                break;
        }
    }

    // handler class
    class RedisCacheWorkerUpdateHandler implements HandlerInterface {
        protected $_redis;
        public function __construct(Redis $redis) {
            $this->_redis = $redis;
        }

        public function handle(MessageInterface $message) {
            $payload = $message->getPayload();
            // decode payload
            $query = unserialize($payload['query']);
            $variables = json_decode($payload['variables'], true);
            // execute query
            $data = $query->execute($variables);
            $this->_redis->set($query->build(), json_encode([
                'updated' => time(),
                'data' => $data
            ]));
            return true;
        }
    }

    // connect to redis and hermes dispatcher
    $redis = new Redis();
    $redis->connect($host, $port);
    $dispatcher = new Dispatcher(new RedisSetDriver($redis));
    // attach handlers
    $dispatcher->registerHandler(RedisCache::MSG_UPDATE, new RedisCacheWorkerUpdateHandler($redis));
    // wait for messages
    $dispatcher->handle();