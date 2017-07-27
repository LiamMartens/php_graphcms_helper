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