<?php

namespace IndieSystems\Notifications\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'indie-notifications:install';
    protected $description = 'Install the notifications package: create table, publish config';

    public function handle()
    {
        $this->info('Installing Indie Notifications...');
        $this->newLine();

        // 1. Check notifications table
        if (Schema::hasTable('notifications')) {
            $this->line('  [✓] Notifications table already exists.');
        } else {
            $this->line('  Creating notifications table migration...');
            $this->call('notifications:table');
            $this->call('migrate');
            $this->line('  [✓] Notifications table created.');
        }

        // 2. Publish config
        $this->call('vendor:publish', [
            '--tag' => 'indie-notifications-config',
            '--force' => false,
        ]);
        $this->line('  [✓] Config published.');

        // 3. Check User model
        if (class_exists('App\\Models\\User')) {
            $traits = class_uses_recursive('App\\Models\\User');
            if (in_array('Illuminate\\Notifications\\Notifiable', $traits)) {
                $this->line('  [✓] User model has Notifiable trait.');
            } else {
                $this->warn('  [!] User model is missing the Notifiable trait. Add it:');
                $this->line('      use Illuminate\\Notifications\\Notifiable;');
            }
        }

        // 4. Check CSRF meta tag
        $this->newLine();
        $this->info('Add these to your layout:');
        $this->newLine();
        $this->line('  In <head>:');
        $this->line('    <meta name="csrf-token" content="{{ csrf_token() }}">');
        $this->newLine();
        $this->line('  In navbar <ul class="navbar-nav ml-auto">:');
        $this->line("    @include('indie-notifications::partials.bell-dropdown')");
        $this->newLine();
        $this->line('  Before </body>:');
        $this->line("    @include('indie-notifications::partials.bell-scripts')");
        $this->newLine();
        $this->line('  Optional sidebar link:');
        $this->line("    @include('indie-notifications::partials.sidebar-link')");
        $this->newLine();

        $this->info('Done! Visit /notifications to see the full page.');

        return 0;
    }
}
