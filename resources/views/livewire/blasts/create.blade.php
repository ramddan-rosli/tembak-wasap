<div>
    <div class="mb-6">
        <a href="{{ route('blasts.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Blast Schedules
        </a>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Create New Blast Schedule</h1>
        </div>

        <form wire:submit="create" class="p-6 space-y-6">
            @if($devices->isEmpty())
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">No connected devices</h3>
                            <p class="mt-1 text-sm text-yellow-700">
                                You need to connect a WhatsApp device before creating a blast.
                                <a href="{{ route('whatsapp.index') }}" class="font-medium underline">Connect a device</a>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Blast Name</label>
                <input
                    wire:model="name"
                    type="text"
                    id="name"
                    placeholder="e.g., January Newsletter"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('name') border-red-500 @enderror"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- WhatsApp Device -->
            <div>
                <label for="whatsapp_device_id" class="block text-sm font-medium text-gray-700">WhatsApp Device</label>
                <select
                    wire:model="whatsapp_device_id"
                    id="whatsapp_device_id"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('whatsapp_device_id') border-red-500 @enderror"
                    {{ $devices->isEmpty() ? 'disabled' : '' }}
                >
                    <option value="">Select a device</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">
                            {{ $device->name }} ({{ $device->phone_number ?? 'No number' }})
                        </option>
                    @endforeach
                </select>
                @error('whatsapp_device_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Scheduled At -->
            <div>
                <label for="scheduled_at" class="block text-sm font-medium text-gray-700">Schedule Date & Time</label>
                <input
                    wire:model="scheduled_at"
                    type="datetime-local"
                    id="scheduled_at"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('scheduled_at') border-red-500 @enderror"
                >
                @error('scheduled_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Message Delay -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Message Delay (seconds)</label>
                <p class="mt-1 text-xs text-gray-500">Random delay between each message to avoid detection. Suggested: 5-15 seconds</p>
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <div>
                        <label for="delay_min" class="block text-xs font-medium text-gray-500">Minimum</label>
                        <input
                            wire:model="delay_min"
                            type="number"
                            id="delay_min"
                            min="1"
                            max="300"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('delay_min') border-red-500 @enderror"
                        >
                        @error('delay_min')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="delay_max" class="block text-xs font-medium text-gray-500">Maximum</label>
                        <input
                            wire:model="delay_max"
                            type="number"
                            id="delay_max"
                            min="1"
                            max="300"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('delay_max') border-red-500 @enderror"
                        >
                        @error('delay_max')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                <textarea
                    wire:model="message"
                    id="message"
                    rows="5"
                    placeholder="Enter your message here..."
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('message') border-red-500 @enderror"
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    {{ strlen($message) }}/4096 characters
                </p>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Media Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Attachment (Optional)</label>
                <div class="mt-1">
                    @if($media)
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $media->getClientOriginalName() }}</p>
                                <p class="text-sm text-gray-500">{{ number_format($media->getSize() / 1024, 2) }} KB</p>
                            </div>
                            <button
                                type="button"
                                wire:click="removeMedia"
                                class="text-red-600 hover:text-red-800"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="media" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                        <span>Upload a file</span>
                                        <input wire:model="media" id="media" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PNG, JPG, GIF, MP4, PDF, DOC up to 10MB
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
                @error('media')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Numbers -->
            <div>
                <label for="phone_numbers" class="block text-sm font-medium text-gray-700">Phone Numbers</label>
                <textarea
                    wire:model.live.debounce.300ms="phone_numbers"
                    id="phone_numbers"
                    rows="6"
                    placeholder="Enter phone numbers (one per line)&#10;0123456789&#10;60198765432&#10;..."
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm font-mono @error('phone_numbers') border-red-500 @enderror"
                ></textarea>
                <p class="mt-1 text-sm {{ $this->phoneCount > 0 ? 'text-green-600' : 'text-gray-500' }}">
                    <span class="font-medium">{{ $this->phoneCount }}</span> valid phone numbers detected.
                </p>
                <p class="mt-1 text-xs text-gray-400">
                    Numbers starting with 0 will automatically have country code 60 added (e.g., 0123456789 → 60123456789)
                </p>
                @error('phone_numbers')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a
                    href="{{ route('blasts.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                    wire:loading.attr="disabled"
                    {{ $devices->isEmpty() ? 'disabled' : '' }}
                >
                    <span wire:loading.remove wire:target="create">Schedule Blast</span>
                    <span wire:loading wire:target="create">Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>
