<div wire:poll.5s="refreshBlast">
    <div class="mb-6">
        <a href="{{ route('blasts.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Blast Schedules
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

    <!-- Blast Info -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-gray-900 break-words">{{ $blast->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Scheduled for {{ $blast->scheduled_at->format('M d, Y \a\t H:i') }}
                </p>
            </div>
            <div class="flex-shrink-0">
                @switch($blast->status)
                    @case('pending')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Pending
                        </span>
                        @break
                    @case('processing')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            Processing
                        </span>
                        @break
                    @case('completed')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            Completed
                        </span>
                        @break
                    @case('failed')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                            Failed
                        </span>
                        @break
                @endswitch
            </div>
        </div>

        <div class="p-6">
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Progress</span>
                    <span>{{ $blast->getProgressPercentage() }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div
                        class="h-3 rounded-full transition-all duration-500 {{ $blast->failed_count > 0 ? 'bg-yellow-500' : 'bg-green-500' }}"
                        style="width: {{ $blast->getProgressPercentage() }}%"
                    ></div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $blast->total_recipients }}</div>
                    <div class="text-sm text-gray-500">Total Recipients</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $blast->sent_count }}</div>
                    <div class="text-sm text-gray-500">Sent</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $blast->failed_count }}</div>
                    <div class="text-sm text-gray-500">Failed</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $blast->total_recipients - $blast->sent_count - $blast->failed_count }}</div>
                    <div class="text-sm text-gray-500">Pending</div>
                </div>
            </div>

            <!-- Blast Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Device</h3>
                    <p class="text-gray-900">{{ $blast->whatsappDevice->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Scheduled At</h3>
                    <p class="text-gray-900">{{ $blast->scheduled_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            <!-- Message -->
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Message</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $blast->message }}</p>
                </div>
            </div>

            <!-- Media -->
            @if($blast->hasMedia())
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Attachment</h3>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <div class="ml-3">
                            <a href="{{ $blast->getMediaUrl() }}" target="_blank" class="text-sm font-medium text-green-600 hover:text-green-500">
                                View Attachment
                            </a>
                            <p class="text-sm text-gray-500">{{ $blast->media_type }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Retry Button -->
            @if($blast->failed_count > 0 && in_array($blast->status, ['completed', 'failed']))
                <div class="mt-6">
                    <button
                        wire:click="retryFailed"
                        wire:confirm="Are you sure you want to retry {{ $blast->failed_count }} failed messages?"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Retry Failed ({{ $blast->failed_count }})
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Recipients Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
            <h2 class="text-lg font-medium text-gray-900">Recipients</h2>
            <select
                wire:model.live="recipientFilter"
                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
            >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <!-- Mobile Card Layout -->
        <div class="block md:hidden divide-y divide-gray-200">
            @forelse($recipients as $recipient)
                <div class="p-4 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">{{ $recipient->phone_number }}</span>
                        @switch($recipient->status)
                            @case('pending')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                @break
                            @case('sent')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sent</span>
                                @break
                            @case('failed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                @break
                        @endswitch
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $recipient->sent_at?->format('M d, Y H:i:s') ?? '-' }}
                    </div>
                    @if($recipient->error_message)
                        <div class="text-sm text-red-600 break-words">
                            {{ $recipient->error_message }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-4 text-center text-sm text-gray-500">
                    No recipients found.
                </div>
            @endforelse
        </div>

        <!-- Desktop Table Layout -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recipients as $recipient)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $recipient->phone_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($recipient->status)
                                    @case('pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                        @break
                                    @case('sent')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Sent
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Failed
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $recipient->sent_at?->format('M d, Y H:i:s') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-red-600 max-w-xs truncate">
                                {{ $recipient->error_message ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No recipients found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
            {{ $recipients->links() }}
        </div>
    </div>
</div>
