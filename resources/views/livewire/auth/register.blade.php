<div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">
        {{ $isFirstUser ? 'Create your account' : 'Register new user' }}
    </h2>

    @if($isFirstUser)
        <p class="text-sm text-gray-600 text-center mb-6">
            You will be the owner of this system.
        </p>
    @endif

    <form wire:submit="register" class="space-y-6">
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Full name
            </label>
            <input
                wire:model="name"
                type="text"
                id="name"
                autocomplete="name"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('name') border-red-500 @enderror"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
                Email address
            </label>
            <input
                wire:model="email"
                type="email"
                id="email"
                autocomplete="email"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('email') border-red-500 @enderror"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
                Password
            </label>
            <input
                wire:model="password"
                type="password"
                id="password"
                autocomplete="new-password"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm password
            </label>
            <input
                wire:model="password_confirmation"
                type="password"
                id="password_confirmation"
                autocomplete="new-password"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
            >
        </div>

        <!-- Submit Button -->
        <div>
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="register">
                    {{ $isFirstUser ? 'Create account' : 'Register user' }}
                </span>
                <span wire:loading wire:target="register">Processing...</span>
            </button>
        </div>

        @if($isFirstUser)
            <div class="text-center">
                <a href="{{ route('login') }}" class="text-sm text-green-600 hover:text-green-500">
                    Already have an account? Sign in
                </a>
            </div>
        @endif
    </form>
</div>
