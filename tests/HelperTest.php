<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    public function testEnv()
    {
        putenv('var_true=true');
        putenv('var_false=false');

        $this->assertSame(true, env('var_true'));
        $this->assertSame(false, env('var_false'));
        $this->assertSame('', env('var_empty'));
        $this->assertSame(null, env('var_null'));

        $this->assertSame(true, env('var_true_parentheses'));
        $this->assertSame(false, env('var_false_parentheses'));
        $this->assertSame('', env('var_empty_parentheses'));
        $this->assertSame(null, env('var_null_parentheses'));

        putenv('var_quotes="quotes"');
        $this->assertSame('quotes', env('var_quotes'));

        putenv('var_basic=basic_test');
        $this->assertSame('basic_test', env('var_basic'));
    }
}
