<?php

use App\Models\Goal;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('الأهداف')] class extends Component {
    #[Url]
    public string $status = '';

    public function deleteGoal(Goal $goal): void
    {
        $this->authorize('delete', $goal);

        $goal->delete();
    }

    public function with(): array
    {
        return [
            'goals' => auth()->user()->goals()
                ->with('category')
                ->withCount([
                    'tasks',
                    'tasks as completed_tasks_count' => fn ($query) => $query->completed(),
                ])
                ->when($this->status === 'active', fn ($query) => $query->active())
                ->when($this->status === 'completed', fn ($query) => $query->completed())
                ->when($this->status === 'overdue', fn ($query) => $query->overdue())
                ->latest()
                ->paginate(9),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl" level="1">الأهداف</flux:heading>
            <flux:text>تابع أهدافك طويلة المدى وتقدمك نحو إنجازها</flux:text>
        </div>

        <flux:button href="{{ route('goals.create') }}" icon="plus" wire:navigate>
            هدف جديد
        </flux:button>
    </div>

    <flux:separator />

    <div class="flex flex-wrap gap-2">
        @foreach ([
            '' => 'الكل',
            'active' => 'نشطة',
            'completed' => 'مكتملة',
            'overdue' => 'متأخرة',
        ] as $key => $label)
            <flux:button
                :variant="$status === $key ? 'primary' : 'ghost'"
                :href="route('goals.index', $key ? ['status' => $key] : [])"
                wire:navigate
            >
                {{ $label }}
            </flux:button>
        @endforeach
    </div>

    @forelse ($goals as $goal)
        <flux:card>
            <div class="flex flex-col gap-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg" class="truncate">
                                <a href="{{ route('goals.show', $goal) }}" class="hover:underline" wire:navigate>
                                    {{ $goal->title }}
                                </a>
                            </flux:heading>

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
                        </div>

                        @if ($goal->description)
                            <flux:text class="line-clamp-2">{{ $goal->description }}</flux:text>
                        @endif
                    </div>

                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('goals.edit', $goal)" wire:navigate />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            class="!text-red-600"
                            wire:click="deleteGoal({{ $goal->id }})"
                            wire:confirm="هل أنت متأكد من حذف هذا الهدف؟"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <flux:progress :value="$goal->progress" />
                        <flux:text class="shrink-0 text-sm font-semibold">{{ $goal->progress }}%</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <flux:text>
                            {{ $goal->completed_tasks_count }} / {{ $goal->tasks_count }} مهام
                        </flux:text>

                        @if ($goal->status !== 'completed')
                            <flux:badge variant="subtle" :color="$goal->schedule_status === 'on_track' ? 'green' : 'amber'">
                                {{ $goal->schedule_status === 'on_track' ? 'على المسار الصحيح' : 'متأخر عن الجدول' }}
                            </flux:badge>

                            <flux:text>
                                متبقي {{ max(0, $goal->days_remaining) }} يوم
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>
    @empty
        <flux:card class="flex flex-col items-center gap-2 py-12 text-center">
            <flux:heading>لا توجد أهداف بعد</flux:heading>
            <flux:text>ابدأ بوضع هدفك الأول واقسمه لخطوات قابلة للتنفيذ</flux:text>
            <flux:button href="{{ route('goals.create') }}" icon="plus" variant="primary" wire:navigate>
                إنشاء هدف جديد
            </flux:button>
        </flux:card>
    @endforelse

    @if ($goals->hasPages())
        <flux:pagination :paginator="$goals" wire:navigate />
    @endif
</div>