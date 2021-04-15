# Colja

A Symfony bundle to bring functionality of [Siler](https://github.com/leocavalcante/siler/) to Symfony.


## Setup Example

```
a-symfony-project/
├─ bin/
├─ config/
│  ├─ graphql/
│  │  ├─ base.schema
│  │  ├─ entityDemo.schema
│  ├─ packages/
│  │  ├─ .../
│  │  ├─ d_mo_colja.yaml
│  │  ├─ ...
├─ src/
│  ├─ Controller/
│  ├─ .../
│  ├─ Entity/
│  │  ├─ ColjaEntity.php
│  │  ├─ ...
│  ├─ GraphQL/
│  │  ├─ DemoEntityResolver.php
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

### entityDemo.schema
```graphql
extend type Query {
    getEntity(id: ID!): ColjaEntity
}

type ColjaEntity {
    id: ID!,
    name: String
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
  extensions:
    - schema: config/graphql/entityDemo.schema
      query:
        getEntity:
          class: App\GraphQL\DemoEntityResolver
          method: getColjaEntity

```

### ColjaEntity.php
```php
<?php

namespace App\Entity;

class ColjaEntity
{
    private $id;

    private $name;

    public function __construct(string $name)
    {
        $this->id = sha1(microtime());
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

### DemoEntityResolver.php
```php
<?php

namespace App\GraphQL;

use DMo\Colja\GraphQL\AbstractEntityResolver;
use App\Entity\ColjaEntity;

class DemoEntityResolver extends AbstractEntityResolver
{
    protected function structure($entity): array
    {
        return [
            'id' => $entity->getId(),
            'name' => $entity->getName()
        ];
    }

    protected function getEntityClassname(): string
    {
        return ColjaEntity::class;
    }

    public function getColjaEntity(array $root = null, array $args): array
    {
        $entity = new ColjaEntity('The name!');
        return $this->structure($entity);
    }
}
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
