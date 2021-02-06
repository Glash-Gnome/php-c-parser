<?php declare(strict_types=1);

namespace PHPCParser\IR;

use PHPCParser\IR;

class AbstractDeclarator extends IR
{
    public $pointer;
    public $declarator;


    public function __construct(?QualifiedPointer $pointer, ?DirectAbstractDeclarator $declarator, array $attributes = []) {
        parent::__construct($attributes);
        $this->pointer = $pointer;
        $this->declarator = $declarator;
    }
}
