# Goal Tracker – المرحلة 1 (Core Loop)

## خطوات التركيب
1. انسخ محتوى `database/migrations/` و `app/Models/` جوه مشروع Laravel بتاعك في نفس الأماكن.
2. في `app/Models/User.php` ضيف العلاقتين دول:
```php
public function goals(): HasMany
{
    return $this->hasMany(Goal::class);
}

public function categories(): HasMany
{
    return $this->hasMany(Category::class);
}
```
3. شغّل:
```bash
php artisan migrate
```
4. جرب في Tinker:
```bash
php artisan tinker
```
```php
$user = User::first();

$goal = $user->goals()->create([
    'title' => 'Learn Laravel 13',
    'priority' => 'high',
    'status' => 'in_progress',
    'start_date' => now(),
    'due_date' => now()->addMonths(3),
]);

$milestone = $goal->milestones()->create(['title' => 'Eloquent', 'order' => 1]);

$milestone->tasks()->create(['title' => 'Relationships']);
$milestone->tasks()->create(['title' => 'Eager Loading', 'status' => 'completed', 'completed_at' => now()]);

$goal->progress;          // نسبة الإنجاز %
$goal->schedule_status;   // on_track / behind_schedule
$goal->days_remaining;    // الأيام المتبقية
```

## اللي جاهز دلوقتي
- Goals → Milestones → Tasks بعلاقات كاملة (hasMany / hasManyThrough)
- حساب Progress تلقائي على مستوى الـ Goal والـ Milestone (بناءً على نسبة الـ Tasks المكتملة)
- Scopes جاهزة: `active()`, `completed()`, `overdue()` على الـ Goal، و `dueToday()`, `pending()` على الـ Task
- `schedule_status`: بيقارن نسبة الوقت المنقضي من مدة الهدف بنسبة الإنجاز الفعلية عشان يقولك on_track ولا behind_schedule

## الخطوة الجاية (مرحلة 2)
- Controller + Routes لعرض الأهداف (GoalController resource)
- Form Requests للـ Validation (StoreGoalRequest, StoreTaskRequest)
- صفحة "Daily Planning" اللي بتجيب كل الـ Tasks المستحقة النهاردة من كل الأهداف (استخدام `whereHas` على الـ Milestone/Goal)

قولّي لما تخلص المرحلة دي وتيجي نبني الـ Controllers والـ Views مع بعض.
