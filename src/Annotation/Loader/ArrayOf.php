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
final class ArrayOf implements LoaderAttributeInterface, NamedArgumentConstructorAnnotation
{
    /**
     * @Attribute(name="loader", required=true)
     */
    public string $loader;

    /**
     * @Attribute(name="args", type="array", required=false)
     */
    public array $args;

    public function __construct(string $loader, array $args = [])
    {
        $this->loader = $loader;
        $this->args = $args;
    }
}
