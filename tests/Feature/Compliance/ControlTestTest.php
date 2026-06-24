<?php

namespace Tests\Feature\Compliance;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\User;
use App\Modules\Compliance\Models\ControlTest;
use App\Modules\Compliance\Services\ControlTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlTestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Control $control;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $this->control = Control::create([
            'control_code' => 'CTRL-001',
            'name' => 'Access Control Policy',
            'is_active' => true,
        ]);
    }

    public function test_can_execute_pass_test(): void
    {
        $service = app(ControlTestService::class);
        $test = $service->execute(
            controlId: $this->control->id,
            testedBy: $this->user->id,
            testType: 'manual',
            result: 'pass',
            score: 95,
        );

        $this->assertDatabaseHas('comp_control_tests', ['id' => $test->id, 'result' => 'pass']);
        $this->assertEquals('pass', $test->result);
        $this->assertEquals(95, $test->score);
    }

    public function test_fail_test_creates_finding(): void
    {
        $service = app(ControlTestService::class);
        $test = $service->execute(
            controlId: $this->control->id,
            testedBy: $this->user->id,
            testType: 'automated',
            result: 'fail',
            notes: 'Firewall rule missing',
        );

        $this->assertNotNull($test->assessment_finding_id);
        $this->assertEquals('non_compliant', $test->assessmentFinding->compliance_state);
    }

    public function test_get_failed_tests(): void
    {
        $service = app(ControlTestService::class);
        $service->execute(controlId: $this->control->id, testedBy: $this->user->id, testType: 'manual', result: 'pass');
        $service->execute(controlId: $this->control->id, testedBy: $this->user->id, testType: 'manual', result: 'fail', notes: 'Failed');

        $failed = $service->getFailedTests();
        $this->assertCount(1, $failed);
        $this->assertEquals('fail', $failed->first()->result);
    }

    public function test_get_test_history(): void
    {
        $service = app(ControlTestService::class);
        $service->execute(controlId: $this->control->id, testedBy: $this->user->id, testType: 'manual', result: 'pass');
        $service->execute(controlId: $this->control->id, testedBy: $this->user->id, testType: 'manual', result: 'fail', notes: 'Failed');

        $history = $service->getHistory($this->control->id);
        $this->assertCount(2, $history);
    }

    public function test_partial_test_creates_finding(): void
    {
        $service = app(ControlTestService::class);
        $test = $service->execute(
            controlId: $this->control->id,
            testedBy: $this->user->id,
            testType: 'manual',
            result: 'partial',
            notes: 'Partially implemented',
        );

        $this->assertNotNull($test->assessment_finding_id);
        $this->assertEquals('partially_compliant', $test->assessmentFinding->compliance_state);
    }
}
