<?php declare(strict_types=1);

namespace PHPCParser\IR\DirectDeclarator;

use PHPCParser\IR\DirectDeclarator;

class Function_ extends DirectDeclarator
{
    public $name;
    public $params;
    public $isVariadic;

    public function __construct(DirectDeclarator $name, array $params, bool $isVariadic, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->params = $params;
        $this->isVariadic = $isVariadic;
    }
}
