<?php

namespace App\Filament\Resources\RefLinkResource\Pages;

use App\Filament\Resources\RefLinkResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ViewMyRefLink extends Page {
    protected static string $resource = RefLinkResource::class;

    public ?array $data = [];

    public function mount(): void {
        try {
            $user = Auth::user();
            if (!$user) {
                $this->data = ['error' => 'User not authenticated'];
                return;
            }

            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if (!$collaborator) {
                $this->data = ['error' => 'Collaborator not found'];
                return;
            }

            $refLink = request()->getSchemeAndHttpHost() . '/ref/' . $collaborator->ref_id;

            $this->data = [
                'ref_id' => $collaborator->ref_id,
                'ref_link' => $refLink,
                'collaborator_name' => $collaborator->full_name,
                'organization_name' => $collaborator->organization->name ?? 'N/A',
                'downlines_count' => $collaborator->downlines()->count(),
                'total_commission' => $collaborator->commissionItems()->sum('amount'),
            ];
        } catch (\Exception $e) {
            $this->data = ['error' => $e->getMessage()];
        }
    }

    public function getTitle(): string {
        return 'Link giới thiệu của tôi';
    }

    public function getBreadcrumb(): string {
        return 'Link giới thiệu';
    }

    protected function getHeaderActions(): array {
        return [
            \Filament\Actions\Action::make('copy_link')
                ->label('Copy link')
                ->icon('heroicon-o-clipboard')
                ->color('primary')
                ->action(function () {
                    $this->js("
                        navigator.clipboard.writeText('{$this->data['ref_link']}').then(function() {
                            window.dispatchEvent(new CustomEvent('notify', {
                                detail: {
                                    message: 'Đã copy link giới thiệu!',
                                    type: 'success'
                                }
                            }));
                        });
                    ");
                }),
        ];
    }

    public function getContent(): string {
        if (isset($this->data['error'])) {
            return "<div class='p-4 bg-red-100 border border-red-400 text-red-700 rounded'>Lỗi: {$this->data['error']}</div>";
        }

        $refId = $this->data['ref_id'] ?? '';
        $refLink = $this->data['ref_link'] ?? '';
        $collaboratorName = $this->data['collaborator_name'] ?? 'N/A';
        $organizationName = $this->data['organization_name'] ?? 'N/A';
        $downlinesCount = $this->data['downlines_count'] ?? 0;
        $totalCommission = $this->data['total_commission'] ?? 0;

        return "
        <div class='space-y-6'>
            <div class='bg-white rounded-lg shadow-sm border border-gray-200 p-6'>
                <div class='flex items-center justify-between mb-6'>
                    <h2 class='text-lg font-semibold text-gray-900'>Thông tin link giới thiệu</h2>
                    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'>
                        Hoạt động
                    </span>
                </div>
                
                <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                    <div class='space-y-4'>
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Tên cộng tác viên</label>
                            <p class='text-sm text-gray-900'>{$collaboratorName}</p>
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Tổ chức</label>
                            <p class='text-sm text-gray-900'>{$organizationName}</p>
                        </div>

                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Mã giới thiệu</label>
                            <p class='text-lg font-mono font-bold text-blue-600'>{$refId}</p>
                        </div>
                    </div>
                    
                    <div class='space-y-4'>
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Số tuyến dưới</label>
                            <p class='text-2xl font-bold text-green-600'>{$downlinesCount}</p>
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Tổng hoa hồng</label>
                            <p class='text-2xl font-bold text-orange-600'>
                                " . number_format($totalCommission, 0, ',', '.') . " ₫
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class='bg-white rounded-lg shadow-sm border border-gray-200 p-6'>
                <h3 class='text-lg font-semibold text-gray-900 mb-4'>Link giới thiệu của bạn</h3>
                
                <div class='space-y-4'>
                    <div>
                        <label class='block text-sm font-medium text-gray-700 mb-2'>Link hoàn chỉnh</label>
                        <div class='flex items-center space-x-2'>
                            <input type='text' value='{$refLink}' readonly 
                                   class='flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono'
                                   id='ref-link-input'>
                            <button onclick='copyRefLink()' 
                                    class='px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'>
                                Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class='bg-blue-50 border border-blue-200 rounded-md p-4'>
                        <div class='flex'>
                            <div class='flex-shrink-0'>
                                <svg class='h-5 w-5 text-blue-400' viewBox='0 0 20 20' fill='currentColor'>
                                    <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd' />
                                </svg>
                            </div>
                            <div class='ml-3'>
                                <h3 class='text-sm font-medium text-blue-800'>Hướng dẫn sử dụng</h3>
                                <div class='mt-2 text-sm text-blue-700'>
                                    <ul class='list-disc list-inside space-y-1'>
                                        <li>Chia sẻ link này với người muốn đăng ký học</li>
                                        <li>Khi họ đăng ký qua link này, bạn sẽ nhận được hoa hồng</li>
                                        <li>Link có hiệu lực trong 30 ngày kể từ khi click</li>
                                        <li>Bạn có thể theo dõi hoa hồng trong mục 'Hoa hồng'</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                <div class='bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white'>
                    <div class='flex items-center'>
                        <div class='flex-shrink-0'>
                            <svg class='h-8 w-8 text-blue-200' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' />
                            </svg>
                        </div>
                        <div class='ml-4'>
                            <p class='text-sm font-medium text-blue-200'>Mã giới thiệu</p>
                            <p class='text-2xl font-bold'>{$refId}</p>
                        </div>
                    </div>
                </div>
                
                <div class='bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white'>
                    <div class='flex items-center'>
                        <div class='flex-shrink-0'>
                            <svg class='h-8 w-8 text-green-200' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' />
                            </svg>
                        </div>
                        <div class='ml-4'>
                            <p class='text-sm font-medium text-green-200'>Tuyến dưới</p>
                            <p class='text-2xl font-bold'>{$downlinesCount}</p>
                        </div>
                    </div>
                </div>
                
                <div class='bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white'>
                    <div class='flex items-center'>
                        <div class='flex-shrink-0'>
                            <svg class='h-8 w-8 text-orange-200' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' />
                            </svg>
                        </div>
                        <div class='ml-4'>
                            <p class='text-sm font-medium text-orange-200'>Tổng hoa hồng</p>
                            <p class='text-2xl font-bold'>" . number_format($totalCommission, 0, ',', '.') . " ₫</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function copyRefLink() {
            const input = document.getElementById('ref-link-input');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
            
            // Hiển thị thông báo
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Đã copy!';
            button.classList.add('bg-green-600');
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('bg-green-600');
                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        }
        </script>
        ";
    }
}
