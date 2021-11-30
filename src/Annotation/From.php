<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * @Annotation()
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY"})
 */
#[Attribute]
final class From implements NamedArgumentConstructorAnnotation
{
    /**
     * @Attribute(name="source", required=true)
     */
    public string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }
}
