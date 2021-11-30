<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Exception;

final class EntityNotFoundException extends \Exception
{
    public function __construct(string $entity, string $by, string $value)
    {
        $this->message = sprintf(
            "Could not find entity '%s' by condition '%s = %s'.",
            $entity,
            $by,
            $value
        );
    }
}
