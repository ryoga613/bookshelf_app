<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Book;
use Illuminate\Support\Carbon;

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

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        return redirect()->route('reading-plans.index');
    }

    public function edit(ReadingPlan $plan)
    {
        return view('reading-plans.edit', ['readingPlan' => $plan]);
    }

    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan)
    {
        $validated = $request->validated();

        $plan->update($validated);

        return redirect()->route('reading-plans.index');
    }

    public function destroy(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $readingPlan->delete();

        return redirect()->route('reading-plans.index');
    }
}
