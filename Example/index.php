<?php
declare(strict_types=1);
require_once "../vendor/autoload.php";
use InitPHP\Container\Container;


require_once "User.php";
require_once "UserModel.php";

$container = new Container();

$user = $container->get(\Example\User::class);
$model = $user->getModel();
$model->set('Muhammet');
echo $user->getModel()->get();

