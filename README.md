# lazy-boy
A skeleton REST API application, using [Silex] and [Syringe] with support for [Puzzle-DI]

## Summary
Lazy Boy will create a skeleton [Silex] framework, so you can create REST APIs without having to bother with 
boilerplate code.

It is packaged with a route loader and uses [Syringe], which allows you to define both your routes and services in 
configuration files, rather than PHP

If you have the [Symfony console] installed, it will also create a console script and automatically load any commands it
finds in the service container (any service name which ends with ".command" and is an instance of the Symfony Command 
class). You can also use [Puzzle-DI] to load service configuration from modules.

## Requirements

* [Silex] 2.0+

## Installation
install using composer:

    composer require silktide/lazy-boy:^2.0

Lazy Boy will automatically generate several files from templates, whenever `composer update` or `composer install` is run.
You are free to make modifications; Lazy Boy will not overwrite a file which already exists, so committing those changes 
to a VCS is safe. Having your VCS ignore the files will mean they are generated when you install vendors on a freshly 
cloned repository.

If you want to disable automatic file generation, so you can use the FrontController or RouteLoader perhaps, add the 
following to your composer file:

```json
"extra": {
  "silktide/lazy-boy": {
    "prevent-install": true
  }
}
```

All that is left to do is create a vhost or otherwise point requests to `web/index.php`.
 
## Routing

### Routes

If you are using the standard Lazy-Boy route loader, you can define your routes in configuration files, using YAML or
JSON. Each route is defined as follows:

```yaml
routes:
    route-name:
        url: /sub/directory
        action: "test_controller:doSomething"
        method: post
```

`routes` is an associative array of routes that you want to allow access to.

In this case, a HTTP request that was `POST`ed to `/sub/directory`, would access a service in the container called
`test-controller` and call it's method `doSomething`. This route could be referenced as `route-name` when using the 
router.

For each route, the `url` and `action` parameters are required, but `method` is optional and defaults to `GET`.

You can also use the `assert` parameter to overwrite the default regex for parameter of a route. For example

```yaml
    routes:
        route-one:
            url: /user/{id}
            action: "test_controller:doSomething"
            method: get
```

The URL `/user/56` would match and the `id` parameter would come back as `56`.
The URL `/user/56/foo` would not match.
```yaml
    routes:
        route-two:
            url: /user/{my_wildcard}
            action: "test_controller:doSomethingElse
            method: get
            assert:
                my_wildcard: ".*"
```

Going to the URL `/user/56` would match and again, the `my_wildcard` parameter would come back as `56`.
Going the the URL `/user/56/foo` would match and the `my_wildcard` parameter would return `56/foo`

### Groups

If you have many routes with similar URLs, such as:

* /users
* /users/{id}
* /users/login
* /users/logout

you can use a group to wrap them with a common url prefix.

```yaml
groups:
    users:
        urlPrefix: /users
        routes:
            user-list:
                url: /
                action: "..."
            get-user:
                url: /{id}
                action: "..."
            user-login:
                url: /login
                action: "..."
                method: post
            user-logout:
                url: /logout
                action "..."
```

### Imports

if you have a lot of routes, it can be convenient to separate related routes into different files. In this case, you can
import files into a parent file by using the `imports` array:

```yaml
imports:
    - users.yml
    - shop/products.yml
    - shop/checkout.yml

groups:
    group: "..."
routes:
    route: "..."
```

Imported files are merged into a single configuration array before routes and groups are processed. Where route naming 
conflicts arise, the latter import will overwrite the former and the importing file will take precedence over any 
imported routes.

## Custom Templates

Lazy Boy uses a simple template system to create standard config and entry point files. It is possible to hook into this
system to extend Lazy Boy and install custom templates.

The extending library should be used as Lazy Boy is; required into an application as a composer dependency. The library 
itself should require Lazy Boy as normal, but then add extra data to the composer.json file to configure the templates:

```json
{
  "name": "silktide/lazy-boy-extension",
  "require": {
    "lazy-boy": "^2.0.0"
  },
  "extra": {
    "silktide/lazy-boy": {
      "templates": {
        "template-name": {
          "template": "[ file path of the template, relative to the library package root directory]",
          "output": "[ file path of the output file, relative to the application root directory ]"
        },
        "index": {
          "template": "[ the 'index' template already exists. You can override a template like this ... ]"
        },
        "console": {
          "output": "[ ... or change where it's written to by overriding the output ]"
        }
      }
    }
  }
}
``` 

As in the example config, you can replace an existing template with a custom one by using the same template name.
You can choose to override the template and/or the output file location.

Currently the following templates are predefined

| Template Name | Output Location         |
|---------------|-------------------------|
| bootstrap *   | app/bootstrap.php       |
| services      | app/config/services.yml |
| routes        | app/config/routes.yml   |
| console **    | app/console.php         |
| index         | web/index.php           |
| htaccess      | web/htaccess            |

\* *This template is protected and cannot be overridden*

** *This template depends on the `symfony/console` library being present in the package list*

## Contributing

If you have improvements you would like to see, open an issue in this github project or better yet, fork the project,
implement your changes and create a pull request.

The project uses [PSR-2] code styles and we insist that these are strictly adhered to. Also, please make sure that your
code works with php 5.4, so things like generators, `finally`, `empty(someFunction())`, etc... should be avoided

## Why "Lazy Boy"
Because it likes REST, of course :)


[Silex]: https://github.com/silexphp/silex
[Syringe]: https://github.com/silktide/syringe
[Puzzle-DI]: https://github.com/downsider/puzzle-di
[Symfony console]: https://github.com/symfony/console
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
