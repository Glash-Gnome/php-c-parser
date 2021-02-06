<?php declare(strict_types=1);

namespace PHPCParser\Node\Type\TagType;

use PHPCParser\Node\Type\TagType;
use PHPCParser\Node\Decl\NamedDecl\TypeDecl\TagDecl\RecordDecl;

class RecordType extends TagType
{
    public $decl;

    public function __construct(RecordDecl $decl, array $attributes = []) {
        parent::__construct($attributes);
        $this->decl = $decl;
    }

    public function getSubNodeNames(): array {
        return ['decl'];
    }

}
