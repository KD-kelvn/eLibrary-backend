{{--
  SSH: OpenSSH reads ~/.ssh/config by default — not backend/.ssh/config.
  Either merge .ssh/config into ~/.ssh/config, or run:
  export SSH_CONFIG=/absolute/path/to/backend/.ssh/config

  Use a Host alias + User + IdentityFile (public-key auth). Passwords cannot be stored in ssh config.
--}}

@setup
    $deployPath = '/var/www/elibrary/backend';
    $gitBranch = 'main';
@endsetup

@servers([
    'local' => '127.0.0.1',
    'production' => 'elibrary-prod',
])

@task('git-status', ['on' => ['local']])
git status
@endtask

@task('app-optimization', ['on' => ['local']])
php artisan optimize:clear
php artisan optimize
@endtask

@task('deploy', ['on' => ['production']])
cd {{ $deployPath }}
git pull origin {{ $gitBranch }}
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
@endtask
