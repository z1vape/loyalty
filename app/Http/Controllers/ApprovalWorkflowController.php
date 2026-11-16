<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalWorkflow;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowController extends Controller
{
    /**
     * Display a listing of the approval workflows.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Role List') && !$user->can('Setting List')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki hak akses untuk mengelola Approval Workflow.');
        }

        $roles = Role::orderBy('id')->get();
        $workflows = ApprovalWorkflow::orderBy('id')->get();

        return view('master_data.approval_workflows.index', compact('roles', 'workflows'));
    }

    /**
     * Update the approval workflow roles.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Role Edit') && !$user->can('Setting Create')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki hak akses untuk mengubah Approval Workflow.');
        }

        $validated = $request->validate([
            'workflows' => 'required|array',
            'workflows.*.role_ids' => 'nullable|array',
            'workflows.*.role_ids.*' => 'integer|exists:roles,id',
            'workflows.*.is_active' => 'nullable|in:0,1',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['workflows'] as $id => $data) {
                $workflow = ApprovalWorkflow::find($id);
                if ($workflow) {
                    $roleIds = isset($data['role_ids']) ? array_map('intval', $data['role_ids']) : [];
                    $workflow->update([
                        'role_ids' => $roleIds,
                        'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                    ]);
                }
            }
        });

        return redirect()->route('master-data.approval-workflows.index')
            ->with('success', 'Konfigurasi Approval Workflow berhasil disimpan.');
    }
}

