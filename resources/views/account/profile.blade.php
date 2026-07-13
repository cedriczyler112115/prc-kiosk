@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="h3 mb-4 text-gray-800">My Profile</h1>

                <!-- Profile Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                    </div>
                    <div class="card-body">
                        @if (session('status') === 'profile-updated')
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Profile updated successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('account.profile.update') }}" class="needs-validation"
                            novalidate>
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name', $user->name) }}" required
                                    pattern="^[a-zA-Z\s\-\.]+,(\s[a-zA-Z\s\-\.]+)+$">
                                <div class="form-text">Format: <i class="text-danger text-italic">( Lastname, Firstname
                                        Middlename )</i></div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control-plaintext fw-bold fs-5" id="email"
                                    value="{{ $user->email }}" readonly>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="transaction_id" class="form-label">Assigned Transaction</label>
                                    @if($transactions->isEmpty())
                                        <select class="form-select" disabled>
                                            <option>No transactions available</option>
                                        </select>
                                        <input type="hidden" name="transaction_id" value="">
                                        <div class="form-text text-danger">No active transactions found. Please contact
                                            administrator.</div>
                                    @else
                                        <select class="form-select @error('transaction_id') is-invalid @enderror"
                                            id="transaction_id" name="transaction_id">
                                            <option value="">Select Transaction</option>
                                            @foreach($transactions as $transaction)
                                                <option value="{{ $transaction->id }}" {{ old('transaction_id', $user->transaction_id) == $transaction->id ? 'selected' : '' }}>
                                                    {{ $transaction->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @error('transaction_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="counter_id" class="form-label">Counter Number</label>
                                    <input type="text" class="form-control @error('counter_id') is-invalid @enderror"
                                        id="counter_id" name="counter_id" value="{{ old('counter_id', $user->counter_id) }}"
                                        min="1" max="99">
                                    @error('counter_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Update Password</h6>
                    </div>
                    <div class="card-body">
                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Password updated successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}" class="needs-validation" novalidate>
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password"
                                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                    id="current_password" name="current_password" required>
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password"
                                    class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                    id="password" name="password" required minlength="8"
                                    oninput="checkPasswordStrength(this.value)">
                                <div class="progress mt-2" style="height: 5px;">
                                    <div id="password-strength-bar" class="progress-bar" role="progressbar"
                                        style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small id="password-strength-text" class="form-text text-muted">Use at least 8 characters
                                    with letters, numbers & symbols.</small>
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Password Strength Indicator
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength += 20;
            if (password.match(/[a-z]+/)) strength += 20;
            if (password.match(/[A-Z]+/)) strength += 20;
            if (password.match(/[0-9]+/)) strength += 20;
            if (password.match(/[\W]+/)) strength += 20;

            const bar = document.getElementById('password-strength-bar');
            const text = document.getElementById('password-strength-text');

            bar.style.width = strength + '%';

            if (strength < 40) {
                bar.className = 'progress-bar bg-danger';
                text.innerText = 'Weak';
                text.className = 'form-text text-danger';
            } else if (strength < 80) {
                bar.className = 'progress-bar bg-warning';
                text.innerText = 'Medium';
                text.className = 'form-text text-warning';
            } else {
                bar.className = 'progress-bar bg-success';
                text.innerText = 'Strong';
                text.className = 'form-text text-success';
            }
        }

        // Bootstrap validation
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
@endsection