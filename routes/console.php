<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\Demo\DemoSeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:seed', function () {
    $this->info('Refreshing database...');
    Artisan::call('migrate:fresh', ['--quiet' => true]);
    $this->info('Running demo seeder...');
    $this->call(DemoSeeder::class);
    $this->info('Demo dataset seeded. Log in as admin@example.com / password');
})->purpose('Seed rich demo data');
