<?php

namespace Database\Factories;

use App\Models\Timer;
use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimerFactory extends Factory
{
    protected $model = Timer::class;

    public function definition(): array
    {
        $statuses = ['running', 'paused', 'stopped'];
        $status = $this->faker->randomElement($statuses);
        
        $startedAt = $this->faker->dateTimeBetween('-2 hours', 'now');
        $elapsedSeconds = $this->faker->numberBetween(60, 7200); // 1 minute to 2 hours
        
        $data = [
            'user_id' => User::factory(),
            'trackable_type' => Issue::class,
            'trackable_id' => Issue::factory(),
            'status' => $status,
            'elapsed_seconds' => $elapsedSeconds,
            'description' => $this->faker->sentence(),
        ];
        
        // Set timestamps based on status
        if ($status === 'running') {
            $data['started_at'] = $startedAt;
        } elseif ($status === 'paused') {
            $data['started_at'] = $startedAt;
            $data['paused_at'] = $this->faker->dateTimeBetween($startedAt, 'now');
        } elseif ($status === 'stopped') {
            $data['started_at'] = $startedAt;
            $data['stopped_at'] = $this->faker->dateTimeBetween($startedAt, 'now');
        }
        
        return $data;
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'paused_at' => null,
            'stopped_at' => null,
        ]);
    }

    public function paused(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-2 hours', '-30 minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'started_at' => $startedAt,
            'paused_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'stopped_at' => null,
        ]);
    }

    public function stopped(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-2 hours', '-30 minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
            'started_at' => $startedAt,
            'stopped_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'paused_at' => null,
        ]);
    }

    public function withProject(): static
    {
        return $this->state(function (array $attributes) {
            $project = Project::factory()->create(['user_id' => $attributes['user_id']]);
            $issue = Issue::factory()->create([
                'user_id' => $attributes['user_id'],
                'project_id' => $project->id
            ]);
            
            return [
                'trackable_type' => Issue::class,
                'trackable_id' => $issue->id,
                'project_id' => $project->id,
                'issue_id' => $issue->id,
            ];
        });
    }
}
