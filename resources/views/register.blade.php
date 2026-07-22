<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account - PRC Queue Kiosk</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            overflow-x: hidden;
        }
        .register-image {
            background-image: url('{{ asset('img/register-bg.svg') }}');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }
        .register-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-form-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Left Side: Image -->


        <!-- Right Side: Form -->
        <div class="col-lg-12 register-section bg-white">
            <div class="register-form-container">
                <div class="mb-4 text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2">
                        <i class="bi bi-people-fill text-primary fs-1 me-2"></i>
                        <span class="h2 fw-bold mb-0 text-dark">PRC Queue Kiosk</span>
                    </div>
                    <h5 class="text-secondary fw-normal">Create New Account</h5>
                </div>

                <p class="text-secondary mb-4 text-center text-lg-start">Fill in your details to get started.</p>

                <form action="{{ route('register.post') }}" method="POST" autocomplete="off" class="needs-validation" novalidate>
                    @csrf
                    
                    <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Lastname, Firstname Middlename" value="{{ old('name') }}" required pattern="^[a-zA-Z\s\-\.]+,(\s[a-zA-Z\s\-\.]+)+$">
                        <label for="name">Full Name (Last, First Middle)</label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">Format: Lastname, Firstname Middlename</div>
                        @enderror
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="name@example.com" value="{{ old('email') }}" required>
                        <label for="email">Email Address</label>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Password" required minlength="8" oninput="checkPasswordStrength(this.value)">
                        <label for="password">Password</label>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="progress mt-2" style="height: 4px;">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Confirm Password" required>
                        <label for="password_confirmation">Confirm Password</label>
                    </div>

                    <button class="btn btn-primary btn-lg w-100 py-2 mb-4" type="submit">Create Account</button>
                    
                    <div class="text-center text-secondary">
                        Already have an account? <a href="{{ route('login') }}" class="text-decoration-none text-primary">Sign in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 20;
        if (password.match(/[a-z]+/)) strength += 20;
        if (password.match(/[A-Z]+/)) strength += 20;
        if (password.match(/[0-9]+/)) strength += 20;
        if (password.match(/[\W]+/)) strength += 20;

        const bar = document.getElementById('password-strength-bar');
        
        bar.style.width = strength + '%';
        
        if (strength < 40) {
            bar.className = 'progress-bar bg-danger';
        } else if (strength < 80) {
            bar.className = 'progress-bar bg-warning';
        } else {
            bar.className = 'progress-bar bg-success';
        }
    }

    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

</body>
</html>
