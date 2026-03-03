<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // payments table created in Module 12
        if (!Schema::hasTable('payments')) {
            return Inertia::render('Admin/Payments/Index', [
                'payments' => [
                    'data'          => [],
                    'total'         => 0,
                    'from'          => null,
                    'to'            => null,
                    'last_page'     => 1,
                    'prev_page_url' => null,
                    'next_page_url' => null,
                ],
                'filters' => [],
            ]);
        }

        $query = \DB::table('payments')
            ->join('tenants', 'payments.tenant_id', '=', 'tenants.id')
            ->join('plans',   'payments.plan_id',   '=', 'plans.id')
            ->select('payments.*', 'tenants.name as tenant_name', 'plans.name as plan_name');

        if ($request->filled('method')) {
            $query->where('payments.method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('payments.status', $request->status);
        }

        $payments = $query->latest('payments.created_at')->paginate(25);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters'  => $request->only('method', 'status'),
        ]);
    }

    public function approveCod(Request $request, int $id)
    {
        if (!Schema::hasTable('payments')) {
            return back()->withErrors(['error' => 'Payments not available yet.']);
        }

        \DB::table('payments')->where('id', $id)->update([
            'status'      => 'completed',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'COD payment approved.');
    }
}