<?php declare(strict_types=1);

namespace PHPCParser\Node\Decl\NamedDecl\ValueDecl\DeclaratorDecl;

use PHPCParser\Node\Decl\NamedDecl\ValueDecl\DeclaratorDecl;

use PHPCParser\Node\Type;
use PHPCParser\Node\Stmt;

class FunctionDecl extends DeclaratorDecl
{

    public $name;
    public $type;
    public $stmts;

    public function __construct(string $name, Type $type, ?Stmt\CompoundStmt $stmts, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->type = $type;
        $this->stmts = $stmts;
    }

    public function getSubNodeNames(): array {
        return ['name', 'type', 'stmts'];
    }

}
