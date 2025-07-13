<?php

declare(strict_types=1);

namespace MiniDI\Tests;

use MiniDI\Container;
use MiniDI\Exceptions\CircularDependencyException;
use PHPUnit\Framework\TestCase;

interface FooInterface
{
}

class Foo implements FooInterface
{
}

class Bar
{
    public function __construct(public FooInterface $foo)
    {
    }
}

class Baz
{
    public function __construct(public Bar $bar, public Foo $foo)
    {
    }
}

class A
{
    public function __construct(public B $b)
    {
    }
}

class B
{
    public function __construct(public A $a)
    {
    }
}

class ContainerTest extends TestCase
{
    public function testBindAndResolveByInterface(): void
    {
        $c = new Container();
        $c->bind(FooInterface::class, Foo::class);
        $instance = $c->make(FooInterface::class);
        self::assertInstanceOf(Foo::class, $instance);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $c = new Container();
        $c->singleton(FooInterface::class, Foo::class);
        self::assertSame($c->make(FooInterface::class), $c->make(FooInterface::class));
    }

    public function testTransientReturnsDifferentInstances(): void
    {
        $c = new Container();
        $c->bind(FooInterface::class, Foo::class);
        self::assertNotSame($c->make(FooInterface::class), $c->make(FooInterface::class));
    }

    public function testAutowireChain(): void
    {
        $c = new Container();
        $c->bind(FooInterface::class, Foo::class);
        $baz = $c->make(Baz::class);
        self::assertInstanceOf(Baz::class, $baz);
        self::assertInstanceOf(Bar::class, $baz->bar);
            self::assertSame($baz->foo, $baz->bar->foo);
    }

    public function testCircularDependencyThrows(): void
    {
        $this->expectException(CircularDependencyException::class);
        (new Container())->make(A::class);
    }
}