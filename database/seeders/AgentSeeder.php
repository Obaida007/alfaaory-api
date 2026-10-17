<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            ['name' => 'أبو حمزة الفاعوري', 'region' => 'الإدارة العامة — جميع أنحاء العالم'],
            ['name' => 'أمجد أبو تيم', 'region' => 'سوريا'],
            ['name' => 'محمد أبو صباح', 'region' => 'سوريا / ريف إدلب / الهبيط'],
        ];

        foreach ($agents as $sort => $agent) {
            Agent::updateOrCreate(['name' => $agent['name']], $agent + ['sort' => $sort, 'is_active' => true]);
        }
    }
}
