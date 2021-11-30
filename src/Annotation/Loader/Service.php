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
final class Service implements LoaderAttributeInterface, NamedArgumentConstructorAnnotation
{
    /**
     * @Attribute(name="class", type="string", required=true)
     */
    public string $class;

    /**
     * @Attribute(name="method", type="string", required=true)
     */
    public mixed $method;

    /**
     * @Attribute(name="extraArguments", type="array", required=false)
     */
    public array $extraArguments;

    public function __construct(string $class, string $method, array $extraArguments = [])
    {
        $this->class = $class;
        $this->method = $method;
        $this->extraArguments = $extraArguments;
    }
}
