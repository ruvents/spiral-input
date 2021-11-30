<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use Spiral\Attributes\NamedArgumentConstructorAttribute;

/**
 * @Annotation()
 * @NamedArgumentConstructor()
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class HydrateFromEntity implements NamedArgumentConstructorAttribute
{
    /**
     * @Attribute(name="entity", type="string", required=true)
     */
    public string $entity;

    /**
     * @Attribute(name="from", type="string", required=true)
     */
    public string $from;

    /**
     * @Attribute(name="loadBy", type="string", required=false)
     */
    public string $loadBy;

    /**
     * @Attribute(name="loadBy", type="string", required=false)
     */
    public array $exclude;

    public function __construct(
        string $entity,
        string $from,
        string $loadBy = 'id',
        array $exclude = []
    ) {
        $this->entity = $entity;
        $this->from = $from;
        $this->loadBy = $loadBy;
        $this->exclude = $exclude;
    }
}
