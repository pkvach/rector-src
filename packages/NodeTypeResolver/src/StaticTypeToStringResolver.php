<?php declare(strict_types=1);

namespace Rector\NodeTypeResolver;

use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class StaticTypeToStringResolver
{
    /**
     * @var string[]
     */
    private $typeClassToTypeHint = [
        CallableType::class => 'callable',
        ClosureType::class => 'callable',
        IntegerType::class => 'int',
        FloatType::class => 'float',
        BooleanType::class => 'bool',
        StringType::class => 'string',
        NullType::class => 'null',
    ];

    /**
     * @return string[]
     */
    public function resolve(?Type $staticType): array
    {
        if ($staticType === null) {
            return [];
        }

        foreach ($this->typeClassToTypeHint as $typeClass => $typeHint) {
            if (is_a($staticType, $typeClass, true)) {
                return [$typeHint];
            }
        }

        if ($staticType instanceof UnionType) {
            return $this->resolveUnionType($staticType);
        }

        if ($staticType instanceof ArrayType) {
            return $this->resolveArrayType($staticType);
        }

        if ($staticType instanceof ObjectType) {
            // the must be absolute, since we have no other way to check absolute/local path
            return ['\\' . $staticType->getClassName()];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function resolveUnionType(UnionType $unionType): array
    {
        $types = [];
        foreach ($unionType->getTypes() as $singleStaticType) {
            $types = array_merge($types, $this->resolve($singleStaticType));
        }

        return $types;
    }

    /**
     * @return string[]
     */
    private function resolveArrayType(ArrayType $arrayType): array
    {
        $types = $this->resolve($arrayType->getItemType());
        foreach ($types as $key => $type) {
            $types[$key] = $type . '[]';
        }

        return $types;
    }
}
