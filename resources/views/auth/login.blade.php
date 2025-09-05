<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Indeks Inklusi</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js CDN --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Tambahan font & config Tailwind --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#2563eb',
                        secondary: '#1e40af',
                    }
                }
            }
        }
    </script>

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 via-white to-blue-200 min-h-screen flex items-center justify-center font-sans">

    @if (session('registration_success'))
        <div
            x-data="{ showModal: true }"
            x-show="showModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            style="display: none;"
        >
            <div 
                @click.away="showModal = false"
                x-show="showModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-90"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-90"
                class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center"
            >
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">
                    {{ session('registration_success')['title'] }}
                </h3>
                <div class="mt-2 text-sm text-gray-500">
                    <p>{{ session('registration_success')['body'] }}</p>
                </div>
                <div class="mt-5">
                    <button 
                        @click="showModal = false"
                        type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary hover:bg-secondary text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                    >
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Selamat Datang</h1>
            <p class="text-sm text-gray-500">Silakan login untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.authenticate') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:outline-none text-sm">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:outline-none text-sm">
            </div>
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="remember" class="form-checkbox rounded border-gray-300 text-primary">
                    <span>Ingat saya</span>
                </label>
            </div>
            <button type="submit"
                class="w-full py-2 px-4 bg-primary hover:bg-secondary text-white font-semibold rounded-md transition duration-200">
                Login
            </button>
        </form>
        <div class="text-center mt-6 text-sm text-gray-600">
            Belum punya akun? <a href="{{ route('register.show') }}" class="text-primary hover:underline">Daftar di sini</a>
        </div>
    </div>

</body>
</html>