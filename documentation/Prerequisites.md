# QuickRep Reporting Engine Server Prerequisites

An adept PHP reporting engine, seamlessly integrated with Laravel, proudly developed by [Owlookit Systems](https://owlookit.com).

## Complete Prerequisites

Before installing the QuickRep Reporting Engine, ensure that your server meets the following prerequisites:

- **PHP Version**: PHP 8.0 or higher must be installed, with PHP 8.1 or newer preferred due to its support for nullable type declarations and upcoming features like encrypted zip file handling.

- **Composer**: The Composer dependency manager is required. For installation instructions, see the [Composer Getting Started](https://getcomposer.org/) guide.

- **Laravel 8.x Server Requirements**: Your server should support the following requirements for Laravel 8.x:
    ```
    PHP >= 8.0.3
    OpenSSL PHP Extension
    PDO PHP Extension
    Mbstring PHP Extension
    Tokenizer PHP Extension
    XML PHP Extension
    ```

- **PostgreSQL**: A PostgreSQL server is needed, along with a user account that has CREATE TABLE permissions.

- **Laravel Installation**: Laravel 8.x should be installed and functional. Consult the [Laravel 8.x Installation Instructions](https://laravel.com/docs/8.x/installation) for guidance.

- **Laravel's Homestead**: Optionally, use Laravel's Homestead VM in conjunction with Vagrant to create a virtual machine pre-configured with all the necessary dependencies. For more information, refer to [Laravel Homestead Installation](https://laravel.com/docs/8.x/homestead).

To start off on the right foot, particularly if you're using Homestead, use Composer within the Homestead Box (via `vagrant ssh`) to ensure you download the correct version of Laravel:


  ```
  composer create-project laravel/laravel quickrep-demo  "8.*.*" --prefer-dist
  ```


  ```
  composer create-project laravel/laravel quickrep-demo  "8.*.*" --prefer-dist
  ```

