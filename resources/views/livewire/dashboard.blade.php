<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Connected Devices -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Connected Devices</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $connectedDevices }} / {{ $totalDevices }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <a href="{{ route('whatsapp.index') }}" class="text-sm font-medium text-green-600 hover:text-green-500">
                    Manage devices &rarr;
                </a>
            </div>
        </div>

        <!-- Pending Blasts -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending Blasts</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $pendingBlasts }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <a href="{{ route('blasts.index') }}" class="text-sm font-medium text-green-600 hover:text-green-500">
                    View schedules &rarr;
                </a>
            </div>
        </div>

        <!-- Processing Blasts -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Processing</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $processingBlasts }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <span class="text-sm text-gray-500">Currently sending messages</span>
            </div>
        </div>

        <!-- Sent Today -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Sent Today</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ number_format($sentToday) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <span class="text-sm text-gray-500">Messages delivered</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('blasts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Blast Schedule
            </a>
            <a href="{{ route('whatsapp.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add WhatsApp Device
            </a>
        </div>
    </div>

    <!-- Recent Blasts -->
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Blast Schedules</h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            @if($recentBlasts->isEmpty())
                <div class="px-4 py-8 text-center text-gray-500">
                    <p>No blast schedules yet.</p>
                    <a href="{{ route('blasts.create') }}" class="text-green-600 hover:text-green-500 mt-2 inline-block">
                        Create your first blast &rarr;
                    </a>
                </div>
            @else
                <!-- Mobile Card Layout -->
                <div class="block md:hidden divide-y divide-gray-200">
                    @foreach($recentBlasts as $blast)
                        <a href="{{ route('blasts.show', $blast) }}" class="block p-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-sm font-medium text-gray-900 break-words min-w-0 flex-1 mr-2">{{ $blast->name }}</span>
                                @switch($blast->status)
                                    @case('pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 flex-shrink-0">Pending</span>
                                        @break
                                    @case('processing')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 flex-shrink-0">Processing</span>
                                        @break
                                    @case('completed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 flex-shrink-0">Completed</span>
                                        @break
                                    @case('failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 flex-shrink-0">Failed</span>
                                        @break
                                @endswitch
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                <span>{{ $blast->whatsappDevice->name ?? 'N/A' }}</span>
                                <span>{{ $blast->scheduled_at->format('M d, Y H:i') }}</span>
                                <span>
                                    {{ $blast->sent_count }}/{{ $blast->total_recipients }}
                                    @if($blast->failed_count > 0)
                                        <span class="text-red-500">({{ $blast->failed_count }} failed)</span>
                                    @endif
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Desktop Table Layout -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentBlasts as $blast)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('blasts.show', $blast) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $blast->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $blast->whatsappDevice->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $blast->scheduled_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $blast->sent_count }}/{{ $blast->total_recipients }}
                                        @if($blast->failed_count > 0)
                                            <span class="text-red-500">({{ $blast->failed_count }} failed)</span>
                                        @endif
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
