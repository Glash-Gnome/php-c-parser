<?php declare(strict_types=1);

namespace PHPCParser\IR;

use PHPCParser\IR;

class QualifiedPointer extends IR
{
    public $qualification;
    public $parent;


    public function __construct(int $qualification, ?self $parent, array $attributes = []) {
        parent::__construct($attributes);
        $this->qualification = $qualification;
        $this->parent = $parent;
    }
}
