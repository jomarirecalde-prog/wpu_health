<header class="portal-public-header" role="banner">
    <div class="container portal-public-header__inner">
        <a href="{{ route('portal.home') }}" class="portal-public-header__brand">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="" class="portal-public-header__logo" width="44" height="44" onerror="this.style.display='none'">
            <span class="portal-public-header__titles">
                <span class="portal-public-header__name">{{ $healthHubName ?? config('healthhub.name') }}</span>
                <span class="portal-public-header__tagline">Consultation &amp; Appointment Portal</span>
            </span>
        </a>
        <nav class="portal-public-header__actions" aria-label="Account">
            @auth('portal')
                <span class="portal-public-header__user">{{ auth('portal')->user()->name }}</span>
                <form action="{{ route('logout') }}" method="post" class="portal-public-header__form">@csrf
                    <button type="submit" class="portal-public-header__btn portal-public-header__btn--ghost"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            @elseif(auth('physician')->check())
                <span class="portal-public-header__user">{{ auth('physician')->user()->displayName() }}</span>
                <form action="{{ route('logout') }}" method="post" class="portal-public-header__form">@csrf
                    <button type="submit" class="portal-public-header__btn portal-public-header__btn--ghost"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            @elseif(auth('admin')->check())
                <span class="portal-public-header__user">{{ auth('admin')->user()->username }}</span>
                <form action="{{ route('logout') }}" method="post" class="portal-public-header__form">@csrf
                    <button type="submit" class="portal-public-header__btn portal-public-header__btn--ghost"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="portal-public-header__btn portal-public-header__btn--ghost"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="{{ route('register') }}" class="portal-public-header__btn portal-public-header__btn--primary"><i class="fas fa-user-plus"></i> Register</a>
            @endauth
        </nav>
    </div>
</header>
