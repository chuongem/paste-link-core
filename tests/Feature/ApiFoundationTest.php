<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\ExampleApiRequest;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    public function test_health_endpoint_uses_standard_success_response(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'data' => [
                    'status' => 'ok',
                    'service' => 'paste-link-core',
                ],
            ]);
    }

    public function test_api_errors_use_standard_error_response(): void
    {
        $response = $this->getJson('/api/v1/missing-route');

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonPath('message', 'The route api/v1/missing-route could not be found.');
    }

    public function test_api_request_validation_uses_standard_error_response(): void
    {
        Route::post('/api/v1/test-validation-pattern', function (ExampleApiRequest $request) {
            return response()->json($request->validated());
        });

        $response = $this->postJson('/api/v1/test-validation-pattern', []);

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
                'code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonValidationErrors(['name']);
    }
}
