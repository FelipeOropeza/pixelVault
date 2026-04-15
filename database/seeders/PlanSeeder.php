<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'storage_limit_bytes' => 104857600, // 100 MB
            'description' => 'Plano básico com 100MB de armazenamento',
        ]);

        Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'storage_limit_bytes' => 1073741824, // 1 GB
            'description' => 'Plano Pro com 1GB de armazenamento',
        ]);

        Plan::firstOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'storage_limit_bytes' => 10737418240, // 10 GB
            'description' => 'Plano Premium com 10GB de armazenamento',
        ]);
    }
}
