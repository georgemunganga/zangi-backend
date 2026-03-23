@servers(['production' => 'zangi-production'])

@setup
    $deployPath = rtrim(env('DEPLOY_PATH', ''), '/');
    $deployBranch = env('DEPLOY_BRANCH', 'main');
    $deployRepository = trim((string) env('DEPLOY_REPOSITORY', ''));
    $phpBin = env('DEPLOY_PHP_BIN', 'php');
    $composerBin = env('DEPLOY_COMPOSER_BIN', 'composer');
    $npmBin = env('DEPLOY_NPM_BIN', 'npm');
    $publicPath = rtrim(env('DEPLOY_PUBLIC_PATH', ''), '/');
    $phpFpmService = trim(env('DEPLOY_PHP_FPM_SERVICE', ''));
    $queueRestart = strtolower((string) env('DEPLOY_QUEUE_RESTART', 'false')) === 'true';
    $buildFrontend = strtolower((string) env('DEPLOY_BUILD_FRONTEND', 'true')) !== 'false';
@endsetup

@story('setup', ['on' => 'production'])
    ensure_deploy_path
    clone_or_attach_repository
    ensure_env_file
    generate_app_key_if_missing
    link_public_directory
@endstory

@story('deploy', ['on' => 'production'])
    ensure_deploy_path
    clone_or_attach_repository
    ensure_env_file
    install_dependencies
    build_frontend_assets
    generate_app_key_if_missing
    run_migrations
    optimize_application
    link_public_directory
    restart_queue_if_enabled
    reload_php_fpm_if_configured
@endstory

@task('ensure_deploy_path')
    if [ -z "{{ $deployPath }}" ]; then
        echo "DEPLOY_PATH is not set."
        exit 1
    fi

    mkdir -p "{{ $deployPath }}"
@endtask

@task('clone_or_attach_repository')
    if [ ! -d "{{ $deployPath }}/.git" ]; then
        if [ -z "{{ $deployRepository }}" ]; then
            echo "DEPLOY_REPOSITORY is required for the first deploy when {{ $deployPath }} is not already a git repository."
            exit 1
        fi

        git clone --branch "{{ $deployBranch }}" "{{ $deployRepository }}" "{{ $deployPath }}"
    fi

    cd "{{ $deployPath }}"

    if [ -n "{{ $deployRepository }}" ]; then
        current_remote="$(git remote get-url origin 2>/dev/null || true)"

        if [ "$current_remote" != "{{ $deployRepository }}" ]; then
            git remote remove origin >/dev/null 2>&1 || true
            git remote add origin "{{ $deployRepository }}"
        fi
    fi

    if ! git remote get-url origin >/dev/null 2>&1; then
        echo "No git origin is configured in {{ $deployPath }}."
        exit 1
    fi

    git fetch origin "{{ $deployBranch }}" --prune
    git checkout "{{ $deployBranch }}"
    git reset --hard "origin/{{ $deployBranch }}"
@endtask

@task('ensure_env_file')
    if [ ! -f "{{ $deployPath }}/.env" ]; then
        cp "{{ $deployPath }}/.env.example" "{{ $deployPath }}/.env"
        echo "Created .env from .env.example. Update production values on the server."
    fi
@endtask

@task('install_dependencies')
    cd "{{ $deployPath }}"
    {{ $composerBin }} install --no-dev --no-interaction --prefer-dist --optimize-autoloader
@endtask

@task('build_frontend_assets')
    @if ($buildFrontend)
    cd "{{ $deployPath }}"

    if [ ! -f package.json ]; then
        echo "No package.json found. Frontend build skipped."
        exit 0
    fi

    if ! command -v "{{ $npmBin }}" >/dev/null 2>&1; then
        echo "{{ $npmBin }} is not available on the server."
        exit 1
    fi

    if [ -f package-lock.json ]; then
        {{ $npmBin }} ci --include=dev
    else
        {{ $npmBin }} install
    fi

    rm -rf public/build
    {{ $npmBin }} run build
    @else
    echo "Frontend build skipped."
    @endif
@endtask

@task('generate_app_key_if_missing')
    cd "{{ $deployPath }}"

    if ! grep -q '^APP_KEY=base64:' .env; then
        {{ $phpBin }} artisan key:generate --force
    fi
@endtask

@task('run_migrations')
    cd "{{ $deployPath }}"
    {{ $phpBin }} artisan migrate --force
@endtask

@task('optimize_application')
    cd "{{ $deployPath }}"
    {{ $phpBin }} artisan optimize:clear
    {{ $phpBin }} artisan storage:link || true
    {{ $phpBin }} artisan config:cache
    {{ $phpBin }} artisan route:cache
    {{ $phpBin }} artisan view:cache
@endtask

@task('link_public_directory')
    if [ -n "{{ $publicPath }}" ]; then
        if [ -L "{{ $publicPath }}" ] || [ ! -e "{{ $publicPath }}" ]; then
            ln -sfn "{{ $deployPath }}/public" "{{ $publicPath }}"
        else
            echo "Skipped public symlink. {{ $publicPath }} exists and is not a symlink."
        fi
    fi
@endtask

@task('restart_queue_if_enabled')
    @if ($queueRestart)
    cd "{{ $deployPath }}"
    {{ $phpBin }} artisan queue:restart || true
    @else
    echo "Queue restart skipped."
    @endif
@endtask

@task('reload_php_fpm_if_configured')
    if [ -n "{{ $phpFpmService }}" ] && command -v systemctl >/dev/null 2>&1; then
        systemctl reload "{{ $phpFpmService }}" || true
    else
        echo "PHP-FPM reload skipped."
    fi
@endtask
