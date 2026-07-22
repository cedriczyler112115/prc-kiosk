<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Professional Regulation Comission - CARAGA Queue Kiosk')</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2-bootstrap-5-theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/jquery-confirm/jquery-confirm.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
  </head>
  <body>
    
    <nav class="navbar navbar-dark prc-navbar">
      <div class="container-fluid d-flex align-items-center justify-content-between responsive-container">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu" aria-controls="sideMenu">Menu</button>
          <a class="navbar-brand mb-0 h1" href="{{ route('dashboard') }}">Professional Regulation Comission - CARAGA Queue Kiosk</a>
        </div>
        <div class="d-flex align-items-center gap-3 ms-auto">
          <span class="text-white">Welcome, {{ Auth::user()->name }}</span>
          <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button class="btn btn-outline-light" type="submit">Logout</button>
          </form>
        </div>
      </div>
    </nav>

    <x-sidebar />

    <div class="@yield('container_class', 'container-fluid') mt-5 responsive-container">
      @yield('content')
    </div>

    <!-- Loader -->
    <style>
        :root {
            --prc-navy-900: #001a33;
            --prc-navy-850: #001f3f;
            --prc-navy-800: #003366;
            --prc-text-on-navy: #ffffff;
            --prc-muted-on-navy: #d6e1f0;

            --bs-primary: var(--prc-navy-850);
            --bs-primary-rgb: 0, 31, 63;
            --bs-link-color: var(--prc-navy-800);
            --bs-link-hover-color: var(--prc-navy-850);
        }

        .prc-navbar {
            background-color: var(--prc-navy-850);
            background-image: linear-gradient(90deg, var(--prc-navy-800) 0%, var(--prc-navy-850) 45%, var(--prc-navy-900) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .prc-navbar .navbar-brand,
        .prc-navbar .text-white {
            color: var(--prc-text-on-navy);
        }

        .prc-navbar .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.75);
        }

        /* Responsive Container */
        .responsive-container {
            width: 98%; /* Mobile width */
            margin: 0 auto;
        }
        @media (min-width: 768px) {
            .responsive-container {
                width: 90%; /* Desktop width */
            }
        }

        .loading-dots::after {
            content: '';
            animation: loading-dots-animation 1.5s infinite steps(4);
            display: inline-block;
            width: 1.5em; /* Ensure enough width for 3 dots */
            text-align: left;
        }
        @keyframes loading-dots-animation {
            0% { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }
    </style>
    <div id="loader" class="d-none position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: rgba(0, 0, 0, 0.6); z-index: 9999;">
        <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="text-white fw-bold fs-5">
            Loading<span class="loading-dots"></span>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-confirm/jquery-confirm.min.js') }}"></script>
    <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('js/jquery.paginated-table.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Global AJAX setup for CSRF token
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            // Toastr options
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
            };

            @if(session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            @if(session('error'))
                toastr.error("{{ session('error') }}");
            @endif

            @if($errors->has('error'))
                toastr.error("{{ $errors->first('error') }}");
            @endif

            @if($errors->any() && !$errors->has('error'))
                @foreach($errors->all() as $error)
                    toastr.error("{{ $error }}");
                @endforeach
            @endif

            // Global Loader Handler
            $(document).ajaxStart(function() {
                $('#loader').removeClass('d-none');
            }).ajaxStop(function() {
                $('#loader').addClass('d-none');
            });

            $(document).on('submit', 'form[data-confirm]', function (e) {
                const form = this;
                const $form = $(form);
                const message = String($form.attr('data-confirm') || '').trim();
                if (message === '') return;
                if ($form.data('confirmed') === true) return;

                e.preventDefault();

                if (typeof $.confirm !== 'function') {
                    return;
                }

                $.confirm({
                    title: 'Confirm',
                    content: message,
                    buttons: {
                        cancel: function () {},
                        confirm: {
                            text: 'Yes',
                            btnClass: 'btn-danger',
                            action: function () {
                                $form.data('confirmed', true);
                                form.submit();
                            }
                        }
                    }
                });
            });

            @auth
                (function () {
                    const url = "{{ route('auth.ping') }}";
                    const refreshEveryMs = 10 * 60 * 1000;

                    function ping() {
                        if (document.visibilityState !== 'visible') return;
                        fetch(url, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).catch(function () {});
                    }

                    ping();
                    setInterval(ping, refreshEveryMs);
                })();
            @endauth

            const offcanvasEl = document.getElementById('sideMenu');
            if (offcanvasEl) {
                offcanvasEl.addEventListener('click', function (e) {
                    const link = e.target.closest('a.nav-link');
                    if (!link) return;
                    const instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (instance) instance.hide();
                });
            }
        });
    </script>
    @stack('scripts')
  </body>
</html>
