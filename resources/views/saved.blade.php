<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Saved Competitions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">← Back to Dashboard</a>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if($competitions->isEmpty())
                    <div class="p-6 text-gray-500 dark:text-gray-400">No saved competitions yet.</div>
                @else
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Location</th>
                                <th class="px-6 py-3">Link</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competitions as $competition)
                                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-100/40 dark:hover:bg-gray-700/40" id="row-{{ $competition->id }}">
                                    <td class="px-6 py-4">{{ $competition->name }}</td>
                                    <td class="px-6 py-4">{{ $competition->date }}</td>
                                    <td class="px-6 py-4">{{ $competition->location }}</td>
                                    <td class="px-6 py-4">
                                        @if($competition->source_url)
                                            <a href="{{ $competition->source_url }}" target="_blank" class="text-blue-500 hover:underline">More Info</a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <button onclick="unsave({{ $competition->id }})" class="text-red-500 hover:underline text-xs">Unsave</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <script>
        function unsave(id) {
            fetch(`/competitions/${id}/save`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                document.getElementById(`row-${id}`).remove();
            });
        }
    </script>
</x-app-layout>
