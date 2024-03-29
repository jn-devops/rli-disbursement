<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Tests\TestCase;

class ApiTokenPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_permissions_can_be_updated(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['disburse:account', 'disburse:wallet'],
        ]);

        $response = $this->put('/user/api-tokens/'.$token->id, [
            'name' => $token->name,
            'permissions' => [
                'collect:account',
                'missing-permission',
            ],
        ]);

        $this->assertTrue($user->fresh()->tokens->first()->can('collect:account'));
        $this->assertFalse($user->fresh()->tokens->first()->can('disburse:account'));
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));
    }
}
