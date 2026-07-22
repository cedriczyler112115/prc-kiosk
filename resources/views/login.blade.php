<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - PRC Queue Kiosk</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            overflow-x: hidden;
        }
        .login-image {
            /* Using a professional office/meeting background */
            background-image: url('{{ asset('img/login-bg.svg') }}');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }
        .login-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-form-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Left Side: Image -->


        <!-- Right Side: Form -->
        <div class="col-lg-12 login-section bg-white">
            <div class="login-form-container">
                <div class="mb-4 text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2">
                        <i class="bi bi-people-fill text-primary fs-1 me-2"></i>
                        <span class="h2 fw-bold mb-0 text-dark">PRC Queue Kiosk</span>
                    </div>
                    <h5 class="text-secondary fw-normal">Queue Management System</h5>
                </div>

                <h3 class="fw-bold mb-2 text-center text-lg-start">Welcome back!</h3>
                <p class="text-secondary mb-4 text-center text-lg-start">Please enter your details to sign in.</p>

                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 list-unstyled">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" autocomplete="off">
                    @csrf
                    
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required autofocus value="{{ old('email') }}">
                        <label for="email">Email</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                            <label class="form-check-label text-secondary" for="rememberMe">
                                Keep me logged in
                            </label>
                        </div>
                        
                    </div>

                    <button class="btn btn-primary btn-lg w-100 py-2 mb-4" type="submit">Log in now</button>
                    
                    <div class="text-center text-secondary">
                        Don't have an account? <a href="{{ route('register') }}" class="text-decoration-none text-primary">Create new account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
