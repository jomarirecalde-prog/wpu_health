<header class="admin-header">
    <div class="container header-content">
        <div class="logo">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="WPU Logo" onerror="this.style.display='none'">
            <div class="logo-text">
                <h1>WPU Health Services</h1>
                <p>Consultation &amp; Appointment Portal</p>
            </div>
        </div>
        <div class="admin-info">
            @auth('portal')
                <span style="font-size:12px;margin-right:12px">{{ auth('portal')->user()->name }}</span>
                <form action="{{ route('portal.logout') }}" method="post" style="display:inline">@csrf
                    <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            @elseif(auth('physician'))
                <span style="font-size:12px;margin-right:12px">{{ auth('physician')->user()->displayName() }}</span>
                <form action="{{ route('physician.logout') }}" method="post" style="display:inline">@csrf
                    <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            @else
                <a href="{{ route('portal.login') }}" class="logout-btn" style="text-decoration:none"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="{{ route('portal.register') }}" class="logout-btn" style="text-decoration:none;margin-left:8px"><i class="fas fa-user-plus"></i> Register</a>
            @endauth
        </div>
    </div>
</header>
