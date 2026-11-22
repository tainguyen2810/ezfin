<?php

namespace App\Modules\Core\Filament\Resources\NotificationSettings;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Filament\Resources\NotificationSettings\Pages\EditNotificationSetting;
use App\Modules\Core\Filament\Resources\NotificationSettings\Pages\ListNotificationSettings;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Models\NotificationSetting;
use App\Modules\Core\Notifications\Audience;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationCatalog;
use App\Modules\Core\Notifications\NotificationType;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Core\Notifications\TemplatedMail;
use App\Modules\Core\Notifications\TemplateRenderer;
use App\Support\Filament\OutsideLivewireRender;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use UnitEnum;

/**
 * Trang Thông báo: bật/tắt từng loại và từng kênh, người nhận, khoảng nhắc lại, sửa mẫu có biến, xem trước, gửi thử.
 * Danh sách loại do các module khai báo trong code (NotificationCatalog); trang này chỉ lưu phần đã đổi so với mặc định.
 */
class NotificationSettingResource extends Resource
{
    protected static ?string $model = NotificationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'thong-bao';

    protected static ?string $modelLabel = 'thông báo';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Thông báo';

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof NotificationSetting ? ($record->type()?->label ?? $record->key) : 'Thông báo';
    }

    /** @return Builder<NotificationSetting> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('key', array_keys(app(NotificationCatalog::class)->all()));
    }

    public static function form(Schema $schema): Schema
    {
        /** @var NotificationSetting $record */
        $record = $schema->getRecord();
        $type = $record->type();

        if ($type === null) {
            return $schema;
        }

        $tabs = array_map(fn (Channel $channel) => Tab::make($channel->label())->schema(self::templateFields($type, $channel)), $type->channels());

        return $schema->columns(1)->components([
            Section::make('Cài đặt')->description($type->description)->columns(2)->schema([
                Toggle::make('enabled')->label('Đang bật')->columnSpanFull()
                    ->disabled(! $type->canDisable)
                    ->helperText($type->canDisable ? 'Tắt thì không gửi trên kênh nào.' : 'Thông báo bắt buộc, không tắt được.'),
                CheckboxList::make('channels')->label('Kênh gửi')
                    ->options(collect($type->channels())->mapWithKeys(fn (Channel $channel) => [$channel->value => $channel->label()])->all())
                    ->disabled(! $type->canDisable)
                    ->live(),
                TextInput::make('repeat_hours')->label('Nhắc lại sau (giờ)')->numeric()->integer()->minValue(1)->maxValue(720)
                    ->placeholder((string) $type->defaultRepeatHours)
                    ->helperText('Lỗi còn tiếp diễn thì chỉ báo lại sau ngần này giờ, để không gửi liên tục.')
                    ->visible($type->defaultRepeatHours !== null),
                CheckboxList::make('recipient_roles')->label('Vai trò nhận')->options(Role::options())->columns(2)->columnSpanFull()
                    ->helperText('Mọi tài khoản quản trị có vai trò đã chọn đều nhận.')
                    ->visible($type->audience === Audience::Admin),
                TagsInput::make('extra_emails')->label('Email nhận thêm')->placeholder('ten@congty.vn')->columnSpanFull()
                    ->nestedRecursiveRules(['email:rfc', 'max:190'])
                    ->helperText('Chỉ nhận qua email (không có chuông). Nhấn Enter sau mỗi địa chỉ.')
                    ->visible($type->audience === Audience::Admin && $type->supports(Channel::Mail)),
                Text::make('Gửi tới: '.$type->audience->label().'.')->columnSpanFull()->visible($type->audience !== Audience::Admin),
            ]),
            Section::make('Nội dung')
                ->description('Bấm vào một biến để chèn vào chỗ con trỏ trong ô đang soạn. Xóa trống một ô = dùng lại chữ mặc định. Thân email dùng Markdown đơn giản: **in đậm**, dòng bắt đầu bằng "- " là danh sách, dòng trống để sang đoạn mới.')
                ->schema([
                    Text::make('Phần hệ thống tự thêm, không sửa được: '.$type->fixedParts)->visible($type->fixedParts !== null),
                    View::make('core::filament.notification-variables')->viewData(['variables' => $type->variables]),
                    Tabs::make('channels_tabs')->tabs($tabs),
                ]),
            Section::make('Lần gửi gần đây')->collapsed()->schema([
                View::make('core::filament.notification-logs')->viewData(fn () => ['logs' => NotificationLog::where('key', $type->key)->latest('id')->limit(10)->get()]),
            ]),
        ]);
    }

    /** @return list<Component|Field> */
    private static function templateFields(NotificationType $type, Channel $channel): array
    {
        $default = $type->defaultTemplate($channel);
        $path = "templates.{$channel->value}";
        $allowed = array_keys($type->variables);
        $rule = fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($allowed) {
            $unknown = TemplateRenderer::unknown((string) $value, $allowed);

            if ($unknown !== []) {
                $fail('Biến không có trong loại thông báo này: {'.implode('}, {', $unknown).'}.');
            }
        };

        $fields = [
            TextInput::make("$path.subject")->label($channel === Channel::Mail ? 'Tiêu đề email' : 'Tiêu đề')->maxLength(255)
                ->placeholder($default['subject'])->rule($rule)->live(debounce: 600)
                ->extraInputAttributes(['data-notification-template' => true]),
        ];

        if ($channel->hasGreeting()) {
            $fields[] = TextInput::make("$path.greeting")->label('Lời chào')->maxLength(255)
                ->placeholder($default['greeting'] ?: 'Không có')->rule($rule)->live(debounce: 600)
                ->extraInputAttributes(['data-notification-template' => true]);
        }

        $fields[] = Textarea::make("$path.body")->label('Nội dung')->rows(6)->maxLength(5000)
            ->placeholder($default['body'])->rule($rule)->live(debounce: 600)
            ->extraInputAttributes(['data-notification-template' => true]);
        $fields[] = TextInput::make("$path.action_label")->label('Chữ trên nút')->maxLength(80)
            ->placeholder($default['action_label'] ?: 'Không có nút')->rule($rule)->live(debounce: 600)
            ->helperText('Đường link của nút do hệ thống tạo.')
            ->extraInputAttributes(['data-notification-template' => true]);

        return [
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Grid::make(1)->schema($fields),
                View::make('core::filament.notification-preview')->viewData(fn (Get $get) => self::preview($type, $channel, (array) $get($path))),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{channel: Channel, message: ?RenderedMessage, html: ?string, error: ?string}
     */
    public static function preview(NotificationType $type, Channel $channel, array $draft): array
    {
        $template = self::draftTemplate($type, $channel, $draft);

        try {
            $message = app(NotificationCenter::class)->render($type->key, $channel, $type->samples(), $template);
            $html = $channel === Channel::Mail
                ? OutsideLivewireRender::run(fn () => (string) (new TemplatedMail($message, url('/')))->toMail(auth()->user())->render())
                : null;

            return ['channel' => $channel, 'message' => $message, 'html' => $html, 'error' => null];
        } catch (Throwable $e) {
            return ['channel' => $channel, 'message' => null, 'html' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mẫu đang soạn: ô để trống lấy chữ mặc định.
     *
     * @param  array<string, mixed>  $draft
     * @return array{subject: string, greeting: string, body: string, action_label: string}
     */
    public static function draftTemplate(NotificationType $type, Channel $channel, array $draft): array
    {
        $template = $type->defaultTemplate($channel);

        foreach (array_keys($template) as $field) {
            $value = self::normalize($draft[$field] ?? null);

            if ($value !== null) {
                $template[$field] = $value;
            }
        }

        return $template;
    }

    public static function normalize(mixed $value): ?string
    {
        $value = trim(str_replace("\r\n", "\n", (string) $value));

        return $value === '' ? null : $value;
    }

    public static function table(Table $table): Table
    {
        $center = app(NotificationCenter::class);

        return $table
            ->columns([
                TextColumn::make('key')->label('Loại thông báo')->wrap()
                    ->formatStateUsing(fn (NotificationSetting $record) => $record->type()?->label)
                    ->description(fn (NotificationSetting $record) => $record->type()?->description),
                TextColumn::make('audience')->label('Gửi tới')->badge()
                    ->state(fn (NotificationSetting $record) => $record->type()?->audience->label()),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->state(fn (NotificationSetting $record) => $center->channels($record->key) === [] ? 'Đang tắt' : 'Đang bật')
                    ->color(fn (string $state) => $state === 'Đang bật' ? 'success' : 'gray'),
                TextColumn::make('active_channels')->label('Kênh')->listWithLineBreaks()
                    ->state(fn (NotificationSetting $record) => array_map(fn (Channel $channel) => $channel->label(), $center->channels($record->key))),
                TextColumn::make('updated_at')->label('Sửa lần cuối')->dateTime('d/m/Y H:i')
                    ->formatStateUsing(fn (NotificationSetting $record) => $record->updated_at?->gt($record->created_at) ? $record->updated_at->format('d/m/Y H:i') : 'Mặc định'),
            ])
            ->defaultSort('key')
            ->paginated(false)
            ->recordActions([EditAction::make()->label('Sửa')])
            ->recordUrl(fn (NotificationSetting $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationSettings::route('/'),
            'edit' => EditNotificationSetting::route('/{record}'),
        ];
    }
}
