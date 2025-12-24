<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

/**
 * Unit tests voor form validatie logica
 * Test specifiek de recursieve sanitization en validatie logica
 */
class FormValidationUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sanitize_recursive_handles_flat_arrays()
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array { return []; }
        };

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeRecursive');
        $method->setAccessible(true);

        $input = [
            'name' => "  John\0Doe  ",
            'email' => '  test@example.com  ',
        ];

        $result = $method->invoke($request, $input);

        $this->assertEquals('JohnDoe', $result['name']); // Trimmed and null bytes removed
        $this->assertEquals('test@example.com', $result['email']); // Trimmed
    }

    /** @test */
    public function sanitize_recursive_handles_nested_arrays()
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array { return []; }
        };

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeRecursive');
        $method->setAccessible(true);

        $input = [
            'user' => [
                'name' => "  John\0Doe  ",
                'address' => [
                    'street' => "  Main\0Street  ",
                ],
            ],
        ];

        $result = $method->invoke($request, $input);

        $this->assertEquals('JohnDoe', $result['user']['name']);
        $this->assertEquals('MainStreet', $result['user']['address']['street']);
    }

    /** @test */
    public function sanitize_recursive_handles_non_string_values()
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array { return []; }
        };

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeRecursive');
        $method->setAccessible(true);

        $input = [
            'name' => 'John',
            'age' => 30,
            'active' => true,
            'items' => [1, 2, 3],
        ];

        $result = $method->invoke($request, $input);

        $this->assertEquals('John', $result['name']);
        $this->assertEquals(30, $result['age']);
        $this->assertTrue($result['active']);
        $this->assertEquals([1, 2, 3], $result['items']);
    }

    /** @test */
    public function sanitize_recursive_removes_control_characters()
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array { return []; }
        };

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeRecursive');
        $method->setAccessible(true);

        // Control characters (but keep newlines and tabs)
        $input = "Test\x00\x01\x02\x03String\n\t";
        $result = $method->invoke($request, $input);

        $this->assertStringNotContainsString("\x00", $result);
        $this->assertStringNotContainsString("\x01", $result);
        $this->assertStringContainsString("\n", $result); // Newline preserved
        $this->assertStringContainsString("\t", $result); // Tab preserved
    }
}





