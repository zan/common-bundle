<?php

namespace Zan\CommonBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Zan\CommonBundle\Util\RequestUtils;

class RequestUtilsTest extends TestCase
{

    public function testGetParametersBasic()
    {
        $params = RequestUtils::getParametersFromQueryString('name1=val1&name2=val2');

        $this->assertCount(2, $params);
        $this->assertEquals('val1', $params['name1']);
        $this->assertEquals('val2', $params['name2']);
    }

    /**
     * Verifies that query strings like arrVal=one&arrVal=two return arrVal as an array
     */
    public function testGetParametersCgiArraySyntax()
    {
        $params = RequestUtils::getParametersFromQueryString('name1=val1&arrVal=one&arrVal=two');

        $this->assertCount(2, $params);
        $this->assertEquals('val1', $params['name1']);

        $this->assertIsArray($params['arrVal']);
        $this->assertEquals('one', $params['arrVal'][0]);
        $this->assertEquals('two', $params['arrVal'][1]);
    }

    /**
     * Verifies that query strings like arrVal=one&arrVal=two work if one of the names appears in the value
     */
    public function testGetParametersCgiArrayParamInData()
    {
        $params = RequestUtils::getParametersFromQueryString('name1=val1&arrVal=one&arrVal=arrVal');

        $this->assertCount(2, $params);
        $this->assertEquals('val1', $params['name1']);

        $this->assertIsArray($params['arrVal']);
        $this->assertEquals('one', $params['arrVal'][0]);
        $this->assertEquals('arrVal', $params['arrVal'][1]);
    }
}
