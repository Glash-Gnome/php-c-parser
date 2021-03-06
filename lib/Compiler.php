<?php

namespace PHPCParser;

use PHPCParser\Node\Decl\NamedDecl\TypeDecl\TypedefNameDecl\TypedefDecl;
use PHPCParser\Node\Decl;
use PHPCParser\Node\Type;
use PHPCParser\Node\Stmt\ValueStmt\Expr;
use PHPCParser\Node\Stmt;

class Compiler
{

    protected $scope;

    public function begin(Scope $scope) {
        $this->scope = $scope;
    }

    public function compileFunction(int $qualifiers, array $types, IR\Declarator $declarator, array $declarations, Stmt\CompoundStmt $stmts, array $attributes = []): array {
        $type = $this->compileType($types);
        $parts = $this->compileNamedDeclarator($declarator, $type);
        $name = $parts[0];
        $signature = $parts[1];
        if ($qualifiers !== 0) {
            $signature = Type\AttributedType::fromDecl($qualifiers, $signature, $attributes);
        }
        if (empty($declarations)) {
            return [new Decl\NamedDecl\ValueDecl\DeclaratorDecl\FunctionDecl($name, $signature, $stmts)];
        }
        throw new \LogicException('Not implemented (yet)');
    }

    public function compileExternalDeclaration(IR\Declaration $declaration, array $attributes = []): array {
        $qualifiers = $declaration->qualifiers;
        $isTypedef = false;
        $type = $this->compileType($declaration->types);
restart:
        $result = [];
        if ($declaration->qualifiers & Decl::KIND_TYPEDEF) {
            // this is wrong
            foreach ($declaration->declarators as $declarator) {
                $result[] = $this->compileTypedef($declarator, $type, $attributes);;
            }
        } elseif ($qualifiers === 0 && empty($declaration->declarators)) {
            if ($type instanceof Type\TagType) {
                if ($type->decl->name !== null) {
                    $this->scope->structdef($type->decl->name, $type->decl);
                }
                return [$type->decl];
            }
            throw new \LogicException('Also not implemented yet');
        } elseif ($qualifiers === 0) {
            foreach ($declaration->declarators as $initDeclarator) {
                $result[] = $this->compileInitDeclarator($initDeclarator, $type, $attributes);
            }     
        } elseif ($qualifiers > 0) {
            $type = Type\AttributedType::fromDecl($qualifiers, $type, $attributes);
            $qualifiers = 0;
            goto restart;
        } else {
            var_dump($declaration);
            throw new \LogicException("Not implmented yet");
        }
        return $result;
    }

    public function compileStructField(int $qualifiers, array $types, ?array $declarators, array $attributes = []): array {
        $result = [];
        $type = $this->compileType($types);
        if (is_null($declarators)) {
            throw new \LogicException("Not implemented yet: empty struct/union declarators");
        }
        foreach ($declarators as $fieldDeclarator) {
            $parts = $this->compileNamedDeclarator($fieldDeclarator->declarator, $type);
            $result[] = new Decl\NamedDecl\ValueDecl\DeclaratorDecl\FieldDecl($parts[0], $parts[1], $fieldDeclarator->initializer, $attributes);
        }
        return $result;
    }

    public function compileParamVarDeclaration(int $qualifiers, array $types, IR\Declarator $declarator, array $attributes = []): Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl {
        $type = $this->compileType($types);
        $parts = $this->compileNamedDeclarator($declarator, $type);
        if ($qualifiers !== 0) {
            $parts[1] = Type\AttributedType::fromDecl($qualifiers, $parts[1], $attributes);
        }
        return new Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl($parts[0], $parts[1], $attributes);
    }

    public function compileParamAbstractDeclaration(int $qualifiers, array $types, ?IR\AbstractDeclarator $declarator, array $attributes = []): Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl {
        $type = $this->compileType($types);
        if ($declarator !== null) {
            $type = $this->compileAbstractDeclarator($declarator, $type);
        }
        if ($qualifiers !== 0) {
            $type = Type\AttributedType::fromDecl($qualifiers, $type, $attributes);
        }
        return new Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl(null, $type, $attributes);
    }

    public function compileTypeReference(int $qualifiers, array $types, ?IR\AbstractDeclarator $declarator, array $attributes = []): Expr\TypeRefExpr {
        assert($qualifiers === 0);
        $type = $this->compileType($types);
        if ($declarator !== null) {
            $type = $this->compileAbstractDeclarator($declarator, $type);
        }
        return new Expr\TypeRefExpr($type);
    }

    public function compileType(array $types): Type {
restart:
        if (empty($types)) {
            throw new \LogicException('Cannot compile empty type list');
        }
        if (count($types) === 1) {
            return $types[0];
        }
        if ($types[0] instanceof Type\BuiltinType && $types[1] instanceof Type\BuiltinType) {
            // combine in order
            $first = array_shift($types);
            $types[0] = new Type\BuiltinType($first->name . ' ' . $types[0]->name, $first->getAttributes());
            goto restart;
        } elseif ($types[0] instanceof Type\BuiltinType && $types[1] instanceof Type\TypedefType) {
            $first = array_shift($types);
            $types[0] = new Type\BuiltinType($first->name . ' ' . $types[0]->name, $first->getAttributes());
            goto restart;
        }
        var_dump($types);
        // Todo
    }

    public function compileQualifiedPointer(IR\QualifiedPointer $pointer, Type $type): Type {
restart:
        $type = new Type\PointerType($type);
        if ($pointer->qualification > 0) {
            $type = Type\AttributedType::fromDecl($pointer->qualification, $type);
        }
        if ($pointer->parent !== null) {
            $pointer = $pointer->parent;
            goto restart;
        }
        return $type;
    }

    public function compileTypedef(IR\InitDeclarator $init, Type $type, array $attributes = []): Decl {
        if (!$init->initializer === null) {
            throw new \LogicException("Typedef cannot come with an initializer");
        }
        $declarator = $init->declarator;
        return $this->compileTypedefDeclarator($declarator, $type, $attributes);
    }

    public function compileInitDeclarator(IR\InitDeclarator $initDeclarator, Type $type, array $attributes = []): Decl {
        if ($initDeclarator->initializer !== null) {
            throw new \LogicException("Can't deal with non-null initializers yet");
        }
        $parts = $this->compileNamedDeclarator($initDeclarator->declarator, $type, $attributes);
        if ($parts[1] instanceof Type\FunctionType) {
            return new Decl\NamedDecl\ValueDecl\DeclaratorDecl\FunctionDecl($parts[0], $parts[1], null, $attributes);
        }
        return new Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl($parts[0], $parts[1], $initDeclarator->initializer, $attributes);
    }

    public function compileTypedefDeclarator(IR\Declarator $declarator, Type $type, array $attributes = []): Decl {
        $parts = $this->compileNamedDeclarator($declarator, $type, $attributes);
        $this->scope->typedef($parts[0], $parts[1]);
        return new TypedefDecl($parts[0], $parts[1], $attributes);
    }

    public function compileAbstractDeclarator(IR\AbstractDeclarator $declarator, Type $type): Type {
restart:
        if ($declarator->pointer !== null) {
            $type = $this->compileQualifiedPointer($declarator->pointer, $type);
        }
        $directabstractdeclarator = $declarator->declarator;
restart_direct:
        if (is_null($directabstractdeclarator)) {
            return $type;
        } elseif ($directabstractdeclarator instanceof IR\DirectAbstractDeclarator\AbstractDeclarator) {
            $type = new Type\ParenType($type);
            $declarator = $directabstractdeclarator->declarator;
            goto restart;
        } elseif ($directabstractdeclarator instanceof IR\DirectAbstractDeclarator\IncompleteArray) {
            $type = new Type\ArrayType\IncompleteArrayType($type);
            $directabstractdeclarator = $directabstractdeclarator->declarator;
            goto restart_direct;
        }
        var_dump($directabstractdeclarator);
        throw new \LogicException('AbstractDeclarator not fully implemented yet');
    }

    public function compileNamedDeclarator(IR\Declarator $declarator, Type $type): array {
restart:
        if ($declarator->pointer !== null) {
            $type = $this->compileQualifiedPointer($declarator->pointer, $type);
        }
        $directdeclarator = $declarator->declarator;
restart_direct:
        if ($directdeclarator instanceof IR\DirectDeclarator\Identifier) {
            return [$directdeclarator->name, $type];
        } elseif ($directdeclarator instanceof IR\DirectDeclarator\IncompleteArray) {
            $type = new Type\ArrayType\IncompleteArrayType($type);
            $directdeclarator = $directdeclarator->declarator;
            goto restart_direct;
        } elseif ($directdeclarator instanceof IR\DirectDeclarator\CompleteArray) {
            if ($directdeclarator->size->isConstant()) {
                $type = new Type\ArrayType\ConstantArrayType($type, $directdeclarator->size);
            } else {
                $type = new Type\ArrayType\VariableArrayType($type, $directdeclarator->size);
            }
            $directdeclarator = $directdeclarator->declarator;
            goto restart_direct;
        } elseif ($directdeclarator instanceof IR\DirectDeclarator\Declarator) {
            $type = new Type\ParenType($type);
            $declarator = $directdeclarator->declarator;
            goto restart;
        } elseif ($directdeclarator instanceof IR\DirectDeclarator\Function_) {
            $type = new Type\FunctionType\FunctionProtoType(
                $type,
                $this->compileDirectParamTypes(...$directdeclarator->params),
                $this->compileDirectParamTypeNames(...($directdeclarator->params)),
                $directdeclarator->isVariadic
            );
            $directdeclarator = $directdeclarator->name;
            goto restart_direct;
        }
        var_dump($directdeclarator);
        throw new \LogicException("Unknown declarator found for typedef");
    }

    public function compileDirectParamTypes(Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl ... $params): array {
        $result = [];
        foreach ($params as $param) {
            $result[] = $param->type;
        }
        return $result;
    }

    public function compileDirectParamTypeNames(Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl\ParmVarDecl ... $params): array {
        $result = [];
        foreach ($params as $param) {
            $result[] = $param->name;
        }
        return $result;
    }
}
