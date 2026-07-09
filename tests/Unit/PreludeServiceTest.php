<?php

namespace Tests\Unit;

use App\Services\PreludeService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PreludeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set a dummy API key for testing
        config(['app.env' => 'testing']);
        putenv('PRELUDE_API_KEY=test_key');
    }

    public function test_send_sms_verification_success()
    {
        Http::fake([
            'api.prelude.dev/v2/verification' => Http::response(['id' => 'ver_12345'], 200),
        ]);

        $service = new PreludeService();
        $id = $service->sendSmsVerification('+1234567890');

        $this->assertEquals('ver_12345', $id);
    }

    public function test_send_sms_verification_failure()
    {
        Http::fake([
            'api.prelude.dev/v2/verification' => Http::response(['error' => 'invalid'], 400),
        ]);

        Log::shouldReceive('error')->once();

        $service = new PreludeService();
        $id = $service->sendSmsVerification('+1234567890');

        $this->assertNull($id);
    }

    public function test_validate_sms_verification_success()
    {
        Http::fake([
            'api.prelude.dev/v2/verification/check' => Http::response(['status' => 'success'], 200),
        ]);

        $service = new PreludeService();
        $isValid = $service->validateSmsVerification('+1234567890', '12345');

        $this->assertTrue($isValid);
    }

    public function test_validate_sms_verification_failure()
    {
        Http::fake([
            'api.prelude.dev/v2/verification/check' => Http::response(['status' => 'expired'], 200),
        ]);

        $service = new PreludeService();
        $isValid = $service->validateSmsVerification('+1234567890', '00000');

        $this->assertFalse($isValid);
    }
}
