<?php

use App\Models\Goal;
use App\Models\Task;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تفاصيل الهدف')] class extends Component {
    public Goal $goal;

    public string $newMilestoneTitle = '';

    /** @var array<int, string> */
    public array $newTaskTitles = [];

    public function mount(Goal $goal): void
    {
        $this->authorize('view', $goal);

        $this->goal = $goal->load('category', 'milestones.tasks');
    }

    public function deleteGoal(): void
    {
        $this->authorize('delete', $this->goal);

        $this->goal->delete();

        $this->redirectRoute('goals.index');
    }

    public function addMilestone(): void
    {
        $this->authorize('update', $this->goal);

        $this->validate(['newMilestoneTitle' => ['required', 'string', 'max:255']]);

        $this->goal->milestones()->create(['title' => $this->newMilestoneTitle]);
        $this->newMilestoneTitle = '';

        $this->refreshGoal();
    }

    public function deleteMilestone(int $milestoneId): void
    {
        $this->authorize('update', $this->goal);

        $this->goal->milestones()->whereKey($milestoneId)->delete();

        $this->refreshGoal();
    }

    public function addTask(int $milestoneId): void
    {
        $this->authorize('update', $this->goal);

        $this->validate([
            'newTaskTitles.'.$milestoneId => ['required', 'string', 'max:255'],
        ]);

        $this->goal->milestones()->findOrFail($milestoneId)
            ->tasks()->create(['title' => $this->newTaskTitles[$milestoneId]]);

        $this->newTaskTitles[$milestoneId] = '';

        $this->refreshGoal();
    }

    public function toggleTask(int $taskId): void
    {
        $this->authorize('update', $this->goal);

        $task = $this->findTask($taskId);

        if ($task->status === 'completed') {
            $task->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $task->markAsCompleted();
        }

        $this->refreshGoal();
    }

    public function deleteTask(int $taskId): void
    {
        $this->authorize('update', $this->goal);

        $this->findTask($taskId)->delete();

        $this->refreshGoal();
    }

    private function findTask(int $taskId): Task
    {
        return Task::whereKey($taskId)
            ->whereHas('milestone', fn ($query) => $query->where('goal_id', $this->goal->id))
            ->firstOrFail();
    }

    private function refreshGoal(): void
    {
        $this->goal->load('category', 'milestones.tasks');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('goals.index') }}" wire:navigate>الأهداف</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $goal->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl" level="1" class="truncate">{{ $goal->title }}</flux:heading>

                    @if ($goal->category)
                        <flux:badge variant="subtle" :color="$goal->category->color ?? 'zinc'" class="!rounded-full">
                            {{ $goal->category->name }}
                        </flux:badge>
                    @endif

                    <flux:badge variant="subtle" :color="match ($goal->status) {
                        'completed' => 'green',
                        'failed' => 'red',
                        'in_progress' => 'blue',
                        default => 'zinc',
                    }">
                        {{ match ($goal->status) {
                            'not_started' => 'لم يبدأ',
                            'in_progress' => 'قيد التنفيذ',
                            'completed' => 'مكتمل',
                            'failed' => 'متوقف',
                        } }}
                    </flux:badge>

                    <flux:badge variant="subtle" :color="$goal->priority === 'high' ? 'red' : ($goal->priority === 'medium' ? 'amber' : 'zinc')">
                        أولوية {{ match ($goal->priority) {
                            'low' => 'منخفضة',
                            'medium' => 'متوسطة',
                            'high' => 'عالية',
                        } }}
                    </flux:badge>
                </div>

                @if ($goal->description)
                    <flux:text>{{ $goal->description }}</flux:text>
                @endif
            </div>

            <div class="flex gap-2">
                <flux:button href="{{ route('goals.edit', $goal) }}" icon="pencil-square" variant="ghost" wire:navigate>
                    تعديل
                </flux:button>
                <flux:button
                    icon="trash"
                    variant="danger"
                    wire:click="deleteGoal"
                    wire:confirm="هل أنت متأكد من حذف هذا الهدف؟"
                >
                    حذف
                </flux:button>
            </div>
        </div>
    </div>

    <flux:card>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <flux:heading size="lg">نسبة الإنجاز</flux:heading>
                <flux:heading size="lg">{{ $goal->progress }}%</flux:heading>
            </div>

            <flux:progress :value="$goal->progress" />

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="text-sm">الأيام المتبقية</flux:text>
                    <flux:heading size="lg">{{ $goal->status === 'completed' ? '—' : max(0, $goal->days_remaining) }} يوم</flux:heading>
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="text-sm">الحالة الزمنية</flux:text>
                    <flux:heading size="lg" :color="$goal->schedule_status === 'on_track' ? 'green' : ($goal->schedule_status === 'behind_schedule' ? 'amber' : 'zinc')">
                        {{ match ($goal->schedule_status) {
                            'on_track' => 'على المسار الصحيح',
                            'behind_schedule' => 'متأخر عن الجدول',
                            'completed' => 'مكتمل',
                        } }}
                    </flux:heading>
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="text-sm">الموعد النهائي</flux:text>
                    <flux:heading size="lg">{{ $goal->due_date?->format('Y-m-d') }}</flux:heading>
                </div>
            </div>
        </div>
    </flux:card>

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl" level="2">المراحل</flux:heading>
            <flux:text>قسّم الهدف لمراحل متتالية وكل مرحلة لمهام قابلة للتنفيذ</flux:text>
        </div>

        @forelse ($goal->milestones as $milestone)
            <flux:card wire:key="milestone-{{ $milestone->id }}" class="flex flex-col gap-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg" class="truncate">
                                {{ $milestone->order > 0 ? $milestone->order.'. ' : '' }}{{ $milestone->title }}
                            </flux:heading>

                            <flux:badge variant="subtle" :color="match ($milestone->status) {
                                'completed' => 'green',
                                'in_progress' => 'blue',
                                default => 'zinc',
                            }">
                                {{ match ($milestone->status) {
                                    'not_started' => 'لم تبدأ',
                                    'in_progress' => 'قيد التنفيذ',
                                    'completed' => 'مكتملة',
                                } }}
                            </flux:badge>
                        </div>

                        @if ($milestone->description)
                            <flux:text>{{ $milestone->description }}</flux:text>
                        @endif
                    </div>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="!text-red-600"
                        wire:click="deleteMilestone({{ $milestone->id }})"
                        wire:confirm="حذف هذه المرحلة وجميع مهامها؟"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <flux:progress :value="$milestone->progress" class="flex-1" />
                    <flux:text class="text-sm font-semibold">{{ $milestone->progress }}%</flux:text>
                </div>

                <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($milestone->tasks as $task)
                        <div class="flex items-center gap-3 py-2.5" wire:key="task-{{ $task->id }}">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$task->status === 'completed' ? 'arrow-uturn-left' : 'check'"
                                :class="$task->status === 'completed' ? '!text-green-600' : '!text-zinc-400'"
                                wire:click="toggleTask({{ $task->id }})"
                            />

                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <flux:text :class="$task->status === 'completed' ? 'line-through opacity-60' : ''">
                                    {{ $task->title }}
                                </flux:text>

                                @if ($task->due_date || $task->priority !== 'medium')
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($task->due_date)
                                            <flux:text class="text-xs {{ $task->due_date->isPast() && $task->status !== 'completed' ? '!text-red-600' : '' }}">
                                                {{ $task->due_date->format('Y-m-d') }}
                                            </flux:text>
                                        @endif

                                        @if ($task->priority !== 'medium')
                                            <flux:badge size="sm" variant="subtle" :color="$task->priority === 'high' ? 'red' : 'zinc'">
                                                {{ $task->priority === 'high' ? 'عالية' : 'منخفضة' }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="!text-red-600"
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="حذف هذه المهمة؟"
                            />
                        </div>
                    @empty
                        <flux:text class="py-2 text-center text-sm">لا توجد مهام في هذه المرحلة</flux:text>
                    @endforelse
                </div>

                <form wire:submit="addTask({{ $milestone->id }})" class="flex flex-col gap-2 sm:flex-row">
                    <flux:input
                        wire:model="newTaskTitles.{{ $milestone->id }}"
                        placeholder="أضف مهمة جديدة..."
                        class="flex-1"
                    />
                    <flux:button type="submit" size="sm" icon="plus">إضافة مهمة</flux:button>
                </form>
            </flux:card>
        @empty
            <flux:card class="flex flex-col items-center gap-2 py-10 text-center">
                <flux:heading>لا توجد مراحل بعد</flux:heading>
                <flux:text>ابدأ بإضافة المرحلة الأولى لهذا الهدف</flux:text>
            </flux:card>
        @endforelse

        <flux:card class="border-dashed">
            <form wire:submit="addMilestone" class="flex flex-col gap-3 sm:flex-row">
                <flux:input wire:model="newMilestoneTitle" placeholder="أضف مرحلة جديدة..." class="flex-1" />
                <flux:button type="submit" variant="primary" icon="plus">إضافة مرحلة</flux:button>
            </form>
            <flux:error name="newMilestoneTitle" />
        </flux:card>
    </div>
</div>