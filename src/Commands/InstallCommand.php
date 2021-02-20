<?php

namespace Samavin\LaravelAdmin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Samavin\LaravelAdmin\AdminServiceProvider;
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


        $this->installDependencies($dependencies);
        $this->installDependencies($devDependencies, true);

        $this->line('Installing dependencies');
        $this->executeCommand(['composer', 'update']);


        /**
         * Specific per-package preconfiguration
         */


        /**
         * Auto included package publish
         */
        $this->call('vendor:publish', [
            '--provider' => AdminServiceProvider::class,
            '--tag' => 'config',
            '--force' => true,
        ]);
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
