# GraphCMS PHP Library

## Initialize GraphCMS client
Just create a new `GraphCMS` instance and pass your project ID and token
```
$g = new GraphCMS('projectId', 'token');
```

## Create a new query
You can use the `GraphCMS` `query` method to create a new query
```
// apart from TYPE_QUERY, there's also TYPE_MUTATION
$query = $g->query(Query::TYPE_QUERY, 'allMovies', [
    'title',
    'director'
]);
// add a variable of type String which is required
$query->variables()->add('titleStartsWith', new Type(Type::STRING, true));
// add a parameter using the variable
$query->params()->add('filter', [ 'title_starts_with' => '$titleStartsWith' ]);
```

## Executing the query
You can call `execute` on your `GraphCMS` client to execute the first non-executed query you created.
```
$data = $g->execute([ 'titleStartsWith' => 'The' ]);
```

## Using a CacheAdapter
The `GraphCMS` client has functionality built in to use any type of cache by using the `CacheAdapter`
class. For example you can use the `SyncFileCache` adapter to write the queries to files synchronously.  

**Example**  
```
$g->setCacheAdapter(new SyncFileCache(__DIR__.'/cache'));
```  
This example will use the `SyncFileCache` adapter to cache
the queries.

## Using the RedisCache
The `RedisCache` adapter requires a bit more work. First off you need `Redis` support for PHP (usually
this comes in the form of the `phpredis` extension). Secondly you need to install  the `tomaj/hermes`
package with composer (a composer file is available in this repo).

**Example**
```
$g->setCacheAdapter(new RedisCache('127.0.0.1', 6379));
```
Here we set the adapter to use the host `127.0.0.1` and the default port `6379`. Values will be added
to the cache, but they will never be updated yet. This is where the `RedisCacheWorker` comes in. You
will have to run this file in CLI, but make sure to include the correct files.

Your CLI file will look something like this:
```
<?php
    // for hermes
    include 'vendor/autoload.php';
    // for graphcms usage
    include 'GraphCMS.php';
    // for RedisCache usage
    include 'RedisCache.php';

    // include the worker
    include 'CacheAdapters/RedisCache/RedisCacheWorker.php';
```

And then run your file (preferrably in the background of course)
```
php7 worker.php --host 127.0.0.1 --port 6379
```