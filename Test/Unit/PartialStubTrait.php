<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit;

/**
 * Build partial stubs across the supported PHPUnit versions.
 */
trait PartialStubTrait
{
    /**
     * Create a double that retains the unstubbed methods.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param string[] $methods
     * @param array|null $constructorArgs
     * @return T
     */
    private function createPartialStub(string $class, array $methods, ?array $constructorArgs = null): object
    {
        if (method_exists($this, 'getStubBuilder')) {
            $builder = $this->getStubBuilder($class)->onlyMethods($methods);
            if ($constructorArgs === null) {
                $builder->disableOriginalConstructor();
            } else {
                $builder->setConstructorArgs($constructorArgs);
            }

            return $builder->getStub();
        }

        $builder = $this->getMockBuilder($class)->onlyMethods($methods);
        if ($constructorArgs === null) {
            $builder->disableOriginalConstructor();
        } else {
            $builder->setConstructorArgs($constructorArgs);
        }

        return $builder->getMock();
    }
}
