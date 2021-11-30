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
final class Constructor implements LoaderAttributeInterface, NamedArgumentConstructorAnnotation
{
    /**
     * @Attribute(name="class", type="string", required=true)
     */
    public string $class;

    /**
     * @Attribute(name="named", type="bool")
     */
    public bool $named;

    public function __construct(string $class, bool $named = false)
    {
        $this->class = $class;
        $this->named = $named;
    }
}
