<?php namespace Ipunkt\RabbitMQTests\Unit;

use Ipunkt\RabbitMQ\Test\TestsRabbitMQ;
use Ipunkt\RabbitMQTests\TestCase;

/**
 * Class TestsRabbitMQTest
 * @package Ipunkt\RabbitMQTests\Unit
 */
class TestsRabbitMQTest extends TestCase
{
    use TestsRabbitMQ;

    /**
     * @test
     */
    public function compare_array_works_with_simple_values()
    {
        $expected = [
            'a' => 5,
        ];
        $actual = [
            'a' => 5,
        ];

        $result = $this->compareArray($expected, $actual);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function compare_array_finds_differences_in_simple_values()
    {
        $expected = [
            'a' => 5,
        ];
        $actual = [
            'a' => 6,
        ];

        $result = $this->compareArray($expected, $actual);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function compare_array_works_with_nested_arrays()
    {
        $expected = [
            'a' => [
                'b' => 5,
            ]
        ];
        $actual = [
            'a' => [
                'b' => 5,
            ]
        ];

        $result = $this->compareArray($expected, $actual);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function compare_array_finds_differences_with_nested_arrays()
    {
        $expected = [
            'a' => [
                'b' => 5,
            ]
        ];
        $actual = [
            'a' => [
                'b' => 6,
            ]
        ];

        $result = $this->compareArray($expected, $actual);

        $this->assertFalse($result);
    }
}