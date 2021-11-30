<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Loader;

use Ruvents\SpiralInput\Annotation\Loader\ArrayOf;
use Ruvents\SpiralInput\Annotation\Loader\Constructor;
use Ruvents\SpiralInput\Annotation\Loader\Entity;
use Ruvents\SpiralInput\Annotation\Loader\LoaderAttributeInterface;
use Ruvents\SpiralInput\Annotation\Loader\Service;
use Cycle\ORM\ORMInterface;
use Psr\Container\ContainerInterface;

final class Loader
{
    public function __construct(
        private ContainerInterface $container,
        private ORMInterface $orm
    ) {
    }

    public function load(mixed $value, LoaderAttributeInterface $attribute): mixed
    {
        return match (\get_class($attribute)) {
            Entity::class => $this->orm->getRepository($attribute->entity)->findOne([$attribute->by => $value]),
            Service::class => $this->container->get($attribute->class)
                ->{$attribute->method}($value, ...$attribute->extraArguments),
            Constructor::class => $attribute->named
                ? new ($attribute->class)(...$value)
                : new ($attribute->class)($value),
            ArrayOf::class => $this->arrayOf($value, $attribute)
        };
    }

    private function arrayOf(mixed $value, ArrayOf $attribute): array
    {
        if (false === \is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$value of ArrayOf loader must be of array type, "%s" given,',
                    \gettype($value)
                )
            );
        }

        $loader = new ($attribute->loader)(...$attribute->args);

        if ($loader instanceof ArrayOf) {
            throw new \LogicException('Recursive call of ArrayOf loader.');
        }

        $result = [];

        foreach ($value as $key => $item) {
            $result[$key] = $this->load($item, $loader);
        }

        return $result;
    }
}
