<?php

namespace Zan\CommonBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Zan\CommonBundle\Util\ZanArray;

class ZanArrayTest extends TestCase
{

    public function testCreateFromStringHandlesArrays()
    {
        $input = [ 'one', 'two' ];

        $r = ZanArray::createFromString($input);

        $this->assertEquals($input, $r);
    }

    public function testCreateFromStringWithCommas()
    {
        $input = 'one, two, three';

        $r = ZanArray::createFromString($input);

        $this->assertCount(3, $r);
        $this->assertEquals('one', $r[0]);
        $this->assertEquals('two', $r[1]);
        $this->assertEquals('three', $r[2]);
    }

    public function testCreateFromStringReturnsEmptyArrayFromNull()
    {
        $r = ZanArray::createFromString(null);

        $this->assertEquals([], $r);
    }
}
