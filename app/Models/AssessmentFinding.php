<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'serial_no',
        'status',
        'observation_title',
        'risk_rating',
        'current_state',
        'gap_description',
        'impact_risk',
        'recommendation',
        'standard_reference',
        'is_compliant',
        'cloned_from_finding_id',
    ];

    protected $casts = [
        'is_compliant' => 'boolean',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function clonedFrom()
    {
        return $this->belongsTo(AssessmentFinding::class, 'cloned_from_finding_id');
    }

    protected static function booted()
    {
        static::saved(function ($finding) {
            // Only trigger automation if we are saving a Gap assessment finding
            // Load assessment relation if not loaded to check type
            $assessment = $finding->assessment;

            if ($assessment && $assessment->assessment_type === 'Gap') {
                if ($finding->is_compliant || ($finding->status === 'Closed' && $finding->risk_rating === 'None')) {
                    // Find or create the Final assessment for this project and framework
                    $finalAssessment = Assessment::firstOrCreate([
                        'project_id'      => $assessment->project_id,
                        'framework'       => $assessment->framework,
                        'assessment_type' => 'Final',
                    ], [
                        'start_date'      => $assessment->start_date ?? now(),
                        'end_date'        => $assessment->end_date ?? now(),
                    ]);

                    // Clone the finding to the Final assessment
                    // Use a temporary mute or flag if we need to prevent event loop, 
                    // but since the destination is 'Final', it won't trigger Gap automation anyway.
                    AssessmentFinding::updateOrCreate(
                        ['cloned_from_finding_id' => $finding->id],
                        [
                            'assessment_id'      => $finalAssessment->id,
                            'serial_no'          => $finding->serial_no,
                            'status'             => $finding->status,
                            'observation_title'  => $finding->observation_title,
                            'risk_rating'        => $finding->risk_rating,
                            'current_state'      => $finding->current_state,
                            'gap_description'    => $finding->gap_description,
                            'impact_risk'        => $finding->impact_risk,
                            'recommendation'     => $finding->recommendation,
                            'standard_reference' => $finding->standard_reference,
                            'is_compliant'       => $finding->is_compliant,
                        ]
                    );
                } else {
                    // Toggled back to non-compliant / open: remove the cloned record from Final assessment
                    AssessmentFinding::where('cloned_from_finding_id', $finding->id)->delete();
                }
            }
        });

        static::deleted(function ($finding) {
            $assessment = $finding->assessment;
            if ($assessment && $assessment->assessment_type === 'Gap') {
                // If parent Gap finding is deleted, delete the clone as well
                AssessmentFinding::where('cloned_from_finding_id', $finding->id)->delete();
            }
        });
    }
}
