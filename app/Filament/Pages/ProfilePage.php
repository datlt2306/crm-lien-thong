<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class ProfilePage extends Page {

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Hồ sơ cá nhân';
    protected static ?string $title = 'Hồ sơ cá nhân';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.pages.profile-page';

    public ?array $data = [];

    /**
     * Lấy link giới thiệu cho CTV (user có collaborator khớp email và có ref_id).
     */
    protected function getRefLinkForUser(?User $user): string {
        if (!$user || $user->role !== 'ctv') {
            return '';
        }
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
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->helperText('Tải lên ảnh đại diện của bạn (tối đa 2MB)')
                        ->dehydrateStateUsing(fn($state) => is_array($state) ? ($state[0] ?? null) : $state)
                        ->dehydrated(true),

                    TextInput::make('name')
                        ->label('Họ và tên')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Nhập họ và tên của bạn'),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Nhập địa chỉ email của bạn')
                        ->helperText('Email này sẽ được sử dụng để đăng nhập'),

                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('Nhập số điện thoại của bạn'),

                    Textarea::make('bio')
                        ->label('Giới thiệu bản thân')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('Viết một vài dòng giới thiệu về bản thân...')
                        ->helperText('Tối đa 500 ký tự'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('🔗 Link giới thiệu')
                ->description('Chia sẻ link này để học viên đăng ký qua bạn')
                ->icon('heroicon-o-link')
                ->visible(fn () => $this->getRefLinkForUser(Auth::user()) !== '')
                ->components([
                    TextInput::make('ref_link')
                        ->label('Link giới thiệu')
                        ->readOnly()
                        ->copyable()
                        ->dehydrated(false)
                        ->helperText('Bấm vào biểu tượng copy để sao chép link. Học viên dùng link này để đăng ký và nộp hóa đơn qua bạn.'),
                ])
                ->columnSpanFull(),

            Section::make('🔐 Bảo mật tài khoản')
                ->description('Thay đổi mật khẩu để bảo vệ tài khoản')
                ->icon('heroicon-o-lock-closed')
                ->components([
                    TextInput::make('current_password')
                        ->label('Mật khẩu hiện tại')
                        ->password()
                        ->required(fn($get) => !empty($get('password')))
                        ->helperText('Nhập mật khẩu hiện tại để xác thực'),

                    TextInput::make('password')
                        ->label('Mật khẩu mới')
                        ->password()
                        ->rules(['min:8'])
                        ->dehydrated(fn($state) => filled($state))
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->helperText('Để trống nếu không muốn thay đổi mật khẩu'),

                    TextInput::make('password_confirmation')
                        ->label('Xác nhận mật khẩu mới')
                        ->password()
                        ->required(fn($get) => !empty($get('password')))
                        ->same('password')
                        ->dehydrated(false)
                        ->helperText('Nhập lại mật khẩu mới để xác nhận'),
                ])
                ->columns(1)
                ->columnSpanFull(),



            Section::make('📢 Thông báo Telegram')
                ->description('Nhận thông báo tức thì qua Telegram Bot')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->components([
                    TextInput::make('telegram_chat_id')
                        ->label('Telegram Chat ID')
                        ->placeholder('Ví dụ: 123456789')
                        ->helperText('Chat với @userinfobot hoặc @GetIDsBot để lấy Chat ID của bạn. Bạn phải chat với Bot của hệ thống trước khi nhận tin.'),
                ])
                ->columnSpanFull(),
            
            Section::make('🔔 Cấu hình thông báo')
                ->description('Thiết lập các loại thông báo bạn sẽ nhận được')
                ->icon('heroicon-o-bell')
                ->components([
                    Section::make('Telegram Notifications')
                        ->compact()
                        ->schema([
                            Toggle::make('notifications.telegram_student_registered')
                                ->label('Sinh viên đăng ký mới'),
                            Toggle::make('notifications.telegram_payment_bill_uploaded')
                                ->label('Sinh viên nộp hóa đơn mới'),
                            Toggle::make('notifications.telegram_payment_verified')
                                ->label('Thanh toán được xác nhận'),
                            Toggle::make('notifications.telegram_payment_rejected')
                                ->label('Thanh toán bị từ chối'),
                            Toggle::make('notifications.telegram_commission_earned')
                                ->label('Nhận được hoa hồng mới'),
                        ])
                        ->columns(2),

                    Section::make('Email Notifications')
                        ->compact()
                        ->schema([
                            Toggle::make('notifications.email_student_registered')
                                ->label('Sinh viên đăng ký mới'),
                            Toggle::make('notifications.email_payment_bill_uploaded')
                                ->label('Sinh viên nộp hóa đơn mới'),
                            Toggle::make('notifications.email_payment_verified')
                                ->label('Thanh toán được xác nhận'),
                            Toggle::make('notifications.email_payment_rejected')
                                ->label('Thanh toán bị từ chối'),
                            Toggle::make('notifications.email_commission_earned')
                                ->label('Nhận được hoa hồng mới'),
                        ])
                        ->columns(2),
                ])
                ->collapsible()
                ->columnSpanFull(),
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

    public function save(): void {
        // Lấy data từ state
        $data = $this->data;
        $user = Auth::user();

        if (!$user) {
            return;
        }

        // Kiểm tra mật khẩu hiện tại nếu có thay đổi mật khẩu
        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('Lỗi xác thực')
                    ->body('Mật khẩu hiện tại không đúng')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Cập nhật thông tin user
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'avatar' => $data['avatar'],
            'bio' => $data['bio'] ?? '',
        ];

        // Cập nhật mật khẩu nếu có
        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        /** @var User $user */
        $user->update($updateData);

        // Cập nhật telegram_chat_id
        $user->update(['telegram_chat_id' => $data['telegram_chat_id'] ?? null]);

        // Cập nhật notification preferences
        if (isset($data['notifications'])) {
            $user->getNotificationPreferences()->update($data['notifications']);
        }

        // Refresh user để lấy dữ liệu mới từ database
        $user->refresh();

        // Reset form
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? [$user->avatar] : [],
            'bio' => $user->bio ?? '',
            'ref_link' => $this->getRefLinkForUser($user),
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
            'telegram_chat_id' => $user->telegram_chat_id,
            'notifications' => $user->getNotificationPreferences()->toArray(),
        ];

        Notification::make()
            ->title('Cập nhật thành công')
            ->body('Thông tin hồ sơ đã được cập nhật')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool {
        return false; // Ẩn khỏi navigation sidebar
    }
}
