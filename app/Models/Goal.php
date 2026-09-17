<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'description',
        'priority', 'status', 'start_date', 'due_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    // ================= Relationships =================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('order');
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Milestone::class);
    }

    // ================= Scopes =================

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['not_started', 'in_progress']);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'failed']);
    }

    // ================= Progress Logic =================

    /**
     * نسبة الإنجاز = عدد التاسكات المكتملة ÷ إجمالي التاسكات في كل الـ Milestones
     */
    public function getProgressAttribute(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->tasks()->where('status', 'completed')->count();

        return (int) round(($completed / $total) * 100);
    }

    /**
     * هل الهدف ماشي كويس بالنسبة للـ Deadline؟
     * بيقارن نسبة الوقت اللي فات من الـ Timeline بنسبة الإنجاز الفعلية.
     */
    public function getScheduleStatusAttribute(): string
    {
        $totalDays = $this->start_date->diffInDays($this->due_date) ?: 1;
        $elapsedDays = $this->start_date->diffInDays(now());

        $expectedProgress = min(100, max(0, ($elapsedDays / $totalDays) * 100));

        if ($this->status === 'completed') {
            return 'completed';
        }

        return $this->progress >= $expectedProgress ? 'on_track' : 'behind_schedule';
    }

    public function getDaysRemainingAttribute(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }
}
