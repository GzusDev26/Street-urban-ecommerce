<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Services\ComplaintDeadlineService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index()
    {
        return Inertia::render('Shop/Legal/Reclamaciones');
    }

    public function store(StoreComplaintRequest $request, ComplaintDeadlineService $deadlineService)
    {
        $submittedAt = CarbonImmutable::now();
        $complaint = DB::transaction(function () use ($request, $deadlineService, $submittedAt) {
            $complaint = Complaint::create([
                ...$request->safe()->except('accept_privacy'),
                'privacy_accepted_at' => $submittedAt,
                'status' => 'Pendiente',
                'submitted_at' => $submittedAt,
                'response_due_at' => $deadlineService->addBusinessDays($submittedAt, 15),
            ]);

            $complaint->update([
                'complaint_number' => sprintf('SU-%s-%06d', $submittedAt->format('Ymd'), $complaint->id),
            ]);

            return $complaint;
        });

        return redirect()->back()->with([
            'success' => 'Tu reclamación fue registrada correctamente. Conserva tu número de registro para futuras consultas.',
            'complaint_number' => $complaint->complaint_number,
        ]);
    }
}
