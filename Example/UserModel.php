<?php
declare(strict_types=1);

namespace Example;

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
