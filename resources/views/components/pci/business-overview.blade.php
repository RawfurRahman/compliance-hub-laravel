@props(['details', 'paymentChannels'])

<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Business Overview</h2>

    {{-- Description of Entity's Business --}}
    <div class="mb-6">
        <label for="business_overview_desc" class="block text-sm font-medium text-gray-700">Description of Entity's Business</label>
        <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border">{{ optional($details)->business_overview_desc ?: 'Not provided.' }}</div>
        <div x-show="isEditing">
            <textarea name="business_overview_desc" id="business_overview_desc" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.business_overview_desc"></textarea>
        </div>
    </div>

    <hr class="my-6">

    {{-- Payment Channels --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">Payment Channels</label>
        <div class="mt-2 space-y-2">
            {{-- This is the corrected section --}}
            <div x-show="isEditing" x-init="if (!Array.isArray(details.payment_channels)) { details.payment_channels = [] }">
                @foreach ($paymentChannels as $channel)
                <div class="flex items-center">
                    <input id="channel_{{ Str::slug($channel) }}" name="payment_channels[]" type="checkbox" value="{{ $channel }}" class="h-4 w-4 text-blue-600 border-gray-300 rounded" x-model="details.payment_channels">
                    <label for="channel_{{ Str::slug($channel) }}" class="ml-3 block text-sm font-medium text-gray-700">{{ $channel }}</label>
                </div>
                @endforeach
            </div>
            
            <div x-show="!isEditing">
                @if(!empty(optional($details)->payment_channels))
                    <ul class="list-disc list-inside">
                        @foreach (optional($details)->payment_channels as $channel)
                            <li class="font-semibold text-gray-800">{{ $channel }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No payment channels selected.</p>
                @endif
            </div>
        </div>
    </div>
</div>
