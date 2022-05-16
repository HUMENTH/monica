<?php

namespace Tests\Unit\Services\Account\Subscription;

use App\Exceptions\NoLicenceKeyEncryptionSetException;
use Exception;
use Tests\TestCase;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Subscription\ActivateLicenceKey;
use App\Services\Account\Subscription\CustomerPortalCall;
use Mockery\MockInterface;

class ActivateLicenceKeyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_activates_a_licence_key()
    {
        config(['monica.licence_private_key' => 'base64:CiZYhXuxFaXsYWOTw8o6C82rqiZkphLg+N6fVep2l0M=']);

        $key = 'eyJpdiI6IjJ5clV4N3hlaThIMGtsckgiLCJ2YWx1ZSI6IkdIUWd4aFM4OWlHL2V3SHF0M1VOazVYTjBaN2c4RGpRUDZtN0VNejhGL0YzZGF6bmFBNnBzK3lUT0VVVXFIaE80SlZ3RmRRK3J4UTRBQU5XU2lUS3JhRHQ0d1paYUIrNGM0VUg2ZzBNU3Y4MjlzQ0d4N2pTZGlPY3E5UWFMRGJCSXdZSnN6a1MwYVg5RFBaQ01jMGtpMzhubnVFbmV5TXB3Zz09IiwibWFjIjoiIiwidGFnIjoiWVZlUjgrQU8yUlBCd1BaTDUxb1JJZz09In0=';
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => $key,
        ];

        $this->mock(CustomerPortalCall::class, function (MockInterface $mock) use ($key) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(['licence_key' => $key])
                ->andReturn(200);
        });

        app(ActivateLicenceKey::class)->handle($request);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'licence_key' => $key,
            'valid_until_at' => '2022-04-03',
            'purchaser_email' => 'admin@admin.com',
            'frequency' => 'monthly',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(ActivateLicenceKey::class)->handle($request);
    }

    /** @test */
    public function it_fails_if_the_licence_key_does_not_exist()
    {
        $this->expectException(ValidationException::class);

        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => '',
        ];

        app(ActivateLicenceKey::class)->handle($request);
    }

    /** @test */
    public function it_fails_if_the_licence_key_is_not_valid_anymore()
    {
        config(['monica.licence_private_key' => 'base64:4IKtoOuRUWQLk2mdQ3MmM7VEr6dvP0IJWoijGk6NpRA=']);

        $this->expectException(Exception::class);

        $key = 'eyJpdiI6IjJ5clV4N3hlaThIMGtsckgiLCJ2YWx1ZSI6IkdIUWd4aFM4OWlHL2V3SHF0M1VOazVYTjBaN2c4RGpRUDZtN0VNejhGL0YzZGF6bmFBNnBzK3lUT0VVVXFIaE80SlZ3RmRRK3J4UTRBQU5XU2lUS3JhRHQ0d1paYUIrNGM0VUg2ZzBNU3Y4MjlzQ0d4N2pTZGlPY3E5UWFMRGJCSXdZSnN6a1MwYVg5RFBaQ01jMGtpMzhubnVFbmV5TXB3Zz09IiwibWFjIjoiIiwidGFnIjoiWVZlUjgrQU8yUlBCd1BaTDUxb1JJZz09In0=';

        $this->mock(CustomerPortalCall::class, function (MockInterface $mock) use ($key) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(['licence_key' => $key])
                ->andReturn(410);
        });

        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => $key,
        ];

        app(ActivateLicenceKey::class)->handle($request);
    }
}
