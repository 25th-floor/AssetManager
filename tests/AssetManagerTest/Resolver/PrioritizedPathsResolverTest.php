<?php

namespace AssetManagerTest\Service;

use PHPUnit_Framework_TestCase;
use ArrayObject;
use AssetManager\Resolver\PrioritizedPathsResolver;

class PrioritizedPathsResolverTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $resolver = new PrioritizedPathsResolver();
        $this->assertEmpty($resolver->getPaths()->toArray());

        $resolver->addPaths(array(__DIR__));
        $this->assertEquals(array(__DIR__ . DIRECTORY_SEPARATOR), $resolver->getPaths()->toArray());

        $resolver->clearPaths();
        $this->assertEquals(array(), $resolver->getPaths()->toArray());
    }

    public function testSetPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array(
            array(
                'path' => 'dir3',
                'priority' => 750,
            ),
            array(
                'path' => 'dir2',
                'priority' => 1000,
            ),
            array(
                'path' => 'dir1',
                'priority' => 500,
            ),
        ));

        $this->assertTrue($resolver->getPaths()->hasPriority(1000));
        $this->assertTrue($resolver->getPaths()->hasPriority(500));

        $fetched = array();

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        // order inverted because of how a stack is traversed
        $this->assertSame(
            array('dir2' . DIRECTORY_SEPARATOR, 'dir3' . DIRECTORY_SEPARATOR, 'dir1' . DIRECTORY_SEPARATOR),
            $fetched
        );

        $this->setExpectedException('AssetManager\Exception\InvalidArgumentException');
        $resolver->setPaths('invalid');
    }

    public function testSetPathsAllowsStringPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array('dir1', 'dir2', 'dir3'));

        $paths = $resolver->getPaths()->toArray();
        $this->assertCount(3, $paths);
        $this->assertContains('dir1' . DIRECTORY_SEPARATOR, $paths);
        $this->assertContains('dir2' . DIRECTORY_SEPARATOR, $paths);
        $this->assertContains('dir3' . DIRECTORY_SEPARATOR, $paths);

    }

    public function testWillValidateGivenPathArray()
    {
        $resolver = new PrioritizedPathsResolver();
        $this->setExpectedException('AssetManager\Exception\InvalidArgumentException');
        $resolver->addPath(array('invalid'));
    }

    public function testResolve()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath(__DIR__);

        $this->assertEquals(__FILE__, $resolver->resolve(basename(__FILE__)));
        $this->assertNull($resolver->resolve('i-do-not-exist.php'));
    }

    public function testWillNotResolveDirectories()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath(__DIR__ . '/..');

        $this->assertNull($resolver->resolve(basename(__DIR__)));
    }

    public function testLfiProtection()
    {
        $resolver = new PrioritizedPathsResolver();
        // should be on by default
        $this->assertTrue($resolver->isLfiProtectionOn());
        $resolver->addPath(__DIR__);

        $this->assertNull($resolver->resolve(
            '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
        ));

        $resolver->setLfiProtection(false);

        $this->assertSame(
            __FILE__,
            $resolver->resolve(
                '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
            )
        );
    }

    public function testWillRefuseInvalidPath()
    {
        $resolver = new PrioritizedPathsResolver();
        $this->setExpectedException('AssetManager\Exception\InvalidArgumentException');
        $resolver->addPath(null);
    }
}
