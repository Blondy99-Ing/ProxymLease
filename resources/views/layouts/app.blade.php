{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Gestion des Swaps')</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar">
        <div class="navbar-container">
            <a href="{{ route('lease.index') }}" class="navbar-brand">Proxym Lease</a>

            <ul class="navbar-nav">
                <li>
                  <a href="{{ route('lease.index') }}"
                     class="nav-link {{ request()->routeIs('swaps.*') ? 'active' : '' }}">
                    Proxym Lease
                  </a>
                </li>
                <li>
                  <a href=""
                     class="nav-link {{ request()->routeIs('stats.*') ? 'active' : '' }}">
                    Statistiques
                  </a>
                </li>
            </ul>

            <div class="navbar-user">
                <button class="theme-toggle" id="themeToggle" type="button">🌙</button>

                @auth
                  @php
                    $u = auth()->user();
                    $initial = strtoupper(mb_substr(($u->prenom ?? $u->nom ?? 'A'), 0, 1, 'UTF-8'));
                  @endphp
                  <div class="user-info">
                      <div class="user-avatar">{{ $initial }}</div>
                      <span>{{ ($u->prenom ?? '') }} {{ ($u->nom ?? '') }}</span>
                  </div>

                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                      @csrf
                  </form>
                  <a href="{{ route('logout') }}"
                     onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                     class="nav-link">
                     Déconnexion
                  </a>
                @endauth

                @guest
                  <a href="{{ route('login') }}" class="nav-link">Se connecter</a>
                @endguest
            </div>
        </div>
    </nav>

    {{-- Flash messages (session + erreurs) --}}
    <div class="container">
        @if (session('status'))
          <div class="flash info">{{ session('status') }}</div>
        @endif

        @if (session('success'))
          <div class="flash success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
          <div class="flash error">{{ session('error') }}</div>
        @endif

        @if (session('warning'))
          <div class="flash warning">{{ session('warning') }}</div>
        @endif

        @if ($errors->any())
          <div class="flash error">
            <ul style="margin:0 0 0 18px;padding:0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
    </div>

    {{-- Contenu des pages --}}
    <main class="container">
        @yield('content')
    </main>

    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/showlease.js') }}"></script>
    <script src="{{ asset('assets/js/export.js') }}"></script>
</body>
</html>
