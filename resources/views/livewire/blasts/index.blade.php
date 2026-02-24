<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Blast Schedules</h1>
        <a
            href="{{ route('blasts.create') }}"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Blast
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    id="search"
                    placeholder="Search by name..."
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                >
            </div>
            <div>
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                    wire:model.live="statusFilter"
                    id="statusFilter"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Blasts Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        @if($blasts->isEmpty())
            <div class="px-4 py-8 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No blast schedules</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new blast schedule.</p>
                <div class="mt-6">
                    <a
                        href="{{ route('blasts.create') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Blast
                    </a>
                </div>
            </div>
        @else
            <!-- Mobile Card Layout -->
            <div class="block lg:hidden divide-y divide-gray-200">
                @foreach($blasts as $blast)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('blasts.show', $blast) }}" class="text-sm font-medium text-gray-900 hover:text-green-600 break-words">
                                    {{ $blast->name }}
                                </a>
                                @if($blast->hasMedia())
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        Media
                                    </span>
                                @endif
                            </div>
                            <div class="ml-2 flex-shrink-0">
                                @switch($blast->status)
                                    @case('pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                        @break
                                    @case('processing')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Processing</span>
                                        @break
                                    @case('completed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                                        @break
                                    @case('failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                            <span>{{ $blast->whatsappDevice->name ?? 'N/A' }}</span>
                            <span>{{ $blast->scheduled_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1 mr-4">
                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2 max-w-[120px]">
                                    <div
                                        class="h-2 rounded-full {{ $blast->failed_count > 0 ? 'bg-yellow-500' : 'bg-green-500' }}"
                                        style="width: {{ $blast->getProgressPercentage() }}%"
                                    ></div>
                                </div>
                                <span class="text-sm text-gray-500">{{ $blast->sent_count }}/{{ $blast->total_recipients }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm font-medium">
                                <a href="{{ route('blasts.show', $blast) }}" class="text-green-600 hover:text-green-900">View</a>
                                @if($blast->status !== 'completed')
                                    <a href="{{ route('blasts.edit', $blast) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                @endif
                                @if($blast->status === 'pending')
                                    <button
                                        wire:click="deleteBlast({{ $blast->id }})"
                                        wire:confirm="Are you sure you want to delete this blast schedule?"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table Layout -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($blasts as $blast)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <a href="{{ route('blasts.show', $blast) }}" class="text-sm font-medium text-gray-900 hover:text-green-600 break-words">
                                            {{ $blast->name }}
                                        </a>
                                        @if($blast->hasMedia())
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                                Media
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $blast->whatsappDevice->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $blast->scheduled_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2 mr-2 max-w-[100px]">
                                            <div
                                                class="h-2 rounded-full {{ $blast->failed_count > 0 ? 'bg-yellow-500' : 'bg-green-500' }}"
                                                style="width: {{ $blast->getProgressPercentage() }}%"
                                            ></div>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            {{ $blast->sent_count }}/{{ $blast->total_recipients }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($blast->status)
                                        @case('pending')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending
                                            </span>
                                            @break
                                        @case('processing')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                Processing
                                            </span>
                                            @break
                                        @case('completed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Failed
                                            </span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('blasts.show', $blast) }}" class="text-green-600 hover:text-green-900 mr-3">
                                        View
                                    </a>
                                    @if($blast->status !== 'completed')
                                        <a href="{{ route('blasts.edit', $blast) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                            Edit
                                        </a>
                                    @endif
                                    @if($blast->status === 'pending')
                                        <button
                                            wire:click="deleteBlast({{ $blast->id }})"
                                            wire:confirm="Are you sure you want to delete this blast schedule?"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $blasts->links() }}
            </div>
        @endif
    </div>
</div>
