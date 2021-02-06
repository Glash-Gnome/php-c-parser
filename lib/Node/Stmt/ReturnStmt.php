<?php declare(strict_types=1);

namespace PHPCParser\Node\Stmt;

use PHPCParser\Node\Stmt;
use PHPCParser\Node\Stmt\ValueStmt\Expr;

class ReturnStmt extends Stmt
{

    public $result;

    public function __construct(?Expr $result, array $attributes = []) {
        $this->result = $result;
    }

    public function getSubNodeNames(): array {
        return ['result'];
    }
}
