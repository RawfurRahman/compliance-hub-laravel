
@props(['title', 'fields', 'type'])

<div x-data="{ items: [] }" class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ $title }}</h2>

    <template x-for="(item, index) in items" :key="index">
        <div class="border p-4 rounded-lg mb-4">
            <h3 class="font-medium text-gray-700 mb-2">{{ substr($title, 0, -1) }} <span x-text="index + 1"></span></h3>
            @foreach($fields as $field)
                <div class="mb-4">
                    <label for="{{ $type }}_<span x-text="index"></span>_{{ $field['name'] }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>
                    @if($field['type'] === 'text')
                        <input type="text" id="{{ $type }}_<span x-text="index"></span>_{{ $field['name'] }}" name="{{ $type }}[]['{{ $field['name'] }}']" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @elseif($field['type'] === 'textarea')
                        <textarea id="{{ $type }}_<span x-text="index"></span>_{{ $field['name'] }}" name="{{ $type }}[]['{{ $field['name'] }}']" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    @endif
                </div>
            @endforeach
            <button @click="items.splice(index, 1)" type="button" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
        </div>
    </template>

    <button @click="items.push({})" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        Add {{ substr($title, 0, -1) }}
    </button>
</div>
