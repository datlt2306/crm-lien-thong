<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Student;

class PublicStudentController extends Controller {
    public function showForm($ref_id) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }
        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
        ]);
    }

    public function submitForm($ref_id, Request $request) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            return back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!']);
        }
        $org = $collaborator->organization;
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:students,phone',
            'email' => 'nullable|email|max:255',
            'current_college' => 'nullable|string|max:255',
            'target_university' => 'required|string|max:255',
            'major' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $student = Student::create([
            'full_name' => $validated['full_name'],
            'dob' => $validated['dob'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'organization_id' => $org ? $org->id : null,
            'collaborator_id' => $collaborator->id,
            'current_college' => $validated['current_college'] ?? null,
            'target_university' => $validated['target_university'],
            'major' => $validated['major'] ?? null,
            'source' => 'ref',
            'status' => 'new',
            'notes' => $validated['notes'] ?? null,
        ]);
        return redirect()->back()->with('success', 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.');
    }
}
