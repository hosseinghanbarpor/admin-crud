<?php

namespace Okami101\LaravelAdmin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Okami101\LaravelAdmin\AdminServiceProvider;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick start install';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        /**
         * Composer dependencies
         */
        $dependencies = ['spatie/laravel-query-builder'];
        $devDependencies = ['laracasts/generators'];


        if ($installLaravelIdeHelper = $this->confirm('Install barryvdh/laravel-ide-helper to provide full autocompletion ?', true)) {
            $devDependencies[] = 'barryvdh/laravel-ide-helper';
        }
        if ($installPhpCsFixer = $this->confirm('Install friendsofphp/php-cs-fixer to provide code styling ?', true)) {
            $devDependencies[] = 'friendsofphp/php-cs-fixer';
        }

        $this->installDependencies($dependencies);
        $this->installDependencies($devDependencies, true);

        $this->line('Installing dependencies');
        $this->executeCommand(['composer', 'update']);


        /**
         * Specific per-package preconfiguration
         */

        if ($installLaravelIdeHelper) {
            $this->configureLaravelIdeHelper();
        }

        if ($installPhpCsFixer) {
            $this->configurePhpCsFixer();
        }

        /**
         * Auto included package publish
         */
        $this->call('vendor:publish', [
            '--provider' => AdminServiceProvider::class,
            '--tag' => 'config',
            '--force' => true,
        ]);
    }

    private function configureLaravelIdeHelper()
    {
        $this->line('Configure Laravel IDE Helper');

        $this->warn(
            'Add this code inside composer.json for automatic generation :' . <<<EOF
"scripts":{
    "post-update-cmd": [
        "Illuminate\\Foundation\\ComposerScripts::postUpdate",
        "@php artisan ide-helper:generate",
        "@php artisan ide-helper:meta"
    ]
},
EOF
        );

        /**
         * Do not include generated code
         */
        $this->addToGitIgnore('.phpstorm.meta.php');
        $this->addToGitIgnore('_ide_helper.php');
    }

    private function configurePhpCsFixer()
    {
        $this->line('Configure PHP CS Fixer');

        $this->call('vendor:publish', [
            '--provider' => AdminServiceProvider::class,
            '--tag' => 'phpcs',
        ]);

        /**
         * Do not include phpcs cache
         */
        $this->addToGitIgnore('.php_cs.cache');
    }

    private function installDependencies(array $dependencies, bool $dev = false)
    {
        $this->line($dev ? 'Add dev dependencies' : 'Add dependencies');

        $command = array_merge(['composer', 'require'], $dependencies, ['--no-update']);

        if ($dev) {
            $command[] = '--dev';
        }

        $process = new Process($command, null, null, null, null);
        $process->run();
    }

    private function executeCommand($command)
    {
        $process = new Process($command, null, null, null, null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->comment($buffer);
            } else {
                $this->line($buffer);
            }
        });
    }

    private function addToGitIgnore($line)
    {
        if (! Str::contains($this->files->get(base_path('.gitignore')), $line)) {
            $this->files->append(base_path('.gitignore'), "$line\n");
        }
    }
}
