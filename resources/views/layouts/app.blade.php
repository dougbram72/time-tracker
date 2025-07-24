<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#10B981">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Time Tracker') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Mobile-first responsive adjustments */
        @media (max-width: 768px) {
            .timer-widget {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 50;
            }
            
            body {
                padding-bottom: 200px; /* Space for fixed timer widget */
            }
        }
        
        /* Smooth transitions */
        .transition-all {
            transition: all 0.2s ease-in-out;
        }
        
        /* Custom scrollbar for recent entries */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        
        /* Mobile Touch Optimizations */
        .touch-manipulation {
            touch-action: manipulation;
        }
        
        /* Prevent text selection on buttons */
        .select-none {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Smooth touch feedback */
        .touch-feedback {
            transition: transform 0.1s ease, filter 0.1s ease;
        }
        
        .touch-feedback:active {
            transform: scale(0.95);
            filter: brightness(1.1);
        }
        
        /* Improved focus states for accessibility */
        input:focus, select:focus, textarea:focus {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }
        
        /* Prevent zoom on input focus (iOS Safari) */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select,
        textarea {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        /* Better tap targets */
        button, a, input, select, textarea {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Smooth modal animations */
        .modal-enter {
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }
        
        .modal-enter-active {
            opacity: 1;
            transform: scale(1) translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        /* Swipe indicators */
        .swipe-indicator {
            position: relative;
        }
        
        .swipe-indicator::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: #E5E7EB;
            border-radius: 2px;
        }
        
        /* Loading states */
        .loading-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ config('app.name', 'Time Tracker') }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        @auth
                            <div class="text-sm text-gray-600">
                                Welcome, {{ Auth::user()->name }}
                            </div>
                            <form method="POST" action="/logout" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 transition-colors">
                                    Logout
                                </button>
                            </form>
                        @else
                            <a href="/login" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        <!-- Timer Widget - Fixed on mobile, inline on desktop -->
        <div class="timer-widget md:fixed md:bottom-4 md:right-4 md:max-w-sm">
            <x-timer-widget />
        </div>
    </div>

    <!-- Timer Store Script -->
    <script>
        {!! file_get_contents(resource_path('js/timer-store.js')) !!}
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
