<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Time Tracker - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Time Tracker</h1>
            <p class="text-gray-600 mb-8">A Laravel-based time tracking application</p>
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
        </div>
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Demo Login</h2>
            <p class="text-gray-600 mb-4">Click the button below to log in as the demo user and start using the timer.</p>
            
            <a href="/login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 inline-block text-center">
                Login as Demo User
            </a>
            
            <div class="mt-6 text-sm text-gray-500">
                <p><strong>Demo Features:</strong></p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Start and stop timers</li>
                    <li>Track time for projects and issues</li>
                    <li>View recent time entries</li>
                    <li>Mobile-responsive design</li>
                </ul>
            </div>
        </div>
        
        <div class="text-center text-sm text-gray-500">
            <p>Built with Laravel, Alpine.js, and Tailwind CSS</p>
        </div>
    </div>
</body>
</html>
