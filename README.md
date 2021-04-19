# Colja

A Symfony bundle to provide GraphQL functionality (by the use of [Siler](https://github.com/leocavalcante/siler/)).
Per default GraphQL requests (method POST) will be handled on request-uri `/graphql`.

\
&nbsp;

## Schema
There has to be defined one basic schema file in GraphQL Schema Definition Language ([SDL](https://graphql.org/learn/schema/)). The path to this file has to be configured in the `d_mo_colja.yaml`-file (see [here](https://github.com/d3mo17/colja-examples/blob/master/config/packages/d_mo_colja.yaml#L2)).

The basic schema file can be extended by multiple more files (see [here](https://github.com/d3mo17/colja-examples/blob/master/config/packages/d_mo_colja.yaml#L11)).

To link a query or mutation (field) from the schema to a resolver callable in php you have to [configure a classname and a method with the specified fieldname](https://github.com/d3mo17/colja-examples/blob/master/config/packages/d_mo_colja.yaml#L4-L6)

\
&nbsp;

## Resolvers
A class which contains field resolvers, must extend the Class `DMo\Colja\GraphQL\AbstractResolver`. This is because the `ResolverManager` will be injected into each Resolver, so through this you will be able to get the symfony controller and container.

All Resolvers (callable functions) can be defined to expect four parameters:
  - `$root`
  - `$args`
  - `$context`
  - `$info`

The most important parameter is the second one: `$args`. It contains all arguments passed to a field.

Each Resolver can return a scalar value or an associative array. The array values can contain scalars or function references which act as field resolvers by themselves.

All field values which need to be "lazy-loaded" must be defined as resolver, because a resolver will only be called if the corresponding field was requested in a query.
You can also realize recursive data structures with this technique (see [example here](https://github.com/d3mo17/colja-examples/blob/master/src/GraphQL/DemoEntityResolver.php#L16-L33)).

\
&nbsp;

## GET-Requests
By default this bundle only supports the request method POST. But you can easily add support for the method GET. Just place the following in the `routes.yaml` of your symfony project:
```
d_mo_colja_endpoint_get:
    path:       /graphql
    controller: DMo\Colja\Controller\GraphQLController::endpoint
    methods:    GET
```

\
&nbsp;

## Setup Example

See also [Colja examples](https://github.com/d3mo17/colja-examples/) for more information

```
a-symfony-project/
├─ bin/
├─ config/
│  ├─ graphql/
│  │  ├─ base.schema
│  ├─ packages/
│  │  ├─ .../
│  │  ├─ d_mo_colja.yaml
│  │  ├─ ...
|  ├─ bundles.php
│  ├─ .../
├─ src/
│  ├─ Controller/
│  ├─ .../
│  ├─ GraphQL/
│  │  ├─ DemoResolver.php
│  │  ├─ ...
├─ ...
├─ composer.json

```

### base.schema
```graphql
type Query {
    demo: String,
    demoArgs(name: String!, num: Int): String
}
```

### d_mo_colja.yaml
```yaml
d_mo_colja:
  schema: config/graphql/base.schema
  query:
    demo:
      class: App\GraphQL\DemoResolver
      method: demo
    demoArgs:
      class: App\GraphQL\DemoResolver
      method: demoWithArguments
```

### bundles.php
```php
<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    // ...
    DMo\Colja\DMoColjaBundle::class => ['all' => true],
];

```

### DemoResolver.php
```php
<?php

namespace App\GraphQL;

use DMo\Colja\GraphQL\AbstractResolver;

class DemoResolver extends AbstractResolver
{
    public function demo(array $root = null, array $args): string
    {
        return "Hello World!";
    }

    public function demoWithArguments(array $root = null, array $args): string
    {
        $num = array_key_exists('num', $args) ? $args['num'] : 17;
        return "Hello ${args['name']}. The magic number is $num!";
    }
}
```

\
&nbsp;

## License

The MIT License (MIT)

Copyright (c) 2021 Daniel Moritz

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
