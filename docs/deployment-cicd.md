# Backend CI/CD Deployment

## What This Setup Does

- deploys the Laravel backend on every push to `main`
- connects from GitHub Actions to the VPS over SSH
- updates the backend code on the server with `git fetch` + `git reset --hard`
- runs:
  - `composer install --no-dev`
  - `npm ci --include=dev`
  - `npm run build`
  - `php artisan migrate --force`
  - `php artisan optimize:clear`
  - `php artisan storage:link`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
- optionally restarts queues
- optionally reloads PHP-FPM

## Important Deployment Path Rule

Set `DEPLOY_PATH` to the full Laravel project directory on the server, not just the web root.

Recommended structure:

```text
/home/Zangi/web/app.zangisworld.com/backend
```

Then point the domain web root to:

```text
/home/Zangi/web/app.zangisworld.com/backend/public
```

If hPanel cannot change the document root, use `DEPLOY_PUBLIC_PATH` and replace `public_html` with a symlink to the Laravel `public` directory.

Example:

```text
DEPLOY_PATH=/home/Zangi/web/app.zangisworld.com/backend
DEPLOY_PUBLIC_PATH=/home/Zangi/web/app.zangisworld.com/public_html
```

`DEPLOY_PUBLIC_PATH` only works safely if `public_html` is already removed or is already a symlink.

For your server, if you are currently in:

```text
/home/Zangi/web/app.zangisworld.com/public_html
```

you have two valid deployment modes.

### Mode A: keep the current server layout

If the Laravel repo is already checked out inside `public_html`, use:

```text
DEPLOY_PATH=/home/Zangi/web/app.zangisworld.com/public_html
DEPLOY_PUBLIC_PATH=
DEPLOY_REPOSITORY=
```

This tells Envoy to reuse the existing git checkout and its current `origin`.

### Mode B: move later to the recommended layout

If you later want the cleaner Laravel structure, use:

```text
DEPLOY_PATH=/home/Zangi/web/app.zangisworld.com/backend
DEPLOY_PUBLIC_PATH=/home/Zangi/web/app.zangisworld.com/public_html
DEPLOY_REPOSITORY=git@github.com:OWNER/REPO.git
```

and make `public_html` point to:

```text
/home/Zangi/web/app.zangisworld.com/backend/public
```

## GitHub Setup

You do **not** need a PAT for this deployment flow.

Use SSH keys instead:

1. GitHub Actions SSH key
   - GitHub Actions uses this key to SSH into the VPS.
   - Add the private key as the `DEPLOY_SSH_KEY` GitHub secret.
   - Add the matching public key to the server user's `~/.ssh/authorized_keys`.

2. Server GitHub deploy key
   - The server uses this key to `git fetch` the backend repo from GitHub.
   - Generate it on the server and add the public key to the GitHub backend repo as a Deploy Key.
   - Read-only access is enough.

## GitHub Secrets To Add

Add these repository secrets in the **backend GitHub repo**:

- `DEPLOY_HOST`
  - example: `72.62.119.251`
- `DEPLOY_PORT`
  - example: `22`
- `DEPLOY_USER`
  - example: `root`
- `DEPLOY_SSH_KEY`
  - private key used by GitHub Actions to SSH into the server
- `DEPLOY_PATH`
  - full Laravel backend path on the VPS
- `DEPLOY_REPOSITORY`
  - optional if `DEPLOY_PATH` already contains a working git checkout with a valid `origin`
  - required for first-time clone or when you want Envoy to replace the remote

Optional secrets:

- `DEPLOY_PUBLIC_PATH`
  - only if you want Envoy to maintain a symlink from a public web path to Laravel `public`
- `DEPLOY_PHP_BIN`
  - example: `php`
- `DEPLOY_COMPOSER_BIN`
  - example: `composer`
- `DEPLOY_NPM_BIN`
  - example: `npm`
- `DEPLOY_PHP_FPM_SERVICE`
  - example: `php8.3-fpm`
- `DEPLOY_QUEUE_RESTART`
  - `true` or `false`
- `DEPLOY_BUILD_FRONTEND`
  - `true` or `false`

## Server Preparation

### 1. Clone location

If you are keeping the current server layout in `public_html`, you can skip this step.

If you are moving to the recommended layout, create the target path:

```bash
mkdir -p /home/Zangi/web/app.zangisworld.com/backend
```

The server must also have Node.js and npm installed because this repo builds Vite assets during deploy.

Minimum Node version for the current Vite setup:

```text
^20.19.0 || >=22.12.0
```

### 2. Give the server GitHub repo access

If `DEPLOY_PATH` already contains a working git checkout and Envoy is reusing the existing `origin`, this step is optional.

If you want Envoy to clone the repo for the first time, or you want the server to pull from GitHub over SSH, do this:

Generate a deploy key on the server:

```bash
ssh-keygen -t ed25519 -C "zangi-backend-server" -f ~/.ssh/id_ed25519_github
```

Add this to `~/.ssh/config` on the server:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_ed25519_github
  IdentitiesOnly yes
```

Print the public key:

```bash
cat ~/.ssh/id_ed25519_github.pub
```

Then add that public key in GitHub:

- backend repo
- `Settings -> Deploy keys -> Add deploy key`
- allow read-only access

### 3. Add the production `.env`

Create or edit the production app file in your active deploy path:

```bash
/home/Zangi/web/app.zangisworld.com/public_html/.env
```

Set real production values for:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://app.zangisworld.com`
- `FRONTEND_URL=https://zangisworld.com`
- database credentials
- SMTP credentials
- Lenco keys
- webhook secret

### 4. First manual deploy

If you keep the current layout, use:

```bash
cd /home/Zangi/web/app.zangisworld.com/public_html
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci --include=dev
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you move to the recommended layout later, use:

```bash
cd /home/Zangi/web/app.zangisworld.com/
git pull --branch main git@github.com:georgemunganga/zangi-backend.git .
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci --include=dev
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## GitHub Actions SSH Key Setup

Generate a key pair on your local machine for GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions-zangi-backend" -f zangi_backend_actions
```

Use:

- `zangi_backend_actions` as the value of the `DEPLOY_SSH_KEY` GitHub secret
- `zangi_backend_actions.pub` in:

```bash
/root/.ssh/authorized_keys
```

on the VPS

## How Deploy Works

The workflow runs:

```bash
php vendor/bin/envoy run deploy
```

Envoy then runs on the VPS:

```bash
if DEPLOY_REPOSITORY is set:
  update origin to DEPLOY_REPOSITORY
else:
  keep the existing origin remote
git fetch origin main --prune
git checkout main
git reset --hard origin/main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --include=dev
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Exact Server Setup For Your VPS

### Keep current layout

If the app is already checked out in:

```text
/home/Zangi/web/app.zangisworld.com/public_html
```

use these GitHub secrets:

```text
DEPLOY_HOST=admin
DEPLOY_PORT=22
DEPLOY_USER=root
DEPLOY_PATH=/home/Zangi/web/app.zangisworld.com/public_html
DEPLOY_PUBLIC_PATH=
DEPLOY_REPOSITORY=
DEPLOY_NPM_BIN=npm
DEPLOY_BUILD_FRONTEND=true
```

In this mode, Envoy will reuse the existing git remote in `public_html`.

### Move to recommended layout later

If you decide to restructure later, run this on the server as `root`:

```bash
mkdir -p /home/Zangi/web/app.zangisworld.com/backend
mv /home/Zangi/web/app.zangisworld.com/public_html /home/Zangi/web/app.zangisworld.com/public_html_backup_$(date +%F_%H-%M-%S)
ln -s /home/Zangi/web/app.zangisworld.com/backend/public /home/Zangi/web/app.zangisworld.com/public_html
```

Then use:

```text
DEPLOY_PATH=/home/Zangi/web/app.zangisworld.com/backend
DEPLOY_PUBLIC_PATH=/home/Zangi/web/app.zangisworld.com/public_html
DEPLOY_REPOSITORY=git@github.com:OWNER/REPO.git
```

## Recommended Next Checks

After configuring secrets:

1. run the workflow manually from GitHub Actions
2. verify `php artisan about` works on the VPS
3. verify the app URL serves the Laravel backend correctly
4. verify `/api/v1/auth/request-otp` responds publicly
5. only after that, test SMTP and Lenco webhook delivery
