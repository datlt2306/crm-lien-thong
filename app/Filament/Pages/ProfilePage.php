<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\RefCode;
use App\Models\Collaborator;

class ProfilePage extends Page {

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Hồ sơ cá nhân';
    protected static ?string $title = 'Hồ sơ cá nhân';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.pages.profile-page';

    public ?array $data = [];

    protected function getRefLinkForUser(?User $user): string {
        if (!$user) return '';
        $collab = $user->collaborator;
        return ($collab && !empty($collab->ref_id))
            ? (request()->getSchemeAndHttpHost() . '/ref/' . $collab->ref_id)
            : '';
    }

    public function mount(): void {
        $user = Auth::user();
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? [$user->avatar] : [],
            'bio' => $user->bio,
            'ref_link' => $this->getRefLinkForUser($user),
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
            'telegram_chat_id' => $user->telegram_chat_id,
            'notifications' => $user->getNotificationPreferences()->toArray(),
            'proxy_refs' => $user->collaborator ? $user->collaborator->refCodes->toArray() : [],
        ];
    }

    public function form(Schema $schema): Schema {
        return $schema
            ->statePath('data')
            ->model(User::class)
            ->schema(fn() => $this->getFormSchema());
    }

    protected function getFormSchema(): array {
        return [
            Section::make('📋 Thông tin cơ bản')
                ->description('Cập nhật thông tin cá nhân của bạn')
                ->icon('heroicon-o-identification')
                ->components([
                    FileUpload::make('avatar')
                        ->label('Ảnh đại diện')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->dehydrateStateUsing(fn($state) => is_array($state) ? ($state[0] ?? null) : $state),

                    TextInput::make('name')
                        ->label('Họ và tên')
                        ->required(),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),

                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel(),

                    Textarea::make('bio')
                        ->label('Giới thiệu bản thân')
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->columns(2),

            Section::make('🔗 Link giới thiệu')
                ->description('Chia sẻ link này để học viên đăng ký qua bạn')
                ->icon('heroicon-o-link')
                ->visible(fn () => $this->getRefLinkForUser(Auth::user()) !== '')
                ->components([
                    TextInput::make('ref_link')
                        ->label('Link giới thiệu')
                        ->readOnly()
                        ->copyable()
                        ->dehydrated(false),
                ]),

            Section::make('🔐 Bảo mật tài khoản')
                ->description('Thay đổi mật khẩu để bảo vệ tài khoản')
                ->icon('heroicon-o-lock-closed')
                ->components([
                    TextInput::make('current_password')
                        ->label('Mật khẩu hiện tại')
                        ->password()
                        ->required(fn($get) => !empty($get('password'))),

                    TextInput::make('password')
                        ->label('Mật khẩu mới')
                        ->password()
                        ->rules(['min:8'])
                        ->dehydrated(fn($state) => filled($state))
                        ->dehydrateStateUsing(fn($state) => Hash::make($state)),

                    TextInput::make('password_confirmation')
                        ->label('Xác nhận mật khẩu mới')
                        ->password()
                        ->required(fn($get) => !empty($get('password')))
                        ->same('password')
                        ->dehydrated(false),
                ])
                ->columns(1),

            Section::make('📢 Thông báo Telegram')
                ->description('Nhận thông báo tức thì qua Telegram Bot')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->components([
                    TextInput::make('telegram_chat_id')
                        ->label('ID Telegram cá nhân')
                        ->placeholder('Ví dụ: 123456789')
                        ->helperText('Dùng để bạn nhận báo cáo tổng hợp và thông báo từ hệ thống.'),
                ]),

            Section::make('👥 Quản lý nguồn Proxy (CTV Phụ)')
                ->description('Tạo thêm mã giới thiệu cho đàn em. Tiền về túi bạn, tin nhắn báo về Telegram đàn em.')
                ->icon('heroicon-o-users')
                ->visible(fn () => Auth::user()->collaborator !== null)
                ->components([
                    Repeater::make('proxy_refs')
                        ->label('Danh sách nguồn phụ')
                        ->schema([
                            TextInput::make('name')
                                ->label('Tên gợi nhớ')
                                ->required(),
                            TextInput::make('code')
                                ->label('Mã Ref')
                                ->required()
                                ->disabled(fn ($state) => !empty($state))
                                ->dehydrated()
                                ->default(fn() => strtoupper(Str::random(5))),
                            TextInput::make('telegram_chat_id')
                                ->label('ID Telegram đàn em')
                                ->nullable(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
            
            Section::make('🔔 Cấu hình thông báo')
                ->description('Thiết lập các loại thông báo bạn sẽ nhận được')
                ->icon('heroicon-o-bell')
                ->components([
                    Section::make('Telegram Notifications')
                        ->compact()
                        ->schema([
                            Toggle::make('notifications.telegram_student_registered')->label('Sinh viên đăng ký mới'),
                            Toggle::make('notifications.telegram_payment_bill_uploaded')->label('Sinh viên nộp hóa đơn'),
                            Toggle::make('notifications.telegram_payment_verified')->label('Thanh toán xác nhận'),
                            Toggle::make('notifications.telegram_payment_rejected')->label('Thanh toán bị từ chối'),
                            Toggle::make('notifications.telegram_commission_earned')->label('Nhận hoa hồng mới'),
                        ])
                        ->columns(2),
                ])
                ->collapsible(),
        ];
    }

    protected function getFormActions(): array {
        return [
            Action::make('save')
                ->label('Lưu thay đổi')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }

    protected function saveProxyRefs($user, $proxyRefs): void {
        $collaborator = $user->collaborator;
        if (!$collaborator) return;

        $existingIds = $collaborator->refCodes->pluck('id')->toArray();
        $newIds = [];

        foreach ($proxyRefs as $refData) {
            $data = [
                'collaborator_id' => $collaborator->id,
                'name' => $refData['name'],
                'code' => $refData['code'],
                'telegram_chat_id' => $refData['telegram_chat_id'] ?? null,
            ];

            if (isset($refData['id'])) {
                RefCode::where('id', $refData['id'])->update($data);
                $newIds[] = $refData['id'];
            } else {
                $newRef = RefCode::create($data);
                $newIds[] = $newRef->id;
            }
        }

        $idsToDelete = array_diff($existingIds, $newIds);
        if (!empty($idsToDelete)) {
            RefCode::whereIn('id', $idsToDelete)->delete();
        }
    }

    public function save(): void {
        $data = $this->data;
        $user = Auth::user();

        if (!$user) return;

        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                Notification::make()->title('Lỗi xác thực')->body('Mật khẩu không đúng')->danger()->send();
                return;
            }
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'avatar' => $data['avatar'],
            'bio' => $data['bio'] ?? '',
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        /** @var User $user */
        $user->update($updateData);
        $user->update(['telegram_chat_id' => $data['telegram_chat_id'] ?? null]);

        if ($user->collaborator) {
            $user->collaborator->update(['telegram_chat_id' => $data['telegram_chat_id'] ?? null]);
        }

        if (isset($data['proxy_refs'])) {
            $this->saveProxyRefs($user, $data['proxy_refs']);
        }

        if (isset($data['notifications'])) {
            $user->getNotificationPreferences()->update($data['notifications']);
        }

        Notification::make()->title('Cập nhật thành công')->success()->send();
    }

    public static function shouldRegisterNavigation(): bool {
        return false;
    }
}
