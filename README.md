# Colja

A Symfony bundle to bring functionality of [Siler](https://github.com/leocavalcante/siler/) to Symfony.
Per default GraphQL requests (method POST) will be handled on request-uri `/graphql`.

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
