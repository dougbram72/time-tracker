<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Issue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectIssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create sample projects
        $project1 = Project::create([
            'user_id' => $user->id,
            'name' => 'Time Tracker App',
            'description' => 'Laravel-based time tracking application',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $project2 = Project::create([
            'user_id' => $user->id,
            'name' => 'Client Website',
            'description' => 'E-commerce website for client',
            'color' => '#10B981',
            'is_active' => true,
        ]);

        $project3 = Project::create([
            'user_id' => $user->id,
            'name' => 'Mobile App',
            'description' => 'React Native mobile application',
            'color' => '#F59E0B',
            'is_active' => true,
        ]);

        // Create sample issues for project 1
        Issue::create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'title' => 'Implement timer widget frontend',
            'description' => 'Create Alpine.js timer widget with mobile-responsive design',
            'priority' => 'high',
            'status' => 'in_progress',
            'is_active' => true,
        ]);

        Issue::create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'title' => 'Add project/issue selection',
            'description' => 'Integrate timer with project and issue selection functionality',
            'priority' => 'medium',
            'status' => 'open',
            'is_active' => true,
        ]);

        Issue::create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'title' => 'Fix timer accuracy bug',
            'description' => 'Timer elapsed time calculation has rounding errors',
            'priority' => 'urgent',
            'status' => 'resolved',
            'is_active' => true,
        ]);

        // Create sample issues for project 2
        Issue::create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'title' => 'Setup payment gateway',
            'description' => 'Integrate Stripe payment processing',
            'priority' => 'high',
            'status' => 'open',
            'is_active' => true,
        ]);

        Issue::create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'title' => 'Design product catalog',
            'description' => 'Create responsive product listing and detail pages',
            'priority' => 'medium',
            'status' => 'in_progress',
            'is_active' => true,
        ]);

        // Create some standalone issues (no project)
        Issue::create([
            'user_id' => $user->id,
            'project_id' => null,
            'title' => 'Customer support ticket #123',
            'description' => 'User unable to login to their account',
            'priority' => 'high',
            'status' => 'open',
            'external_id' => 'TICKET-123',
            'is_active' => true,
        ]);

        Issue::create([
            'user_id' => $user->id,
            'project_id' => null,
            'title' => 'Server maintenance',
            'description' => 'Update server packages and security patches',
            'priority' => 'low',
            'status' => 'open',
            'is_active' => true,
        ]);

        $this->command->info('Created sample projects and issues for testing');
    }
}
