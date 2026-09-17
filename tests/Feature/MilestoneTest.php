<?php

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Livewire\Livewire;

test('users can add a milestone to their goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->set('newMilestoneTitle', 'First Phase')
        ->call('addMilestone')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('milestones', [
        'goal_id' => $goal->id,
        'title' => 'First Phase',
    ]);
});

test('milestone title is required', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->set('newMilestoneTitle', '')
        ->call('addMilestone')
        ->assertHasErrors('newMilestoneTitle');
});

test('users cannot add a milestone to another user\'s goal', function () {
    $user = User::factory()->create();
    $otherGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $otherGoal])
        ->set('newMilestoneTitle', 'Hacked')
        ->call('addMilestone')
        ->assertForbidden();
});

test('users can delete a milestone from their goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'To Delete']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('deleteMilestone', $milestone->id);

    expect(Milestone::find($milestone->id))->toBeNull();
});

test('deleting a milestone from another goal is a no-op', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $otherGoal = Goal::factory()->for($user)->create();
    $otherMilestone = $otherGoal->milestones()->create(['title' => 'Foreign']);

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->call('deleteMilestone', $otherMilestone->id);

    expect(Milestone::find($otherMilestone->id))->not->toBeNull();
});

test('milestone progress is the ratio of completed tasks', function () {
    $goal = Goal::factory()->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);

    $milestone->tasks()->create(['title' => 'Done', 'status' => 'completed', 'completed_at' => now()]);
    $milestone->tasks()->create(['title' => 'Pending']);

    expect($milestone->fresh()->progress)->toBe(50);
});
