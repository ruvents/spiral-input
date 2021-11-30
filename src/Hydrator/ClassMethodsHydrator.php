<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Hydrator;

use Laminas\Hydrator\AbstractHydrator;
use Laminas\Hydrator\Filter;
use Laminas\Hydrator\HydratorOptionsInterface;
use Laminas\Hydrator\NamingStrategy;
use Laminas\Stdlib\ArrayUtils;

/**
 * TODO: создать PR в laminas/laminas-hydrator с объявлением
 * protected identifyAttributeName, чтобы не копировать весь класс.
 */
class ClassMethodsHydrator extends AbstractHydrator implements HydratorOptionsInterface
{
    /**
     * Flag defining whether array keys are underscore-separated (true) or camel case (false).
     *
     * @var bool
     */
    protected $underscoreSeparatedKeys = true;

    /**
     * Flag defining whether to check the setter method with method_exists to prevent the
     * hydrator from calling __call during hydration.
     *
     * @var bool
     */
    protected $methodExistsCheck = false;

    /**
     * Holds the names of the methods used for hydration, indexed by class::property name,
     * false if the hydration method is not callable/usable for hydration purposes.
     *
     * @var bool[]|string[]
     */
    private $hydrationMethodsCache = [];

    /**
     * @var null[]|string[][]
     */
    private $extractionMethodsCache = [];

    /**
     * @var Filter\FilterInterface
     */
    private $callableMethodFilter;

    public function __construct(bool $underscoreSeparatedKeys = true, bool $methodExistsCheck = false)
    {
        $this->setUnderscoreSeparatedKeys($underscoreSeparatedKeys);
        $this->setMethodExistsCheck($methodExistsCheck);

        $this->callableMethodFilter = new Filter\OptionalParametersFilter();

        $compositeFilter = $this->getCompositeFilter();
        $compositeFilter->addFilter('is', new Filter\IsFilter());
        $compositeFilter->addFilter('has', new Filter\HasFilter());
        $compositeFilter->addFilter('get', new Filter\GetFilter());
        $compositeFilter->addFilter(
            'parameter',
            new Filter\OptionalParametersFilter(),
            Filter\FilterComposite::CONDITION_AND
        );
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(iterable $options): void
    {
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (isset($options['underscoreSeparatedKeys'])) {
            $this->setUnderscoreSeparatedKeys($options['underscoreSeparatedKeys']);
        }

        if (isset($options['methodExistsCheck'])) {
            $this->setMethodExistsCheck($options['methodExistsCheck']);
        }
    }

    public function setUnderscoreSeparatedKeys(bool $underscoreSeparatedKeys): void
    {
        $this->underscoreSeparatedKeys = $underscoreSeparatedKeys;

        if ($this->underscoreSeparatedKeys) {
            $this->setNamingStrategy(new NamingStrategy\UnderscoreNamingStrategy());

            return;
        }

        if ($this->hasNamingStrategy()) {
            $this->removeNamingStrategy();

            return;
        }
    }

    public function getUnderscoreSeparatedKeys(): bool
    {
        return $this->underscoreSeparatedKeys;
    }

    public function setMethodExistsCheck(bool $methodExistsCheck): void
    {
        $this->methodExistsCheck = $methodExistsCheck;
    }

    public function getMethodExistsCheck(): bool
    {
        return $this->methodExistsCheck;
    }

    /**
     * {@inheritDoc}
     */
    public function extract(object $object): array
    {
        $objectClass = \get_class($object);
        $isAnonymous = false !== strpos($objectClass, '@anonymous');

        if ($isAnonymous) {
            $objectClass = spl_object_hash($object);
        }

        // reset the hydrator's hydrator's cache for this object, as the filter may be per-instance
        if ($object instanceof Filter\FilterProviderInterface) {
            $this->extractionMethodsCache[$objectClass] = null;
        }

        // pass 1 - finding out which properties can be extracted, with which methods (populate hydration cache)
        if (!isset($this->extractionMethodsCache[$objectClass])) {
            $this->extractionMethodsCache[$objectClass] = [];

            $filter = $this->initCompositeFilter($object);
            $methods = get_class_methods($object);

            foreach ($methods as $method) {
                $methodFqn = $isAnonymous
                    ? $method
                    : $objectClass.'::'.$method;

                if (
                    false === $filter->filter($methodFqn, $isAnonymous ? $object : null)
                    || !$this->callableMethodFilter->filter($methodFqn, $isAnonymous ? $object : null)
                ) {
                    continue;
                }

                $this->extractionMethodsCache[$objectClass][$method] = $this->identifyAttributeName($object, $method);
            }
        }

        $values = [];

        if (null === $this->extractionMethodsCache[$objectClass]) {
            return $values;
        }

        // pass 2 - actually extract data
        foreach ($this->extractionMethodsCache[$objectClass] as $methodName => $attributeName) {
            $realAttributeName = $this->extractName($attributeName, $object);
            $values[$realAttributeName] = $this->extractValue($realAttributeName, $object->$methodName(), $object);
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, object $object)
    {
        $objectClass = \get_class($object);

        foreach ($data as $property => $value) {
            $propertyFqn = $objectClass.'::$'.$property;

            if (!isset($this->hydrationMethodsCache[$propertyFqn])) {
                $setterName = 'set'.ucfirst($this->hydrateName($property, $data));

                $this->hydrationMethodsCache[$propertyFqn] = \is_callable([$object, $setterName])
                    && (!$this->methodExistsCheck || method_exists($object, $setterName))
                    ? $setterName
                    : false;
            }

            if ($this->hydrationMethodsCache[$propertyFqn]) {
                $object->{$this->hydrationMethodsCache[$propertyFqn]}($this->hydrateValue($property, $value, $data));
            }
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function addFilter(string $name, $filter, int $condition = Filter\FilterComposite::CONDITION_OR): void
    {
        $this->resetCaches();
        parent::addFilter($name, $filter, $condition);
    }

    /**
     * {@inheritDoc}
     */
    public function removeFilter(string $name): void
    {
        $this->resetCaches();
        parent::removeFilter($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setNamingStrategy(NamingStrategy\NamingStrategyInterface $strategy): void
    {
        $this->resetCaches();
        parent::setNamingStrategy($strategy);
    }

    /**
     * {@inheritDoc}
     */
    public function removeNamingStrategy(): void
    {
        $this->resetCaches();
        parent::removeNamingStrategy();
    }

    private function initCompositeFilter(object $object): Filter\FilterComposite
    {
        if ($object instanceof Filter\FilterProviderInterface) {
            return new Filter\FilterComposite(
                [$object->getFilter()],
                [new Filter\MethodMatchFilter('getFilter')]
            );
        }

        return $this->getCompositeFilter();
    }

    private function identifyAttributeName(object $object, string $method): string
    {
        if (0 === strpos($method, 'get')) {
            $attribute = substr($method, 3);

            return property_exists($object, $attribute) ? $attribute : lcfirst($attribute);
        }

        if (0 === strpos($method, 'is')) {
            $attribute = substr($method, 2);

            return property_exists($object, $attribute) ? $attribute : lcfirst($attribute);
        }

        return $method;
    }

    private function resetCaches(): void
    {
        $this->hydrationMethodsCache = $this->extractionMethodsCache = [];
    }
}
