@php
    $success = session('success');
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký xét tuyển liên thông GTVT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/ref-form.css') }}">

</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <h1>Đăng ký xét tuyển liên thông GTVT</h1>
                <p>Điền thông tin để gửi hồ sơ đăng ký nhanh cho bộ phận tuyển sinh.</p>
            </div>
            <div class="content">
        @if($success)
        <div class="alert alert-success">{{ $success }}</div>
        @endif
        @if(session('registered_student'))
            @php
                $st = session('registered_student');
            @endphp
            <div class="success-card">
                <div class="success-icon">✓</div>
                <h2 class="success-title">Đăng ký thành công!</h2>
                <p class="success-text">
                    Kính gửi <strong>{{ $st['full_name'] }}</strong>,<br>
                    Chúng tôi đã tiếp nhận thông tin hồ sơ của bạn cho chương trình <br>
                    <span class="success-highlight">{{ $st['major'] }} - Hệ {{ $st['program_type'] }}</span><br>
                    liên thông dự kiến tại <strong>{{ $st['intake_name'] }}</strong>.
                </p>
                <p class="success-text">Dưới đây là <strong>MÃ HỒ SƠ</strong> của bạn dùng để tra cứu trạng thái:</p>
                
                <div class="profile-code-display">
                    {{ $st['profile_code'] }}
                </div>
                
                <p class="success-footer-text">Bạn có thể dùng mã này để tra cứu tại trang Tra cứu hồ sơ.</p>
                
                <a href="{{ route('public.profile.track.form') }}?profile_code={{ $st['profile_code'] }}" class="submit success-link">Tra cứu hồ sơ ngay</a>
                <div class="mt-10">
                    <a href="{{ request()->fullUrl() }}" class="success-re-register">Đăng ký hồ sơ khác</a>
                </div>
            </div>
        @else
            @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form id="student-form" method="POST" action="{{ route('public.ref.submit', ['ref_id' => $ref_id]) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label class="label">Họ và tên <span class="req">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="field-input @error('full_name') border-red-500 @enderror" placeholder="VD: Trần Trung Kiên" required>
                        @error('full_name') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Số điện thoại <span class="req">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="field-input @error('phone') border-red-500 @enderror" placeholder="VD: 0934699191" required>
                        @error('phone') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Email <span class="req">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="field-input @error('email') border-red-500 @enderror" placeholder="VD: kien.tran@gmail.com" required>
                        @error('email') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Ngày tháng năm sinh <span class="req">*</span></label>
                        <input type="date" name="dob" value="{{ old('dob') }}" class="field-input @error('dob') border-red-500 @enderror" required>
                        @error('dob') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field mt-10">
                    <label class="label">Địa chỉ <span class="req">*</span></label>
                    <input type="text" name="address" value="{{ old('address') }}" class="field-input @error('address') border-red-500 @enderror" placeholder="VD: Số 123, Đường ABC, Quận XYZ, TP. Thái Bình" required>
                    @error('address') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="field mt-10">
                    <label class="label">Chương trình đào tạo (Ngành & Hệ) <span class="req">*</span></label>
                    <select id="program_selector" class="field-select @error('quota_id') border-red-500 @enderror" required>
                        <option value="">-- Chọn ngành học & hệ đào tạo --</option>
                        @php
                            $typeLabels = [
                                'REGULAR' => '🎓 HỆ CHÍNH QUY', 
                                'PART_TIME' => '💼 HỆ VỪA HỌC VỪA LÀM', 
                                'DISTANCE' => '🌐 HỆ ĐÀO TẠO TỪ XA'
                            ];
                            $groupedPrograms = collect($programs ?? [])->groupBy('program_name');
                        @endphp
                        @foreach($groupedPrograms as $type => $group)
                            <optgroup label="{{ $typeLabels[$type] ?? $type }}">
                                @foreach($group as $program)
                                    <option value="{{ $program['major_name'] }}|{{ $program['program_name'] }}">
                                        {{ $program['major_name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="field mt-10">
                    <label class="label">Đợt tuyển sinh dự kiến <span class="req">*</span></label>
                    <select name="quota_id" id="quota_id" class="field-select @error('quota_id') border-red-500 @enderror" required disabled>
                        <option value="">-- Vui lòng chọn chương trình học trước --</option>
                    </select>
                    <input type="hidden" name="intake_id" id="intake_id" value="{{ old('intake_id') }}">
                    @error('quota_id') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div id="fee-info-container" class="hidden"></div>

                <div class="field mt-10">
                    <label class="label">Ghi chú</label>
                    <textarea name="notes" class="field-textarea">{{ old('notes') }}</textarea>
                </div>

                <div class="field mt-10 mb-20 flex justify-center">
                    <!-- Hardcoded site key for immediate load -->
                    <div class="g-recaptcha" data-sitekey="6Ldyz74sAAAAAMBNZc87V7Xcf5CD6zIrjTX37kjn" data-callback="recaptchaCallback"></div>
                    @error('g-recaptcha-response') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="submit" id="submit-btn" disabled>
                    <span class="spinner"></span>
                    <span class="btn-text">Gửi đăng ký</span>
                </button>
            </form>
        @endif
        <p class="footer">&copy; {{ date('Y') }} Liên thông Đại học</p>
            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        const programSelector = document.getElementById('program_selector');
        const quotaSelect = document.getElementById('quota_id');
        const intakeHidden = document.getElementById('intake_id');
        const feeContainer = document.getElementById('fee-info-container');
        const form = document.getElementById('student-form');
        const submitBtn = document.getElementById('submit-btn');

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                let errorDiv = field.parentNode.querySelector('.error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                    field.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            }
        }

        function hideError(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                const errorDiv = field.parentNode.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
            }
        }

        window.recaptchaCallback = function() {
            submitBtn.disabled = false;
        };

        const intakesData = @json($intakes ?? []);
        const oldQuotaId = @json(old('quota_id'));

        function getProgramLabel(programCode) {
            const code = String(programCode || '').trim().toUpperCase();
            if (code === 'REGULAR' || code === 'CHÍNH QUY' || code === 'HỆ CHÍNH QUY') return 'REGULAR';
            if (code === 'PART_TIME' || code === 'VỪA HỌC VỪA LÀM' || code === 'HỆ VỪA HỌC VỪA LÀM' || code === 'VHVL') return 'PART_TIME';
            if (code === 'DISTANCE' || code === 'ĐÀO TẠO TỪ XA' || code === 'DTTX') return 'DISTANCE';
            return programCode || 'Chưa xác định';
        }

        function switchTab(tabName) {
            document.querySelectorAll('.info-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            const activeTab = document.querySelector(`.info-tab[data-tab="${tabName}"]`);
            if (activeTab) activeTab.classList.add('active');
            
            const activeContent = document.getElementById(`tab-${tabName}`);
            if (activeContent) activeContent.classList.add('active');
        }

        function updateFeeInfo(quotaId) {
            if (!quotaId) {
                feeContainer.classList.add('hidden');
                return;
            }

            let selectedQuota = null;
            let selectedIntakeId = null;
            for (const intake of intakesData) {
                const q = (intake.quotas || []).find(it => it.id == quotaId);
                if (q) {
                    selectedQuota = q;
                    selectedIntakeId = intake.id;
                    break;
                }
            }

            if (!selectedQuota) {
                feeContainer.classList.add('hidden');
                return;
            }

            intakeHidden.value = selectedIntakeId;

            const programCode = getProgramLabel(selectedQuota.program_name);
            let feeHtml = `
                <div class="fee-info-box">
                    <div class="info-tabs">
                        <div class="info-tab active" data-tab="fees" onclick="switchTab('fees')">
                            <i data-lucide="credit-card"></i> Chi phí & Học phí
                        </div>
                        <div class="info-tab" data-tab="training" onclick="switchTab('training')">
                            <i data-lucide="graduation-cap"></i> Tuyển sinh & Đào tạo
                        </div>
                        <div class="info-tab" data-tab="docs" onclick="switchTab('docs')">
                            <i data-lucide="file-text"></i> Hồ sơ chuẩn bị
                        </div>
                    </div>

                    <!-- TAB 1: CHI PHÍ -->
                    <div id="tab-fees" class="tab-content active">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i data-lucide="banknote"></i> Lệ phí tuyển sinh 
                                <span class="info-badge">Đóng 1 lần</span>
                            </div>
                            <ul class="fee-list">
            `;

            if (programCode === 'REGULAR') {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí hồ sơ thi tuyển</span> <span>60.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí thi tuyển</span> <span>640.000đ</span></li>
                    <li class="fee-item"><span>Học phí ôn tập (3 môn)</span> <span>1.500.000đ</span></li>
                    <li class="fee-item border-top-1 fw-800 c-primary pt-12 mt-8"><span>Tổng chi phí đăng ký</span> <span>1.750.000đ</span></li>
                `;
            } else if (programCode === 'PART_TIME') {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí hồ sơ xét tuyển</span> <span>60.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí xét tuyển/thi tuyển</span> <span>640.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí hồ sơ</span> <span>50.000đ</span></li>
                    <li class="fee-item border-top-1 fw-800 c-primary pt-12 mt-8"><span>Tổng chi phí đăng ký</span> <span>750.000đ</span></li>
                `;
            } else {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí xét tuyển</span> <span>200.000đ</span></li>
                `;
            }

            feeHtml += `
                            </ul>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="book-open"></i> Học phí đào tạo (2025 - 2026)</div>
                            <div class="info-grid">
                                <div><div class="info-item-label">Tổng học phí (4 kỳ)</div><div class="info-item-value">~ 57 - 60 triệu VNĐ</div></div>
                                <div><div class="info-item-label">Đóng theo kỳ</div><div class="info-item-value">~ 14.5 triệu VNĐ/kỳ</div></div>
                            </div>
                        </div>

                        <div class="payment-info">
                            <div class="fee-section-title">
                                <i data-lucide="landmark"></i> Thông tin thanh toán:
                            </div>
                            <div class="payment-container">
                                <div class="payment-details">
                                    <div class="payment-row mb-8"><span class="payment-label w-85">Ngân hàng:</span> <span class="payment-value">BIDV</span></div>
                                    <div class="payment-row mb-8"><span class="payment-label w-85">Số tài khoản:</span> <span class="payment-value">8849994466</span></div>
                                    <div class="payment-row mb-8"><span class="payment-label w-85">Người nhận:</span> <span class="payment-value">Cô Ly</span></div>
                                    <div class="payment-row mb-0"><span class="payment-label w-85">Nội dung:</span> <span class="payment-value fs-13 c-indigo">Họ tên + Ngày sinh</span></div>
                                </div>
                                <div class="qr-code-wrapper w-180 border-indigo-qr">
                                    <img src="${window.location.origin}/assets/qr-ly.png" alt="QR" onerror="this.parentElement.style.display='none'">
                                </div>
                            </div>
                        </div>
                        
                        <div class="fs-12 c-indigo mt-16 lh-1-6 bg-indigo-light p-12 border-radius-12 border-indigo-light flex gap-10 flex-start">
                            <i data-lucide="info" class="w-18px h-18px flex-shrink-0"></i>
                            <div>
                                <strong>Chính sách hoàn phí:</strong> Không hoàn trả lệ phí (trừ khi trường không mở lớp). Có thể bảo lưu hồ sơ trong vòng 2 đợt tuyển sinh liên tiếp.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: TUYỂN SINH & ĐÀO TẠO -->
                    <div id="tab-training" class="tab-content">
                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="clipboard-list"></i> Hình thức & Môn thi</div>
                            <div class="fs-14 c-text-muted mb-12">
                                ${programCode === 'REGULAR' ? 'Kỳ thi tuyển sinh gồm 3 môn chuyên ngành:' : 'Xét tuyển hồ sơ học tập, không yêu cầu thi tuyển.'}
                            </div>
                            ${programCode === 'REGULAR' ? `
                            <div class="info-grid">
                                <div class="bg-light p-10 border-radius-8 border text-center fw-700 fs-13 c-primary">Toán</div>
                                <div class="bg-light p-10 border-radius-8 border text-center fw-700 fs-13 c-primary">Toán rời rạc</div>
                                <div class="span-2 bg-light p-10 border-radius-8 border text-center fw-700 fs-13 c-primary">Cấu trúc dữ liệu & Giải thuật</div>
                            </div>
                            ` : ''}
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="calendar"></i> Thời gian & Lịch học</div>
                            <div class="info-grid mb-12">
                                <div><div class="info-item-label">Thời gian đào tạo</div><div class="info-item-value">2 năm (4 học kỳ)</div></div>
                                <div><div class="info-item-label">Lịch học dự kiến</div><div class="info-item-value">Tối (18h30 - 20h30)</div></div>
                            </div>
                            <div class="fs-13 c-text-muted p-10 bg-light border-radius-8 border-left-4">
                                📍 <strong>Địa điểm:</strong> ${
                                    programCode === 'REGULAR' ? 'Học trực tiếp tại CS1: Số 3 Cầu Giấy, Hà Nội.' : 
                                    (programCode === 'PART_TIME' ? 'Học kết hợp (Hybrid): Offline và Online.' : 'Học Online 100%, thi tập trung cuối kỳ.')
                                }
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="award"></i> Bằng cấp & Chứng chỉ</div>
                            <div class="info-item-value c-emerald mb-12 flex items-center gap-8">
                                <i data-lucide="check-circle" class="w-18px h-18px"></i> Nhận bằng Cử nhân Liên thông Chính quy
                            </div>
                            
                            <div class="info-item-label">Chứng chỉ ngoại ngữ miễn học (nếu có):</div>
                            <div class="flex flex-wrap gap-8 mt-6">
                                <span class="info-badge">IELTS ≥ 4.5</span>
                                <span class="info-badge">TOEIC ≥ 450</span>
                                <span class="info-badge">VSTEP ≥ 5</span>
                            </div>
                            <div class="fs-11 c-text-muted mt-10 lh-1-5">
                                * Chấp nhận: ĐHQG Hà Nội, ĐH Sư phạm HN, ĐH Hà Nội.
                                <br> * Yêu cầu chứng chỉ Giáo dục Quốc phòng của Bộ GD.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: HỒ SƠ -->
                    <div id="tab-docs" class="tab-content">
                        <div class="info-card">
                            <ul class="fee-list gap-12 flex flex-col">
            `;

            if (programCode === 'DISTANCE') {
                feeHtml += `
                    <li class="fee-item flex-col items-start no-border gap-4">
                        <div class="fs-13 flex gap-8 fw-600 c-text-main">
                            <i data-lucide="file-check" class="w-16px c-primary"></i> Phiếu đăng ký hệ Từ xa
                        </div>
                        <div class="ml-24">
                            <a href="https://docs.google.com/document/d/1qH_S7h3ehCjifGNjUAT8OOUPn6d82ZmX0zILlopwghE/edit?tab=t.0" target="_blank" class="c-primary no-decoration fs-13 fw-500">
                                <i data-lucide="download" class="w-12px h-12px display-inline-block valign-middle"></i> Tải phiếu mẫu tại đây
                            </a>
                        </div>
                    </li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 bản sao công chứng CCCD</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 bản sao công chứng Bằng TN CĐ, Bảng điểm</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 02 ảnh 4x6 (ghi rõ thông tin mặt sau)</span></li>
                `;
            } else {
                feeHtml += `
                    <li class="fee-item flex-col items-start no-border gap-4">
                        <div class="fs-13 flex gap-8 fw-600 c-text-main">
                            <i data-lucide="file-check" class="fs-13 w-16px c-primary"></i> Phiếu tuyển sinh CQ hoặc VHVL
                        </div>
                        <div class="ml-24">
                            <a href="https://docs.google.com/document/d/1bJi-xG6ogDXqMFDX8TGrFis5cXPm7EUVUpX4pjKEyJw/edit?tab=t.0" target="_blank" class="c-primary no-decoration fs-13 fw-500">
                                <i data-lucide="download" class="w-12px h-12px display-inline-block valign-middle"></i> Tải phiếu mẫu tại đây
                            </a>
                            <div class="fs-11 c-text-muted mt-2">(Cần xin dấu xác nhận của Xã/Phường hoặc Cơ quan)</div>
                        </div>
                    </li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 Bản sao công chứng Bằng TN Cao đẳng</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 Bản sao công chứng Bằng tốt nghiệp THPT</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 Bản công chứng Bảng điểm Cao đẳng</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 01 Bản sao công chứng Giấy khai sinh & CCCD</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• Giấy khám sức khỏe (A3, bản gốc)</span></li>
                    <li class="fee-item no-border p-0-0-0-24"><span class="c-text-muted fs-13">• 04 ảnh chân dung 4x6 cm (mới chụp)</span></li>
                `;
            }

            feeHtml += `
                            </ul>
                            <div class="mt-15 p-10 bg-rose-light border-radius-8 c-rose-dark fs-12 fw-600 flex gap-8 items-center">
                                <i data-lucide="alert-circle" class="w-16px h-16px"></i> Tất cả giấy tờ bản sao đều phải được công chứng.
                            </div>
                        </div>

                        <div class="info-card bg-emerald-light border-emerald-light">
                            <div class="info-card-title c-emerald-dark"><i data-lucide="map-pin"></i> Địa chỉ nộp hồ sơ</div>
                            <div class="fs-13 c-emerald-dark lh-1-6">
                                <div class="fw-700 fs-14">Số 73 Nguyễn Chí Thanh, Láng Thượng, Đống Đa, Hà Nội</div>
                                <div class="mt-4">Nhà A - Phòng 101 (Văn phòng tuyển sinh)</div>
                                <div class="mt-10 flex items-center gap-8">
                                    <div class="bg-emerald color-white p-4-12 border-radius-20 fw-700">
                                        <i data-lucide="phone" class="w-12px h-12px display-inline-block valign-middle mr-4"></i>
                                        0966.666.585 (Cô Hà)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            feeContainer.innerHTML = feeHtml;
            feeContainer.classList.remove('hidden');
            
            // Re-initialize Lucide icons for dynamic content
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        programSelector.addEventListener('change', function(e, isInitialSelection = false) {
            const selectedValue = this.value;
            quotaSelect.innerHTML = '<option value="">-- Chọn đợt tuyển sinh --</option>';
            feeContainer.classList.add('hidden');
            intakeHidden.value = '';
            
            if (!selectedValue) {
                quotaSelect.disabled = true;
                return;
            }

            const [majorName, programName] = selectedValue.split('|');
            const availableOptions = [];
            intakesData.forEach(intake => {
                const q = (intake.quotas || []).find(it => it.major_name === majorName && it.program_name === programName && it.status === 'active');
                if (q) {
                    const availableSlots = Number(q.available_slots ?? 0);
                    if (availableSlots > 0 || isInitialSelection) {
                        availableOptions.push({
                            quota_id: q.id,
                            intake_name: intake.name,
                            available_slots: availableSlots
                        });
                    }
                }
            });

            if (availableOptions.length > 0) {
                availableOptions.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.quota_id;
                    option.textContent = opt.intake_name + ' (Chỉ tiêu còn lại: ' + opt.available_slots + ')';
                    quotaSelect.appendChild(option);
                });
                quotaSelect.disabled = false;
            } else {
                quotaSelect.innerHTML = '<option value="">-- Hiện chưa có đợt tuyển nào cho ngành này --</option>';
                quotaSelect.disabled = true;
            }
        });

        quotaSelect.addEventListener('change', function() {
            hideError('quota_id');
            updateFeeInfo(this.value);
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                if (!programSelector.value) {
                    alert('Vui lòng chọn chương trình đào tạo!');
                    isValid = false;
                }
                if (!quotaSelect.value) {
                    showError('quota_id', 'Vui lòng chọn đợt tuyển sinh');
                    isValid = false;
                }
                
                if (typeof grecaptcha !== 'undefined') {
                    const response = grecaptcha.getResponse();
                    if (response.length === 0) {
                        alert('Vui lòng xác minh Captcha!');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                } else {
                    const btn = document.getElementById('submit-btn');
                    btn.disabled = true;
                    btn.classList.add('loading');
                    btn.querySelector('.btn-text').textContent = 'Đang xử lý...';
                }
            });
        }

        if (oldQuotaId) {
            let foundMajor = null, foundProgram = null;
            for (const intake of intakesData) {
                const q = (intake.quotas || []).find(it => it.id == oldQuotaId);
                if (q) {
                    foundMajor = q.major_name;
                    foundProgram = q.program_name;
                    break;
                }
            }
            if (foundMajor && foundProgram) {
                programSelector.value = foundMajor + '|' + foundProgram;
                programSelector.dispatchEvent(new Event('change', { bubbles: true }));
                quotaSelect.value = oldQuotaId;
                updateFeeInfo(oldQuotaId);
            }
        }
    </script>
</body>

</html>