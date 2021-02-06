<?php declare(strict_types=1);

namespace PHPCParser\Node\Type\ArrayType;

use PHPCParser\Node\Type\ArrayType;
use PHPCParser\Node\Type;
use PHPCParser\Node\Stmt\ValueStmt\Expr;

class ConstantArrayType extends ArrayType
{
    public $parent;
    public $size;

    public function __construct(Type $parent, Expr $size, array $attributes = []) {
        parent::__construct($attributes);
        $this->parent = $parent;
        $this->size = $size;
    }

    public function getSubNodeNames(): array {
        return ['parent', 'size'];
    }

}
