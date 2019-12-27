<?php

namespace JanGregor\Prophecy\PhpDoc;

use JanGregor\Prophecy\Type\ObjectProphecyType;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Prophecy\Prophecy\ObjectProphecy;

class TypeNodeResolverExtension implements \PHPStan\PhpDoc\TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    /**
     * @var TypeNodeResolver
     */
    private $typeNodeResolver;

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }

    public function getCacheKey(): string
    {
        return 'prophecy-v1';
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if (!$typeNode instanceof UnionTypeNode) {
            return null;
        }

        if (2 === \count($typeNode->types)) {
            $objectProphecyType = null;
            $prophesizedType = null;

            foreach ($typeNode->types as $innerType) {
                $type = $this->typeNodeResolver->resolve($innerType, $nameScope);

                if ($type instanceof TypeWithClassName) {
                    if (ObjectProphecy::class === $type->getClassName()) {
                        $objectProphecyType = $type;
                    } else {
                        $prophesizedType = $type;
                    }
                } else {
                    break;
                }
            }

            if (null !== $objectProphecyType && null !== $prophesizedType) {
                return new ObjectProphecyType($prophesizedType->getClassName());
            }
        }

        return null;
    }
}
