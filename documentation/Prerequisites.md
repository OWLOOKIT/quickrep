QuickRep Reporting Engine Server Prerequisites
========

A PHP reporting engine that works especially well with Laravel, built with love at [Owlookit Systems](https://owlookit.com)

## Complete Prerequisites
- PHP 8.0.+ installed, 8.1.+ preferred (required for nullable type declarations, and soon encrypted zip files)
- Composer Installed. See [Composer Getting Started](https://getcomposer.org/)
- Server requirements for Laravel 8.x:
```
    PHP >= 8.0.3
    OpenSSL PHP Extension
    PDO PHP Extension
    Mbstring PHP Extension
    Tokenizer PHP Extension
    XML PHP Extension
```
- MYSQL server, and user with CREATE TABLE permissions
  
- Installed and functioning Laravel 8.x. See [Laravel 8.x Installation Instructions](https://laravel.com/docs/8.x/installation)

- Optionally you can use Laravel's Homestead VM and Vagrant to create a VM with all the correct dependencies. See [Laravel Homestead Installation](https://laravel.com/docs/8.x/homestead)

  A good way to start is to use composer to insure you download correct version, do this inside the Homestead Box (vagrant ssh) if you are using Homestead.
  
  ```
  composer create-project laravel/laravel quickrep-demo  "8.*.*" --prefer-dist
  ```

