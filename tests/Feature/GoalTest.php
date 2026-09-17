<?php

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('goals.index'))
        ->assertRedirect(route('login'));
});

test('users can view only their own goals', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Goal::factory()->for($user)->create(['title' => 'My Goal']);
    Goal::factory()->for($otherUser)->create(['title' => 'Secret Goal']);

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->assertSee('My Goal')
        ->assertDontSee('Secret Goal');
});

test('users can filter goals by status', function () {
    $user = User::factory()->create();

    Goal::factory()->for($user)->create(['title' => 'Active Goal']);
    Goal::factory()->for($user)->completed()->create(['title' => 'Done Goal']);

    Livewire::actingAs($user)
        ->withQueryParams(['status' => 'completed'])
        ->test('pages::goals.index')
        ->assertSee('Done Goal')
        ->assertDontSee('Active Goal');
});

test('users can create a goal', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.create')
        ->set('title', 'Learn Laravel')
        ->set('description', 'Master the framework')
        ->set('category_id', $category->id)
        ->set('priority', 'high')
        ->set('status', 'in_progress')
        ->set('start_date', today()->toDateString())
        ->set('due_date', today()->addMonths(3)->toDateString())
        ->call('save')
        ->assertRedirectToRoute('goals.show', Goal::first());

    $this->assertDatabaseHas('goals', [
        'user_id' => $user->id,
        'title' => 'Learn Laravel',
        'priority' => 'high',
    ]);
});

test('goal creation requires valid dates', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.create')
        ->set('title', 'Bad Goal')
        ->set('start_date', today()->addMonth()->toDateString())
        ->set('due_date', today()->toDateString())
        ->call('save')
        ->assertHasErrors('due_date');
});

test('users can view a goal they own', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $goal])
        ->assertSee($goal->title);
});

test('users cannot view another user\'s goal', function () {
    $user = User::factory()->create();
    $otherGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.show', ['goal' => $otherGoal])
        ->assertForbidden();
});

test('users can update their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.edit', ['goal' => $goal])
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertRedirectToRoute('goals.show', $goal);

    expect($goal->fresh()->title)->toBe('Updated Title');
});

test('users cannot update another user\'s goal', function () {
    $user = User::factory()->create();
    $otherGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.edit', ['goal' => $otherGoal])
        ->assertForbidden();
});

test('users can delete their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->call('deleteGoal', $goal->id);

    expect(Goal::find($goal->id))->toBeNull();
});

test('users cannot delete another user\'s goal', function () {
    $user = User::factory()->create();
    $otherGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->call('deleteGoal', $otherGoal->id)
        ->assertForbidden();
});

test('deleting a goal cascades to its milestones and tasks', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = $goal->milestones()->create(['title' => 'Setup']);
    $milestone->tasks()->create(['title' => 'Install']);

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->call('deleteGoal', $goal->id);

    $this->assertDatabaseMissing('milestones', ['id' => $milestone->id]);
    $this->assertDatabaseMissing('tasks', ['title' => 'Install']);
});
