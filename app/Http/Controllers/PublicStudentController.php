<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class PublicStudentController extends Controller {
    public function showForm($ref_id) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }
        // Danh sách đơn vị để dùng làm dropdown cho "Trường đang học"
        $organizations = Organization::orderBy('name')->pluck('name')->toArray();
        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'organizations' => $organizations,
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
            // Lấy tên đơn vị làm "Trường muốn học"
            'target_university' => $org?->name,
            'major' => $validated['major'] ?? null,
            'source' => 'ref',
            'status' => 'new',
            'notes' => $validated['notes'] ?? null,
        ]);
        return redirect()->back()->with('success', 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.');
    }

    /**
     * Hiển thị form upload bill thanh toán cho sinh viên đã đăng ký.
     */
    public function showPaymentForm(string $ref_id) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }

        return view('ref-payment', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
        ]);
    }

    /**
     * Nhận bill thanh toán từ sinh viên, tạo Payment ở trạng thái SUBMITTED.
     */
    public function submitPayment(string $ref_id, Request $request) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            return back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!']);
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1000',
            'program_type' => 'required|in:REGULAR,PART_TIME',
            'bill' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Tìm student theo số điện thoại và collaborator hiện tại
        $student = Student::where('phone', $validated['phone'])
            ->where('collaborator_id', $collaborator->id)
            ->first();

        if (!$student) {
            return back()->withErrors(['phone' => 'Không tìm thấy hồ sơ sinh viên. Vui lòng gửi form đăng ký trước.']);
        }

        // Lưu bill
        $path = $request->file('bill')->store('bills', 'public');

        // Tạo payment SUBMITTED
        Payment::create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaborator->id,
            'sub_collaborator_id' => null,
            'program_type' => $validated['program_type'],
            'amount' => $validated['amount'],
            'bill_path' => $path,
            'status' => 'SUBMITTED',
        ]);

        return redirect()->back()->with('success', 'Tải lên hóa đơn thành công! Chờ chủ đơn vị xác nhận.');
    }
}
