<?php

use App\Http\Controllers\LT\ApprovalController;
use App\Http\Controllers\LT\CategoryController;
use App\Http\Controllers\LT\ExportController;
use App\Http\Controllers\LT\LoginManagementController;
use App\Http\Controllers\LT\MeetingController;
use App\Http\Controllers\LT\ReportController;
use App\Http\Controllers\LT\ScoringRuleController;
use App\Http\Controllers\LT\TeamController;
use App\Http\Controllers\Team\EntryController;
use App\Http\Controllers\Team\RosterController;
use App\Http\Controllers\Team\TeamProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'appName' => config('app.name'),
        'teams' => \App\Models\Team::active()->orderBy('name')->get(['name', 'short_code', 'crest_color', 'logo_path']),
    ]);
})->name('home');

/*
|--------------------------------------------------------------------------
| Placeholder role areas (Phase 0C)
|--------------------------------------------------------------------------
| Real guards/policies + screens land in Phase 1+. For now these render the
| AppShell with role-aware nav so layout + base routing can be verified.
*/

// Team captain area — requires the `team` guard (cross-guard → 403).
Route::prefix('team')->middleware('guard:team')->group(function () {
    Route::get('/', [\App\Http\Controllers\Team\DashboardController::class, 'index'])->name('team.dashboard');
    // Score entry (Phase 3).
    Route::get('/submit', [EntryController::class, 'index'])->name('team.submit');
    Route::get('/submit/{meeting}', [EntryController::class, 'open'])->name('team.submit.open');
    Route::put('/submit/{meeting}', [EntryController::class, 'saveDraft'])->name('team.submit.save');
    Route::get('/submit/{meeting}/review', [EntryController::class, 'review'])->name('team.submit.review');
    Route::post('/submit/{meeting}/submit', [EntryController::class, 'submit'])->name('team.submit.submit');
    // Roster (FR-MBR-001..003, 007) — strictly own-team.
    Route::get('/roster', [RosterController::class, 'index'])->name('team.roster');
    Route::post('/roster', [RosterController::class, 'store'])->name('team.roster.store');
    Route::get('/roster/{member}', [RosterController::class, 'show'])->name('team.roster.show');
    Route::get('/roster/{member}/edit', [RosterController::class, 'edit'])->name('team.roster.edit');
    Route::put('/roster/{member}', [RosterController::class, 'update'])->name('team.roster.update');
    Route::patch('/roster/{member}/toggle', [RosterController::class, 'toggleActive'])->name('team.roster.toggle');
    // Captain team profile — crest colour only (FR-TEAM-006).
    Route::get('/profile', [TeamProfileController::class, 'edit'])->name('team.profile');
    Route::put('/profile', [TeamProfileController::class, 'update'])->name('team.profile.update');
    // Notifications (Phase 6D).
    Route::get('/notifications', [\App\Http\Controllers\Team\NotificationController::class, 'index'])->name('team.notifications');
});

// Leadership (LT) area — requires the `lt` guard (cross-guard → 403).
Route::prefix('lt')->middleware('guard:lt')->group(function () {
    Route::get('/', fn () => Inertia::render('LT/Overview'))->name('lt.overview');
    // Approval queue + review + actions (Phase 4B/4C).
    Route::get('/queue', [ApprovalController::class, 'queue'])->name('lt.queue');
    Route::get('/queue/{entry}', [ApprovalController::class, 'review'])->name('lt.queue.review');
    Route::put('/queue/{entry}', [ApprovalController::class, 'update'])->name('lt.queue.update');
    Route::post('/queue/{entry}/approve', [ApprovalController::class, 'approve'])->name('lt.queue.approve');
    Route::post('/queue/{entry}/send-back', [ApprovalController::class, 'sendBack'])->name('lt.queue.sendback');
    // Recently approved + unlock (Phase 4D).
    Route::get('/recent', [ApprovalController::class, 'recent'])->name('lt.recent');
    Route::post('/recent/{entry}/unlock', [ApprovalController::class, 'unlock'])->name('lt.recent.unlock');

    // Announcements (Phase 6D).
    Route::get('/announcements', [\App\Http\Controllers\LT\AnnouncementController::class, 'index'])->name('lt.announcements');
    Route::post('/announcements', [\App\Http\Controllers\LT\AnnouncementController::class, 'store'])->name('lt.announcements.store');

    // Reports (Phase 6A).
    Route::get('/reports', [ReportController::class, 'index'])->name('lt.reports');
    Route::get('/reports/categories', [ReportController::class, 'categories'])->name('lt.reports.categories');
    Route::get('/reports/mvp', [ReportController::class, 'mvp'])->name('lt.reports.mvp');
    Route::get('/reports/teams/{team}', [ReportController::class, 'team'])->name('lt.reports.team');

    // CSV exports (Phase 6C).
    Route::get('/exports/standings.csv', [ExportController::class, 'standings'])->name('lt.exports.standings');
    Route::get('/exports/season.csv', [ExportController::class, 'season'])->name('lt.exports.season');
    Route::get('/exports/category-leaders.csv', [ExportController::class, 'categoryLeaders'])->name('lt.exports.categories');
    Route::get('/exports/mvp.csv', [ExportController::class, 'mvp'])->name('lt.exports.mvp');
    Route::get('/exports/teams/{team}.csv', [ExportController::class, 'team'])->name('lt.exports.team');
    // Teams management (FR-TEAM-001..003, 007, 008).
    Route::get('/teams', [TeamController::class, 'index'])->name('lt.teams');
    Route::post('/teams', [TeamController::class, 'store'])->name('lt.teams.store');
    Route::get('/teams/{team}/edit', [TeamController::class, 'edit'])->name('lt.teams.edit');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('lt.teams.update');
    Route::patch('/teams/{team}/toggle', [TeamController::class, 'toggleActive'])->name('lt.teams.toggle');
    // Meetings management (FR-MTG-001..005, 008).
    Route::get('/meetings', [MeetingController::class, 'index'])->name('lt.meetings');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('lt.meetings.store');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('lt.meetings.update');
    Route::patch('/meetings/{meeting}/toggle', [MeetingController::class, 'toggleStatus'])->name('lt.meetings.toggle');
    Route::get('/meetings/{meeting}/categories', [MeetingController::class, 'editCategories'])->name('lt.meetings.categories');
    Route::put('/meetings/{meeting}/categories', [MeetingController::class, 'updateCategories'])->name('lt.meetings.categories.update');

    // Category catalog (FR-SCO-001).
    Route::get('/categories', [CategoryController::class, 'index'])->name('lt.categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('lt.categories.store');
    Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggleActive'])->name('lt.categories.toggle');
    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('lt.categories.reorder');
    // Scoring rules (FR-SCO-002, 003).
    Route::get('/scoring', [ScoringRuleController::class, 'index'])->name('lt.scoring');
    Route::post('/scoring', [ScoringRuleController::class, 'store'])->name('lt.scoring.store');
    Route::get('/scoring/{scoringRule}/edit', [ScoringRuleController::class, 'edit'])->name('lt.scoring.edit');
    Route::put('/scoring/{scoringRule}', [ScoringRuleController::class, 'update'])->name('lt.scoring.update');
    Route::patch('/scoring/{scoringRule}/toggle', [ScoringRuleController::class, 'toggleActive'])->name('lt.scoring.toggle');

    // Login management (issue / reset captain + LT logins).
    Route::get('/logins', [LoginManagementController::class, 'index'])->name('lt.logins');
    Route::post('/logins/captains', [LoginManagementController::class, 'storeCaptain'])->name('lt.logins.captains.store');
    Route::post('/logins/captains/{teamUser}/reset', [LoginManagementController::class, 'resetCaptain'])->name('lt.logins.captains.reset');
    Route::post('/logins/lt', [LoginManagementController::class, 'storeLt'])->name('lt.logins.lt.store');
    Route::post('/logins/lt/{ltUser}/reset', [LoginManagementController::class, 'resetLt'])->name('lt.logins.lt.reset');
});

// Shared league table — any authenticated guard (captain sees own row highlighted).
Route::get('/league', [\App\Http\Controllers\LeagueController::class, 'index'])
    ->middleware('guard:team,lt')->name('league');

// Shared season summary grid.
Route::get('/season', [\App\Http\Controllers\SeasonController::class, 'index'])
    ->middleware('guard:team,lt')->name('season');

/*
|--------------------------------------------------------------------------
| Dev-only sandbox routes (removed / gated before release)
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    // Component/design-token preview.
    Route::get('/design', fn () => Inertia::render('Design/Preview'))->name('design.preview');

    // Preview the on-brand error page for a given status (bypasses debug handler).
    Route::get('/design/error/{status}', function (int $status) {
        abort_unless(in_array($status, [403, 404, 419, 429, 500, 503], true), 404);

        return Inertia::render('Error', ['status' => $status])->toResponse(request())
            ->setStatusCode($status);
    })->name('design.error');
}

/*
|--------------------------------------------------------------------------
| Public display (Phase 6E) — no auth, approved-only, read-only.
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('/league', [\App\Http\Controllers\PublicController::class, 'league'])->name('public.league');
    Route::get('/season', [\App\Http\Controllers\PublicController::class, 'season'])->name('public.season');
    Route::get('/live', [\App\Http\Controllers\PublicController::class, 'live'])->name('public.live');
});

require __DIR__.'/auth.php';
