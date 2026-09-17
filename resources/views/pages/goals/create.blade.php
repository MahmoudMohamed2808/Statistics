<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('هدف جديد')] class extends Component {
    public string $title = '';
    public string $description = '';
    public ?int $category_id = null;
    public string $priority = 'medium';
    public string $status = 'not_started';
    public string $start_date = '';
    public string $due_date = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:not_started,in_progress,completed,failed'],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $goal = auth()->user()->goals()->create($this->only([
            'title', 'description', 'category_id', 'priority', 'status', 'start_date', 'due_date',
        ]));

        $this->redirectRoute('goals.show', $goal);
    }

    public function with(): array
    {
        return [
            'categories' => auth()->user()->categories()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-1">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('goals.index') }}" wire:navigate>الأهداف</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>هدف جديد</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl" level="1">إنشاء هدف جديد</flux:heading>
    </div>

    <flux:card>
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:field>
                <flux:label>عنوان الهدف</flux:label>
                <flux:input wire:model="title" placeholder="مثال: تعلم Laravel 13" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>الوصف</flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="وصف مختصر للهدف وسبب أهميته" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>التصنيف</flux:label>
                    <flux:select wire:model="category_id">
                        <option value="">بدون تصنيف</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category_id" />
                </flux:field>

                <flux:field>
                    <flux:label>الأولوية</flux:label>
                    <flux:select wire:model="priority">
                        <option value="low">منخفضة</option>
                        <option value="medium">متوسطة</option>
                        <option value="high">عالية</option>
                    </flux:select>
                    <flux:error name="priority" />
                </flux:field>

                <flux:field>
                    <flux:label>تاريخ البداية</flux:label>
                    <flux:input type="date" wire:model="start_date" />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>تاريخ الاستحقاق</flux:label>
                    <flux:input type="date" wire:model="due_date" />
                    <flux:error name="due_date" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>الحالة</flux:label>
                <flux:select wire:model="status">
                    <option value="not_started">لم يبدأ</option>
                    <option value="in_progress">قيد التنفيذ</option>
                    <option value="completed">مكتمل</option>
                    <option value="failed">متوقف</option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button href="{{ route('goals.index') }}" variant="ghost" wire:navigate>إلغاء</flux:button>
                <flux:button type="submit" variant="primary">إنشاء الهدف</flux:button>
            </div>
        </form>
    </flux:card>
</div>