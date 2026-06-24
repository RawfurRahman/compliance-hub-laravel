<x-mail::message>
# AI Analysis Report

**File:** {{ $fileName }}

## Observations
{{ $observations }}

## Recommendations
{{ $recommendations }}

<x-mail::button :url="url('/evidence')">
Review in Evidence Hub
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
