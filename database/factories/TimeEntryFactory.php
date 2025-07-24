<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Project;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('-1 week', 'now');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(15, 480) . ' minutes');
        $durationSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();
        
        return [
            'user_id' => User::factory(),
            'started_at' => $startTime,
            'ended_at' => $endTime,
            'duration_seconds' => $durationSeconds,
            'description' => $this->faker->sentence(),
            'trackable_type' => $this->faker->randomElement(['App\\Models\\Project', 'App\\Models\\Issue']),
            'trackable_id' => function (array $attributes) {
                return $attributes['trackable_type'] === 'App\\Models\\Project' 
                    ? Project::factory()->create()->id
                    : Issue::factory()->create()->id;
            },
            'project_id' => function (array $attributes) {
                if ($attributes['trackable_type'] === 'App\\Models\\Project') {
                    return $attributes['trackable_id'];
                } else {
                    // For issues, get the project_id from the issue
                    return Issue::find($attributes['trackable_id'])->project_id ?? Project::factory()->create()->id;
                }
            },
            'issue_id' => function (array $attributes) {
                return $attributes['trackable_type'] === 'App\\Models\\Issue' ? $attributes['trackable_id'] : null;
            },
        ];
    }

    /**
     * Create a time entry for a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $project->id,
            'project_id' => $project->id,
            'issue_id' => null,
        ]);
    }

    /**
     * Create a time entry for a specific issue.
     */
    public function forIssue(Issue $issue): static
    {
        return $this->state(fn (array $attributes) => [
            'trackable_type' => 'App\\Models\\Issue',
            'trackable_id' => $issue->id,
            'project_id' => $issue->project_id,
            'issue_id' => $issue->id,
        ]);
    }

    /**
     * Create a time entry for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a time entry with specific duration.
     */
    public function withDuration(int $minutes): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startTime = $this->faker->dateTimeBetween('-1 week', 'now');
            $endTime = (clone $startTime)->modify("+{$minutes} minutes");
            
            return [
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_seconds' => $minutes * 60,
            ];
        });
    }

    /**
     * Create a time entry that's currently running (no end time).
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);
    }
}
