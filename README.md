# InitPHP Dependencies Container
Simple Dependencies Container following PSR-11 standards.

_Note :_ This is a pre-release version of the library currently available. Report potential bugs and feature requests to the issue section of this repo.

## Requirements

- PHP 7.4 or higher
- [PSR-11 Container Interface Package](https://packagist.org/packages/psr/container) 2.0.2

## Installation

```
composer require initphp/container:dev-main
```

## Usage

Check the `Example` directory for an example usage.

```php
require_once "vendor/autoload.php";
use InitPHP\Container\Container;

class UserModel
{
    private string $name;

    public function set(string $name)
    {
        $this->name = $name;
    }
    
    public function get()
    {
        return $this->name ?? null;
    }
}

class User
{
    private $model;

    public function __construct(UserModel $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}

$container = new Container();
$user = $container->get(\Example\User::class);
$model = $user->getModel();
$model->set('Muhammet');
echo $user->getModel()->get();
```

## Contributing

> All contributions to this project will be published under the MIT License. By submitting a pull request or filing a bug, issue, or feature request, you are agreeing to comply with this waiver of copyright interest.

1. Fork it ( https://github.com/initphp/container/fork )
2. Create your feature branch (git checkout -b my-new-feature)
3. Commit your changes (git commit -am "Add some feature")
4. Push to the branch (git push origin my-new-feature)
5. Create a new Pull Request

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE) 
