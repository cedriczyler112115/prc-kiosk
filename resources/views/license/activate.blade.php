@extends('layouts.app')

@section('title', 'Activation Required')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3">Activation Required</h2>
                    <p class="text-secondary mb-4">
                        This installation must be activated for this device before continuing.
                    </p>

                    <div class="mb-4">
                        <div class="small text-secondary">Device identity</div>
                        <div class="fw-semibold">{{ $macHash ?? 'Unavailable' }}</div>
                    </div>

                    <form method="POST" action="{{ route('license.activate.post') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label for="token" class="form-label">License Token</label>
                            <textarea
                                id="token"
                                name="token"
                                class="form-control @error('token') is-invalid @enderror"
                                rows="4"
                                required
                            >{{ old('token') }}</textarea>
                            @error('token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Confirm Your Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @error('license')
                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-primary w-100">
                            Activate
                        </button>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#registerDeviceModal" class="text-decoration-none">Register Device</a>
                    </form>
                </div>
            </div>
            <div class="text-secondary small mt-3">
                If you are the project creator, generate a signed token for this device identity and paste it above.<br>
                
            </div>
            
            <div class="mt-4 text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disableActivationModal">
                    Allow without activation
                </button>
            </div>
        </div>
    </div>

    <!-- Disable Activation Modal -->
    <div class="modal fade" id="disableActivationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('license.disable-activation') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Allow Without Activation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary mb-3">Are you sure you want to bypass the activation? This action will disable the license requirement for this system.</p>
                        <div class="mb-3">
                            <label class="form-label">Confirm Your Password</label>
                            <input type="password" name="password" class="form-control @error('disable_password') is-invalid @enderror" required>
                            @error('disable_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-3 pt-0 px-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Device Registration Modal -->
    <div class="modal fade" id="registerDeviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Password Check -->
                    <div id="step-password">
                        <div class="mb-3">
                            <label class="form-label">Developer Password</label>
                            <input type="password" id="staticPassword" class="form-control" autocomplete="off">
                            <div class="invalid-feedback" id="passwordError">Invalid password.</div>
                        </div>
                        <button type="button" class="btn btn-primary w-100" id="btnVerifyPassword">Continue</button>
                    </div>

                    <!-- Step 2: Generation Form -->
                    <div id="step-generate" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Device Identity</label>
                            <input type="text" id="generatorDeviceIdentity" class="form-control" value="{{ $macHash ?? '' }}" readonly>
                        </div>
                        <button type="button" class="btn btn-primary w-100 mb-3" id="btnGenerateToken">Generate Token</button>

                        <div id="generatedTokenContainer" class="d-none">
                            <label class="form-label text-success">Generated Token</label>
                            <textarea id="generatedToken" class="form-control text-success fw-bold" rows="5" readonly></textarea>
                            <div class="form-text">Copy this token, close the modal, and paste it in the Activate form.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnVerify = document.getElementById('btnVerifyPassword');
            const btnGenerate = document.getElementById('btnGenerateToken');
            const stepPassword = document.getElementById('step-password');
            const stepGenerate = document.getElementById('step-generate');
            const staticPassword = document.getElementById('staticPassword');
            const passwordError = document.getElementById('passwordError');
            const deviceIdentity = document.getElementById('generatorDeviceIdentity');
            const generatedTokenContainer = document.getElementById('generatedTokenContainer');
            const generatedToken = document.getElementById('generatedToken');

            let currentPassword = '';

            btnVerify.addEventListener('click', function () {
                const pwd = staticPassword.value.trim();
                if (pwd === '') {
                    staticPassword.classList.add('is-invalid');
                    passwordError.innerText = 'Password is required.';
                    return;
                }

                btnVerify.disabled = true;
                btnVerify.innerText = 'Verifying...';

                fetch('{{ route("license.verify-registration-password") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ password: pwd })
                })
                .then(r => r.json())
                .then(data => {
                    btnVerify.disabled = false;
                    btnVerify.innerText = 'Continue';
                    if (data.success) {
                        currentPassword = pwd;
                        staticPassword.classList.remove('is-invalid');
                        stepPassword.classList.add('d-none');
                        stepGenerate.classList.remove('d-none');
                    } else {
                        staticPassword.classList.add('is-invalid');
                        passwordError.innerText = data.message || 'Invalid password.';
                    }
                })
                .catch(err => {
                    btnVerify.disabled = false;
                    btnVerify.innerText = 'Continue';
                    alert('Error verifying password.');
                });
            });

            btnGenerate.addEventListener('click', function () {
                btnGenerate.disabled = true;
                btnGenerate.innerText = 'Generating...';
                
                fetch('{{ route("license.generate-token") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        password: currentPassword,
                        device_identity: deviceIdentity.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    btnGenerate.disabled = false;
                    btnGenerate.innerText = 'Generate Token';
                    
                    if (data.success) {
                        generatedToken.value = data.token;
                        generatedTokenContainer.classList.remove('d-none');
                        if (document.getElementById('token')) {
                            document.getElementById('token').value = data.token;
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    btnGenerate.disabled = false;
                    btnGenerate.innerText = 'Generate Token';
                    alert('An error occurred. Check browser console.');
                });
            });
        });
    </script>

    @if($errors->has('disable_password'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var disableModal = new bootstrap.Modal(document.getElementById('disableActivationModal'));
            disableModal.show();
        });
    </script>
    @endif
@endsection

