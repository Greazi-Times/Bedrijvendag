<?php

namespace App\Filament\Pages;

use App\Settings\HomePageImageSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ManageHomePageImages extends SettingsPage
{
    protected static string|null|\UnitEnum $navigationGroup = 'Settings';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Page media';

    protected static ?string $title = 'Page media';

    protected static ?string $slug = 'page-media';

    protected static string $settings = HomePageImageSettings::class;

    public function mount(): void
    {
        $this->ensureSettingsExist();

        parent::mount();
    }

    public function save(): void
    {
        $this->ensureSettingsExist();

        parent::save();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Site-wide media')
                ->schema([
                    FileUpload::make('site_logo_path')
                        ->label('Site logo')
                        ->image()
                        ->disk('public')
                        ->directory('site')
                        ->imageEditor()
                        ->helperText('Used in the header, mobile menu, and footer. Leave empty to use the default favicon logo.')
                        ->nullable(),

                    FileUpload::make('event_map_path')
                        ->label('Event map')
                        ->image()
                        ->disk('public')
                        ->directory('event-maps')
                        ->imageEditor()
                        ->helperText('Used as the shared blank map on /plattegrond and in the stand marker editor.')
                        ->nullable(),
                ]),

            Section::make('Home page')
                ->schema([
                    TextInput::make('home_youtube_url')
                        ->label('YouTube link or video ID')
                        ->maxLength(2048)
                        ->helperText('Used by the video popup on the home page. You can paste a full YouTube link or only the video ID.')
                        ->nullable()
                        ->columnSpanFull(),

                    FileUpload::make('hero_image_path')
                        ->label('Hero image')
                        ->image()
                        ->disk('public')
                        ->directory('home-page')
                        ->imageEditor()
                        ->helperText('Shown at the top of the home page. Leave empty to use the default hero image.')
                        ->nullable(),

                    FileUpload::make('info_first_image_path')
                        ->label('Info image 1')
                        ->image()
                        ->disk('public')
                        ->directory('home-page')
                        ->imageEditor()
                        ->helperText('First small image in the "Wat is ATIx Bedrijvendag" collage.')
                        ->nullable(),

                    FileUpload::make('info_second_image_path')
                        ->label('Info image 2')
                        ->image()
                        ->disk('public')
                        ->directory('home-page')
                        ->imageEditor()
                        ->helperText('Second small image in the "Wat is ATIx Bedrijvendag" collage.')
                        ->nullable(),

                    FileUpload::make('info_third_image_path')
                        ->label('Info image 3')
                        ->image()
                        ->disk('public')
                        ->directory('home-page')
                        ->imageEditor()
                        ->helperText('Large image in the "Wat is ATIx Bedrijvendag" collage.')
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('About page')
                ->schema([
                    FileUpload::make('about_hero_image_path')
                        ->label('Hero image')
                        ->image()
                        ->disk('public')
                        ->directory('about-page')
                        ->imageEditor()
                        ->helperText('Shown at the top of the About page.')
                        ->nullable(),

                    FileUpload::make('about_info_first_image_path')
                        ->label('Info image 1')
                        ->image()
                        ->disk('public')
                        ->directory('about-page')
                        ->imageEditor()
                        ->helperText('First small image in the About page collage.')
                        ->nullable(),

                    FileUpload::make('about_info_second_image_path')
                        ->label('Info image 2')
                        ->image()
                        ->disk('public')
                        ->directory('about-page')
                        ->imageEditor()
                        ->helperText('Second small image in the About page collage.')
                        ->nullable(),

                    FileUpload::make('about_info_third_image_path')
                        ->label('Info image 3')
                        ->image()
                        ->disk('public')
                        ->directory('about-page')
                        ->imageEditor()
                        ->helperText('Large image in the About page collage.')
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Slides page')
                ->schema([
                    FileUpload::make('slide_image_paths')
                        ->label('Slideshow images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->disk('public')
                        ->directory('slides')
                        ->imageEditor()
                        ->helperText('Shown on the /slides page in the order listed here. Leave empty to use the default slideshow images.')
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private function ensureSettingsExist(): void
    {
        $table = config('settings.repositories.database.table') ?? 'settings';
        $connection = config('settings.repositories.database.connection');
        $group = HomePageImageSettings::group();
        $now = now();

        foreach ($this->settingDefaults() as $name => $default) {
            $exists = DB::connection($connection)
                ->table($table)
                ->where('group', $group)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::connection($connection)
                ->table($table)
                ->insert([
                    'group' => $group,
                    'name' => $name,
                    'locked' => false,
                    'payload' => json_encode($default),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    private function settingDefaults(): array
    {
        return [
            'site_logo_path' => null,
            'event_map_path' => null,
            'home_youtube_url' => 'https://www.youtube.com/watch?v=yMBxJQk7gbg',
            'hero_image_path' => null,
            'info_first_image_path' => null,
            'info_second_image_path' => null,
            'info_third_image_path' => null,
            'about_hero_image_path' => null,
            'about_info_first_image_path' => null,
            'about_info_second_image_path' => null,
            'about_info_third_image_path' => null,
            'slide_image_paths' => [],
        ];
    }
}
