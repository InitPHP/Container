<?php
declare(strict_types=1);

namespace Example;

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
