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