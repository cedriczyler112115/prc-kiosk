# Licensing / Activation Guide

This project uses a signed license token to activate a specific device after login.

## 1) One-time setup (project creator)

### A. Generate an RSA keypair (keep the private key secret)

Generate a 2048-bit RSA private key and corresponding public key. Do this on the project creator’s machine.

**Windows (PowerShell)**

```powershell
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out license-private.pem
openssl rsa -in license-private.pem -pubout -out license-public.pem
```

**Linux/macOS (bash)**

```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out license-private.pem
openssl rsa -in license-private.pem -pubout -out license-public.pem
```

Store `license-private.pem` offline (USB, password manager vault, etc.). Do not commit it.

### B. Configure the app with the public key

Put the public key (contents of `license-public.pem`) into your `.env`:

```env
LICENSE_ENABLED=true
LICENSE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...your key...
-----END PUBLIC KEY-----"
```

## 2) Activating a device (per installation)

### A. Login, then open any protected page

After login, if the device is not activated, the app redirects to:

`/license/activate`

### B. Get the device MAC hash (on the target device)

Run:

```bash
php artisan license:device
```

It prints:

```
mac=aa:bb:cc:dd:ee:ff
mac_hash=<sha256 hash>
```

Copy the `mac_hash`.

### C. Generate the license token (project creator machine)

Use the private key to sign the `mac_hash`:

```bash
php artisan license:sign <mac_hash> --private-key=/absolute/path/to/license-private.pem --days=365
```

The command outputs a single token string. Copy it.

Notes:
- The private key path can also be provided via environment variable `LICENSE_PRIVATE_KEY_PATH`.
- The token expires after `--days` (default 365).

### D. Paste token and confirm your password (on the target device)

On `/license/activate`:
- **License Token**: paste the token you generated.
- **Confirm Your Password**: enter the password of the user that is currently logged in.

After successful activation, you are redirected to `/dashboard`.

## 3) What “Confirm Your Password” means

The activation page requires an extra authentication step.
It is simply the current logged-in user’s account password (the same password used to login).

## 4) Troubleshooting

- If the device MAC changes (new NIC, VPN adapter order changes, VM changes), you may be redirected back to activation. Generate a new token for the new device hash.
- Ensure OpenSSL is available for signing tokens (the server-side verification uses PHP OpenSSL too).
- For environments where MAC lookup is blocked, you can set:

```env
LICENSE_MAC_OVERRIDE=aa:bb:cc:dd:ee:ff
```

