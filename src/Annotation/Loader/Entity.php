<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Annotation\Loader;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * @Annotation()
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY"})
 */
#[Attribute()]
final class Entity implements LoaderAttributeInterface, NamedArgumentConstructorAnnotation
{
    /**
     * @Attribute(name="entity", required=true)
     */
    public string $entity;

    /**
     * @Attribute(name="by", type="string", required=false)
     */
    public mixed $by;

    public function __construct(string $entity, string $by = 'id')
    {
        $this->entity = $entity;
        $this->by = $by;
    }
}
