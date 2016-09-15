<?php


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
    }
}
