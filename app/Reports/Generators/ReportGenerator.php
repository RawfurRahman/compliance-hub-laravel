<?php

namespace App\Reports\Generators;

use App\Models\Project;
use Illuminate\View\View;

abstract class ReportGenerator
{
    abstract public function generate(Project $project, string $type, array $options = []): View;
}
