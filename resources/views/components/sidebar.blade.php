<style>
  .sidebar-group-title { font-size: .75rem; letter-spacing: .08em; }
  .prc-sidebar {
    color: var(--prc-text-on-navy, #ffffff);
    background-color: #000000; /* lighter than navy blue, solid color */
  }
  .prc-sidebar .offcanvas-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  }
  .prc-sidebar .sidebar-group-title {
    color: rgba(214, 225, 240, 0.85) !important;
  }
  .offcanvas-body .nav-link { border-radius: .375rem; transition: background-color .15s ease-in-out, color .15s ease-in-out; }
  .offcanvas-body .nav-link:hover { background-color: rgba(255,255,255,.10); color: #fff; }
  .offcanvas-body .nav-link.active { background-color: rgba(255,255,255,.20); color: #fff; font-weight: 600; }
</style>

<div class="offcanvas offcanvas-start prc-sidebar" style="width: 280px" tabindex="-1" id="sideMenu" aria-labelledby="sideMenuLabel" data-bs-scroll="true">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sideMenuLabel">Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body">
    <nav class="d-flex flex-column gap-2">
      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </a>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('live-queue-board')) active @endif" href="{{ route('live-queue-board') }}?init=1">
        <i class="bi bi-display"></i>
        <span>Live Queue Board</span>
      </a>
      <div class="sidebar-group-title text-secondary text-uppercase fw-semibold mt-2">Entrance Queuing</div>
      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('queue.guard-entry')) active @endif" href="{{ route('queue.guard-entry') }}">
        <i class="bi bi-person-fill-add"></i>
        <span>Guard Queue Entry</span>
      </a>
      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('queue.guard-summary')) active @endif" href="{{ route('queue.guard-summary') }}">
        <i class="bi bi-card-checklist"></i>
        <span>Waiting List</span>
      </a>

      @if(!Auth::user()->isGuard())
      <div class="sidebar-group-title text-secondary text-uppercase fw-semibold mt-2">Queue Management</div>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('queue.my-counter')) active @endif" href="{{ route('queue.my-counter') }}">
        <i class="bi bi-person-workspace"></i>
        <span>My Counter</span>
      </a>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('queue.list')) active @endif" href="{{ route('queue.list') }}">
        <i class="bi bi-list-check"></i>
        <span>Queue List</span>
      </a>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('queue.logs')) active @endif" href="{{ route('queue.logs') }}">
        <i class="bi bi-clock-history"></i>
        <span>Queue Logs</span>
      </a>
      @endif

      @if(Auth::user()->isAdmin())
      <div class="sidebar-group-title text-secondary text-uppercase fw-semibold mt-2">Libraries</div>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('libraries.transaction-types')) active @endif" href="{{ route('libraries.transaction-types') }}">
        <i class="bi bi-receipt"></i>
        <span>Transaction types</span>
      </a>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('libraries.windows')) active @endif" href="{{ route('libraries.windows') }}">
        <i class="bi bi-window"></i>
        <span>User Assignments</span>
      </a>
      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('libraries.priorities')) active @endif" href="{{ route('libraries.priorities') }}">
        <i class="bi bi-person-wheelchair"></i>
        <span>Special Lane</span>
      </a>
      @endif

      <div class="sidebar-group-title text-secondary text-uppercase fw-semibold mt-2">Account</div>

      <a class="nav-link text-white d-flex align-items-center gap-2 @if(request()->routeIs('account.profile')) active @endif" href="{{ route('account.profile') }}">
        <i class="bi bi-person-circle"></i>
        <span>Profile</span>
      </a>

      <form action="{{ route('logout') }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="nav-link text-white d-flex align-items-center gap-2 w-100 text-start bg-transparent border-0">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </button>
      </form>
    </nav>
  </div>
</div>
