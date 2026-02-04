<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VeA EconModels</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Tree connector lines */
        .tree-line {
            position: absolute;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            height: 2px;
        }
        
        .tree-vertical-line {
            position: absolute;
            background: linear-gradient(to bottom, #3b82f6, #60a5fa);
            width: 2px;
        }
        
        /* Node styles */
        .tree-node {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tree-node:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    @include('partials.navbar')
    
    <main>
        @yield('content')
    </main>
    
    @include('partials.footer')
</body>
</html>
