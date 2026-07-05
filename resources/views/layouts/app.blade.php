<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator Control Center</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
        }

        nav {
            background: #1a2535;
            padding: 0 32px;
            display: flex;
            align-items: center;
            gap: 32px;
            height: 56px;
        }

        nav .brand {
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
            transition: color .2s, border-color .2s;
        }

        nav a:hover, nav a.active {
            color: #fff;
            border-bottom-color: #3b82f6;
        }

        .page { padding: 32px; max-width: 1200px; margin: 0 auto; }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">⚙ Generator Control</div>
    <a href="{{ route('dashboard') }}"
       class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('tickets.index') }}"
       class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">Maintenance Tickets</a>
       <a href="{{ route('tickets.history') }}"
   class="{{ request()->routeIs('tickets.history') ? 'active' : '' }}">History</a>
   <a href="{{ route('classifier.index') }}"
   class="{{ request()->routeIs('classifier.index') ? 'active' : '' }}">Failure Classifier</a>

    {{-- NEW: user info + logout --}}
    <div style="margin-left:auto; display:flex; align-items:center; gap:16px;">
        <span style="color:#94a3b8; font-size:13px;">
            {{ auth()->user()->name }}
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    style="background:none; border:1px solid #334155; color:#94a3b8;
                           padding:5px 12px; border-radius:6px; font-size:12px;
                           cursor:pointer; transition:all .2s;"
                    onmouseover="this.style.borderColor='#3b82f6';this.style.color='#fff'"
                    onmouseout="this.style.borderColor='#334155';this.style.color='#94a3b8'">
                Logout
            </button>
        </form>
    </div>
</nav>

<div class="page">
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</body>
</html>