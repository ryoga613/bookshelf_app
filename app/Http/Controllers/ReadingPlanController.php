<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\Notification;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReadingPlanController extends Controller
{
    public function index(Request $request)
    {
        $readingPlansQuery = ReadingPlan::with('book')
            ->where('user_id', Auth::id())
            ->orderBy('target_date', 'asc');
        $currentStatus = ($request->query('status') !== null && $request->query('status') !== '')
        ? (int) $request->query('status')
        : null;

        if ($request->filled('status')) {
            $readingPlansQuery = $readingPlansQuery->where('status', $request->status);
        }

        $readingPlans = $readingPlansQuery->get();

        return view('reading-plans.index', compact('currentStatus', 'readingPlans'));

    }

    public function create()
    {

        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = Auth::id();
        $validated['status'] = ReadingPlanStatus::NotCompleted;
        ReadingPlan::create($validated);

        return redirect()->route('reading-plans.index');
    }

    public function complete(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        return redirect()->route('reading-plans.index');
    }

    public function edit(ReadingPlan $plan)
    {
        $this->authorize('update', $plan);

        return view('reading-plans.edit', ['readingPlan' => $plan]);
    }

    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan)
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        // 期限切れの計画は、期日を変更したら「未読」に戻す
        if ($plan->status === ReadingPlanStatus::Expired) {
            $validated['status'] = ReadingPlanStatus::NotCompleted;
        }

        $plan->update($validated);

        return redirect()->route('reading-plans.index');
    }

    public function destroy(ReadingPlan $plan)
    {
        $this->authorize('delete', $plan);

        DB::transaction(function () use ($plan) {
            // この読書計画に紐づくリマインド通知を削除
            // （data は text カラムのため、対象ユーザーの goal_reminder に絞ってから PHP 側で plan_id を照合する）
            $notificationIds = Notification::where('user_id', $plan->user_id)
                ->where('type', 'goal_reminder')
                ->get()
                ->filter(fn (Notification $notification) => ($notification->data['plan_id'] ?? null) === $plan->id)
                ->pluck('id');

            Notification::whereIn('id', $notificationIds)->delete();

            $plan->delete();
        });

        return redirect()->route('reading-plans.index');
    }
}
