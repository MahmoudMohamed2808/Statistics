# خطة مشروع Goal Tracker – التخطيط الكامل

> Laravel 13 + Blade (وبعدين Livewire/Filament اختياري) + MySQL/SQLite

---

## 🎯 الهدف من المشروع
نظام لإدارة الأهداف طويلة المدى وتقسيمها لخطوات قابلة للتنفيذ، مع متابعة يومية للتقدم والوقت والاستمرارية (Streak)، وتحليلات توضح هل المستخدم ماشي صح ولا متأخر عن جدوله.

---

## 🗺️ نظرة عامة على المراحل

| # | المرحلة | المخرج النهائي | الحالة |
|---|---------|-----------------|--------|
| 0 | التجهيز | مشروع Laravel + Auth جاهز | ✅ اتعمل |
| 1 | Core Loop | Goals → Milestones → Tasks + Progress | ✅ اتعمل (الجداول والـ Models) |
| 2 | Daily Planning + Dashboard | صفحة "النهاردة" + الصفحة الرئيسية | 🔜 جاية |
| 3 | Time Tracking | تسجيل وقت فعلي لكل Task | 🔜 |
| 4 | Streak + Statistics | نظام الاستمرارية + صفحة إحصائيات | 🔜 |
| 5 | Goal Analytics + Journal | صفحة تفصيلية للهدف + يوميات | 🔜 |
| 6 | Achievements + Notifications | إنجازات + تنبيهات (Events/Queues) | 🔜 |
| 7 | تحسينات (اختياري) | Livewire/Filament + Multi-user كامل | 🔜 |

---

## 📦 المرحلة 0: التجهيز *(خلصت)*
**اللي اتعمل:**
- Laravel 13 project
- Auth (Breeze/Fortify) لتسجيل الدخول والخروج
- كل الجداول فيها `user_id` من البداية (استعداد لـ Multi-user لاحقًا حتى لو مستخدم واحد دلوقتي)

---

## 🎯 المرحلة 1: Core Loop *(خلصت – الجداول والـ Models)*
**الجداول:** `categories`, `goals`, `milestones`, `tasks`

**اللي شغال دلوقتي:**
- علاقات كاملة: Goal → hasMany → Milestone → hasMany → Task
- `Goal::progress` و `Milestone::progress`: نسبة مئوية محسوبة تلقائيًا من الـ Tasks المكتملة
- `Goal::schedule_status`: on_track / behind_schedule بمقارنة الوقت المنقضي بالتقدم الفعلي
- Scopes: `active()`, `completed()`, `overdue()` على Goal، و `pending()`, `dueToday()`, `overdue()` على Task

**اللي ناقص فيها (وهنعمله دلوقتي كامتداد للمرحلة دي):**
- `GoalController`, `MilestoneController`, `TaskController` (Resource Controllers)
- `StoreGoalRequest`, `UpdateGoalRequest`, `StoreTaskRequest` (Form Requests للـ Validation)
- Views بسيطة بـ Blade: صفحة كل الأهداف، صفحة تفاصيل هدف، فورم إضافة/تعديل
- Policy بسيطة (`GoalPolicy`) تتأكد إن المستخدم يقدر يشوف/يعدل بس أهدافه هو

**Laravel Concepts المستخدمة:** Eloquent Relationships, Query Scopes, Form Requests, Policies, Route Model Binding

---

## 📅 المرحلة 2: Daily Planning + Dashboard
**الهدف:** صفحة تجمع كل الـ Tasks المستحقة النهاردة من كل الأهداف، وصفحة رئيسية تلخص الوضع العام.

**اللي هيتعمل:**
- `DashboardController`: يجيب
  - عدد الأهداف النشطة/المكتملة
  - Tasks المستحقة النهاردة (query عبر `Task::whereHas('milestone.goal', fn ...)`)
  - نسبة الإنجاز اليومي (X من Y مهام)
- صفحة "اليوم" (`/today`): Checklist تفاعلية تعلّم الـ Task Completed بضغطة زرار (AJAX بسيط أو Livewire)
- Widget بسيط في الـ Dashboard: قائمة الأهداف النشطة مع الـ Progress Bar بتاعة كل واحد

**جدول جديد:** لا يوجد (بنستخدم الموجود بذكاء عبر الـ Queries)

**Laravel Concepts:** Eager Loading (`with()`), Aggregation Queries (`count`, `avg`), API Resources (لو هتعمل AJAX)

---

## ⏱️ المرحلة 3: Time Tracking
**الهدف:** تسجيل الوقت الفعلي اللي اتصرف على كل Task.

**جدول جديد:** `time_entries`
```
id, task_id, started_at, ended_at, duration_minutes, timestamps
```

**اللي هيتعمل:**
- زرار Start/Stop على كل Task (أو إدخال يدوي: من - لحد)
- عند الـ Stop: حساب `duration_minutes` وتحديث `actual_minutes` بتاع الـ Task تلقائيًا (عبر Observer أو Event)
- عرض "الوقت الكلي" على مستوى Task / Milestone / Goal (Sum عبر العلاقات)

**Laravel Concepts:** Observers, Database Transactions, Carbon diffs

---

## 🔥 المرحلة 4: Streak + Statistics
**الهدف:** حساب أيام الاستمرارية + صفحة إحصائيات أسبوعية/شهرية.

**جدول جديد:** `daily_logs`
```
id, user_id, date, tasks_completed, minutes_worked, timestamps
```
> بيتحدث تلقائيًا (Job يومي أو عند كل Task completion) بدل ما يتحسب Live كل مرة — أسرع للـ Statistics.

**اللي هيتعمل:**
- حساب `current_streak`: أيام متتالية فيها `tasks_completed > 0`
- صفحة Statistics:
  - Weekly: Chart بسيط (Chart.js) لعدد الـ Tasks/اليوم
  - Monthly: مقارنة بالشهر اللي فات (نسبة تغيّر %)
- Achievement بسيط مبدئي: "🔥 7 Days Streak" (تفصيلها الكامل في المرحلة 6)

**Laravel Concepts:** Scheduler (Task Scheduling)، Aggregation متقدمة، Caching بسيط للإحصائيات

---

## 📈 المرحلة 5: Goal Analytics + Daily Journal
**الهدف:** صفحة تفصيلية لكل هدف + يوميات نصية.

**جدول جديد:** `daily_logs` (لو مش اتعمل) نضيفله عمود `note` نصي، أو جدول منفصل `journal_entries`
```
id, user_id, goal_id (nullable), date, content, timestamps
```

**اللي هيتعمل:**
- صفحة `/goals/{goal}`:
  - Progress bar كبيرة + عدد Tasks (Total/Completed/Pending/Overdue)
  - إجمالي الوقت المصروف (من `time_entries`)
  - الأيام المتبقية + `schedule_status` (on_track/behind)
- صفحة Journal بسيطة: Textarea + تاريخ، وقائمة بالمدخلات السابقة

**Laravel Concepts:** Complex Eloquent queries, Query Builder مباشر لو احتجت أداء أفضل

---

## 🔔 المرحلة 6: Achievements + Notifications
**الهدف:** نظام إنجازات ديناميكي + تنبيهات حقيقية.

**جداول جديدة:** `achievements`, `user_achievements`
```
achievements: id, key, title, description, icon
user_achievements: id, user_id, achievement_id, earned_at
```

**اللي هيتعمل:**
- `Event: TaskCompleted` يتطلق عند كل `markAsCompleted()`
- `Listener: CheckAchievements` يشيك هل المستخدم استحق إنجاز جديد (100 Task، 7 Days Streak... إلخ)
- Laravel Notifications: تنبيه "Task due tomorrow" و "Goal deadline approaching" (عبر Database Notifications + قناة Email اختياري)
- `Scheduler`: Job يومي يشيك المواعيد المستحقة ويبعت التنبيهات (يحتاج Queue Worker)

**Laravel Concepts:** Events & Listeners, Notifications, Queues, Jobs, Scheduler — دي أهم مرحلة تعليميًا في Laravel المتقدم

---

## 💡 المرحلة 7 (اختياري): تحسينات
- تحويل الفورمات لـ Livewire (تفاعلية بدون Reload)
- أو استخدام Filament لعمل لوحة تحكم Admin كاملة بسرعة
- Multi-user كامل: لو حابب تفتحه لمستخدمين تانيين فعليًا (الأساس جاهز من المرحلة 0)
- Tags نظام: `tags`, `goal_tags`, `task_tags` لو حبيت تصنيف إضافي مرن

---

## 🗃️ خريطة الجداول الكاملة (بترتيب الظهور عبر المراحل)

```
users              → المرحلة 0
categories         → المرحلة 1
goals              → المرحلة 1
milestones         → المرحلة 1
tasks              → المرحلة 1
time_entries       → المرحلة 3
daily_logs         → المرحلة 4
journal_entries    → المرحلة 5
achievements       → المرحلة 6
user_achievements  → المرحلة 6
notifications      → المرحلة 6 (جدول جاهز من Laravel نفسه)
```

---

## ⚙️ ملخص Laravel Concepts حسب المرحلة

| المرحلة | أهم المفاهيم |
|---------|--------------|
| 1 | Relationships, Scopes, Form Requests, Policies |
| 2 | Eager Loading, Aggregation |
| 3 | Observers, Transactions |
| 4 | Scheduler, Caching |
| 5 | Complex Queries |
| 6 | Events, Listeners, Notifications, Queues, Jobs |
| 7 | Livewire/Filament |

---

## ✅ الخطوة الجاية فورًا
إحنا واقفين آخر حاجة عند: الجداول والـ Models جاهزة (مرحلة 1)، وناقص فيها الـ Controllers/Requests/Views.

**الاقتراح:** نكمّل باقي المرحلة 1 (Controllers + Form Requests + Views بسيطة) قبل ما ننتقل للمرحلة 2، عشان يبقى عندك تطبيق شغال فعليًا من المتصفح مش بس Tinker.
