<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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

    public function mount(): void {
        $user = Auth::user();
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? [$user->avatar] : [],
            'bio' => $user->bio,
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
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
                        ->directory('avatars')
                        ->visibility('public')
                        ->helperText('Tải lên ảnh đại diện của bạn (tối đa 2MB)'),

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
            'avatar' => is_array($data['avatar']) && !empty($data['avatar']) ? $data['avatar'][0] : ($data['avatar'] ?? null),
            'bio' => $data['bio'],
        ];

        // Cập nhật mật khẩu nếu có
        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        /** @var User $user */
        $user->update($updateData);

        // Reset form
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? [$user->avatar] : [],
            'bio' => $user->bio,
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        Notification::make()
            ->title('Cập nhật thành công')
            ->body('Thông tin hồ sơ đã được cập nhật')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool {
        return Auth::check();
    }
}
