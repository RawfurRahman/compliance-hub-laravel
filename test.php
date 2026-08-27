<?php

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\PciDssRequirement;
use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$project = Project::find(7);
if (! $project) {
    echo "Project not found\n";
    exit;
}
$isPci = $project->module_type === 'pci_dss';
if ($isPci) {
    $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
    $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles', 'pciDssDetails.findings');
    $evidenceByRequirement = $project->evidenceFiles->groupBy('pci_dss_requirement_id');
    $findings = $project->pciDssDetails ? $project->pciDssDetails->findings->keyBy('pci_dss_requirement_id') : collect();

    $requirementsData = $requirements->map(function ($req) use ($findings) {
        $finding = $findings->get($req->id);
        $majorNum = explode('.', $req->req_num)[0];

        return [
            'id' => $req->id,
            'req_num' => $req->req_num,
            'description' => $req->req_description,
            'domain' => 'Requirement '.$majorNum,
            'name' => '',
            'is_applicable' => ($finding && $finding->is_applicable === false) ? 0 : 1,
        ];
    })->values();
} else {
    $framework = Framework::where('slug', $project->module_type)->first();
    $controls = $framework ? FrameworkControl::where('framework_id', $framework->id)->get()->sortBy('control_id', SORT_NATURAL) : collect();

    $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles');
    $evidenceByRequirement = $project->evidenceFiles->groupBy('framework_control_id');

    $requirementsData = $controls->map(function ($control) {
        $name = $control->control_name;

        return [
            'id' => $control->id,
            'req_num' => $control->control_id,
            'description' => $control->requirement_description,
            'domain' => $control->domain,
            'name' => $name,
            'is_applicable' => 1,
        ];
    })->values();
}

$domains = $requirementsData->pluck('domain')->unique()->values();

echo 'Requirements count: '.count($requirementsData)."\n";
echo 'Domains count: '.count($domains)."\n";
echo 'First req: '.json_encode($requirementsData->first())."\n";
echo 'First domain: '.json_encode($domains->first())."\n";
