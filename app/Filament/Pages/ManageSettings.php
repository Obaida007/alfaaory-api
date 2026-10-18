<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Single settings page backed by the key/value `settings` table.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى والإعدادات';

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $title = 'إعدادات المتجر';

    protected static ?int $navigationSort = 10;

    /** @var array<string, string|null> */
    public ?array $data = [];

    /** @var array<int, string> */
    protected const KEYS = [
        'site_name', 'admin_whatsapp', 'admin_email', 'facebook_url', 'instagram_url',
        'telegram_url', 'tiktok_url', 'youtube_url', 'about_text', 'privacy_policy',
    ];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['super_admin', 'admin']);
    }

    public function mount(): void
    {
        $all = Setting::allCached();
        $this->form->fill(collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => $all[$key] ?? null])->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('التواصل')->columns(2)->schema([
                    TextInput::make('site_name')->label('اسم المتجر')->required()->maxLength(100),
                    TextInput::make('admin_whatsapp')->label('واتساب الإدارة')->tel()->helperText('بالصيغة الدولية، مثل +963912345678'),
                    TextInput::make('admin_email')->label('بريد الإدارة')->email(),
                ]),
                Section::make('روابط التواصل الاجتماعي')->columns(2)->collapsible()->schema([
                    TextInput::make('facebook_url')->label('فيسبوك')->url(),
                    TextInput::make('instagram_url')->label('إنستغرام')->url(),
                    TextInput::make('telegram_url')->label('تيليجرام')->url(),
                    TextInput::make('tiktok_url')->label('تيك توك')->url(),
                    TextInput::make('youtube_url')->label('يوتيوب')->url(),
                ]),
                Section::make('الصفحات')->schema([
                    Textarea::make('about_text')->label('نص «من نحن»')->rows(8),
                    Textarea::make('privacy_policy')->label('سياسة الخصوصية')->rows(12),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('حفظ')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            if (in_array($key, self::KEYS, true)) {
                Setting::put($key, $value);
            }
        }

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }
}
