<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Collaborator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentApiController extends Controller {
    /**
     * Lấy danh sách sinh viên với phân quyền và filtering
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Áp dụng logic phân quyền từ StudentResource
        $query = $this->getAuthorizedQuery($user);

        // Filtering
        $query = $this->applyFilters($query, $request);

        // Sorting
        $query = $this->applySorting($query, $request);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 items per page
        $students = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ]);
    }

    /**
     * Lấy chi tiết một sinh viên
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Áp dụng logic phân quyền từ StudentResource
        $query = $this->getAuthorizedQuery($user);
        $student = $query->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], 404);
        }

        // Load relationships
        $student->load(['collaborator', 'major', 'intake', 'payment']);

        return response()->json([
            'success' => true,
            'data' => $this->formatStudentData($student),
        ]);
    }

    /**
     * Áp dụng logic phân quyền từ StudentResource
     *
     * @param $user
     * @return Builder
     */
    private function getAuthorizedQuery($user): Builder {
        $query = Student::query();

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }


        // CTV chỉ thấy student của chính mình (hệ thống chỉ còn 1 cấp CTV)
        if ($user->role === 'collaborator') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $query->where('collaborator_id', $collaborator->id);
            }
        }

        // Kế toán & cán bộ hồ sơ chỉ thấy học viên đã được CTV xác nhận nộp tiền
        if (
            $user->role === 'accountant'
            || $user->role === 'document'
            || ($user->roles && $user->roles->contains('name', 'accountant'))
        ) {
            return $query->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->whereIn('status', ['submitted', 'verified']);
            });
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
    }


    /**
     * Áp dụng filters từ request
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    private function applyFilters(Builder $query, Request $request): Builder {
        // Filter theo trạng thái
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }


        // Filter theo collaborator_id
        if ($request->has('collaborator_id')) {
            $query->where('collaborator_id', $request->get('collaborator_id'));
        }

        // Filter theo major
        if ($request->has('major')) {
            $query->where('major', $request->get('major'));
        }

        // Filter theo intake_id
        if ($request->has('intake_id')) {
            $query->where('intake_id', $request->get('intake_id'));
        }

        // Filter theo program_type
        if ($request->has('program_type')) {
            $query->where('program_type', $request->get('program_type'));
        }

        // Filter theo source
        if ($request->has('source')) {
            $query->where('source', $request->get('source'));
        }

        // Filter theo payment_status
        if ($request->has('payment_status')) {
            $query->whereHas('payment', function ($paymentQuery) use ($request) {
                $paymentQuery->where('status', $request->get('payment_status'));
            });
        }

        // Search theo tên, số điện thoại, email
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identity_card', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Áp dụng sorting từ request
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    private function applySorting(Builder $query, Request $request): Builder {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort_by
        $allowedSortFields = [
            'id',
            'full_name',
            'phone',
            'email',
            'status',
            'created_at',
            'updated_at',
            'dob',
            'intake_month',
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        // Validate sort_order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Format dữ liệu sinh viên cho response
     *
     * @param Student $student
     * @return array
     */
    private function formatStudentData(Student $student): array {
        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'phone' => $student->phone,
            'email' => $student->email,
            'identity_card' => $student->identity_card,
            'dob' => $student->dob?->format('Y-m-d'),
            'address' => $student->address,
            'status' => $student->status,
            'status_label' => Student::getStatusOptions()[$student->status] ?? $student->status,
            'source' => $student->source,
            'program_type' => $student->program_type,
            'intake_month' => $student->intake_month,
            'major' => $student->major,
            'target_university' => $student->target_university,
            'notes' => $student->notes,
            'collaborator' => $student->collaborator ? [
                'id' => $student->collaborator->id,
                'full_name' => $student->collaborator->full_name,
                'phone' => $student->collaborator->phone,
                'email' => $student->collaborator->email,
                'ref_id' => $student->collaborator->ref_id,
            ] : null,
            'major_info' => $student->major ? [
                'name' => $student->major,
            ] : null,
            'intake_info' => $student->intake ? [
                'id' => $student->intake->id,
                'name' => $student->intake->name,
            ] : null,
            'payment' => $student->payment ? [
                'id' => $student->payment->id,
                'amount' => $student->payment->amount,
                'status' => $student->payment->status,
            ] : null,
            'created_at' => $student->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $student->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
