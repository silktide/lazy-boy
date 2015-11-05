# lazy-boy
A skeleton REST API application, using [Silex] and [Syringe] with support for [Puzzle-DI]

## Summary
Lazy boy will create a skeleton [Silex] framework, so you can create REST APIs without having to bother with 
boilerplate code.

It is packaged with a route loader and uses [Syringe], which allows you to define both your routes and services in 
configuration files, rather than PHP

If you have the [Symfony console] installed, it will also create a console script and automatically load any commands it
finds in the service container (any service name which ends with ".command" and is an instance of the Symfony Command 
class). You can also use [Puzzle-DI] to load service configuration from modules.

## Installation
install using composer:

    composer require silktide/lazy-boy:^1.0

Once installed, add the following scripts to your composer.json file ...
 
    "scripts": {
      "post-update-cmd": [
        "Silktide\\LazyBoy\\Controller\\ScriptController::install"
      ],
      "post-install-cmd": [
        "Silktide\\LazyBoy\\Controller\\ScriptController::install"
      ],
      "install-lazy-boy": [
        "Silktide\\LazyBoy\\Controller\\ScriptController::install"
      ]
    }

... and run this command

    composer install-lazy-boy
    
This will generate several files from Lazy Boy templates. You are free to make modifications; Lazy Boy will not overwrite 
a file which already exists, so committing those changes to a VCS is safe. Having your VCS ignore the files will
mean they are generated the first time you run `composer update` or `composer install` on a freshly cloned repository.
You can also regenerate the files by deleting them and running the install command.

All that is left to do is create a vhost or otherwise point requests to `web/index.php`.
 
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