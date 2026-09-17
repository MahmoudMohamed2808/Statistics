<?php

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('users can add a task to a milestone', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->set('newTaskTitles.'.$milestone->id, 'Install Laravel')
        ->call('addTask', $milestone->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'milestone_id' => $milestone->id,
        'title' => 'Install Laravel',
    ]);
});

test('task title is required', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->set('newTaskTitles.'.$milestone->id, '')
        ->call('addTask', $milestone->id)
        ->assertHasErrors('newTaskTitles.'.$milestone->id);
});

test('users cannot add a task to another user\'s milestone', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $otherGoal = Goal::factory()->create();
    $otherMilestone = $otherGoal->milestones()->create(['title' => 'Foreign']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->set('newTaskTitles.'.$otherMilestone->id, 'Hacked')
        ->call('addTask', $otherMilestone->id)
        ->assertNotFound();

    $this->assertDatabaseMissing('tasks', ['title' => 'Hacked']);
});

test('users can toggle a task completion', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);
    $task = $milestone->tasks()->create(['title' => 'Do the thing']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->status)->toBe('completed');
    expect($task->fresh()->completed_at)->not->toBeNull();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->status)->toBe('pending');
    expect($task->fresh()->completed_at)->toBeNull();
});

test('users cannot toggle another user\'s task', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $otherTask = Task::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('toggleTask', $otherTask->id)
        ->assertNotFound();
});

test('users can delete a task from their milestone', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);
    $task = $milestone->tasks()->create(['title' => 'To Delete']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('deleteTask', $task->id);

    expect(Task::find($task->id))->toBeNull();
});

test('goal progress aggregates completed tasks across milestones', function () {
    $goal = Goal::factory()->create();
    $first = $goal->milestones()->create(['title' => 'First']);
    $second = $goal->milestones()->create(['title' => 'Second']);

    $first->tasks()->create(['title' => 'Done', 'status' => 'completed', 'completed_at' => now()]);
    $first->tasks()->create(['title' => 'Pending']);
    $second->tasks()->create(['title' => 'Pending']);

    expect($goal->fresh()->progress)->toBe(33);
});

test('goal schedule status flags a behind-schedule goal', function () {
    $goal = Goal::factory()->create([
        'start_date' => today()->subDays(10)->toDateString(),
        'due_date' => today()->addDays(10)->toDateString(),
        'status' => 'in_progress',
    ]);

    expect($goal->schedule_status)->toBe('behind_schedule');
});
