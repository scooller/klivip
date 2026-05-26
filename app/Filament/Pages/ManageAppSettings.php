<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;

class ManageAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configuración del Sitio';

    protected static ?string $title = 'Configuración del Sitio';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.manage-app-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Información Básica')
                                    ->schema([
                                        TextInput::make('site_name')
                                            ->label('Nombre del Sitio')
                                            ->required()
                                            ->maxLength(255),

                                        Textarea::make('site_description')
                                            ->label('Descripción')
                                            ->rows(3)
                                            ->maxLength(500),

                                        Toggle::make('enable_registrations')
                                            ->label('Permitir Registros de Usuarios')
                                            ->helperText('Cuando está desactivado, el registro de nuevos usuarios es bloqueado.'),

                                        Toggle::make('enable_home_login_without_code')
                                            ->label('Login home sin código')
                                            ->helperText('Permite acceder desde home usando solo email/teléfono, sin OTP.'),

                                        Toggle::make('enable_profile_unlock_otp')
                                            ->label('Desbloqueo de perfil con código')
                                            ->helperText('Permite solicitar un código OTP para habilitar la edición del perfil.'),

                                        Toggle::make('enable_profile_unlock_magic_link')
                                            ->label('Desbloqueo de perfil con link de un solo uso')
                                            ->helperText('Envía un enlace temporal de un solo uso para habilitar la edición.'),

                                        Toggle::make('hide_birth_date_on_profile')
                                            ->label('Ocultar fecha de nacimiento en perfil')
                                            ->helperText('Oculta la fecha de nacimiento en la vista de perfil de usuario.'),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Optimización para Motores de Búsqueda')
                                    ->schema([
                                        Textarea::make('meta_keywords')
                                            ->label('Palabras Clave (Keywords)')
                                            ->rows(2)
                                            ->helperText('Separadas por comas'),

                                        TextInput::make('meta_author')
                                            ->label('Autor'),

                                        TextInput::make('tag_manager_id')
                                            ->label('Google Tag Manager ID')
                                            ->placeholder('GTM-XXXXXXX')
                                            ->maxLength(50)
                                            ->helperText('ID del contenedor de Google Tag Manager.'),

                                        FileUpload::make('og_image')
                                            ->label('Imagen Open Graph')
                                            ->image()
                                            ->directory('seo')
                                            ->visibility('public')
                                            ->helperText('Imagen para compartir en redes sociales (1200x630px recomendado).'),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('Contacto')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('Información de Contacto')
                                    ->schema([
                                        TextInput::make('contact_email')
                                            ->label('Email de Contacto')
                                            ->email(),

                                        TextInput::make('contact_phone')
                                            ->label('Teléfono')
                                            ->tel(),

                                        Textarea::make('contact_address')
                                            ->label('Dirección')
                                            ->rows(3),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('Redes Sociales')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Enlaces a Redes Sociales')
                                    ->schema([
                                        TextInput::make('facebook_url')
                                            ->label('Facebook')
                                            ->url()
                                            ->placeholder('https://facebook.com/tupagina'),

                                        TextInput::make('instagram_url')
                                            ->label('Instagram')
                                            ->url()
                                            ->placeholder('https://instagram.com/tuusuario'),

                                        TextInput::make('twitter_url')
                                            ->label('Twitter / X')
                                            ->url()
                                            ->placeholder('https://twitter.com/tuusuario'),

                                        TextInput::make('linkedin_url')
                                            ->label('LinkedIn')
                                            ->url()
                                            ->placeholder('https://linkedin.com/company/tuempresa'),

                                        TextInput::make('youtube_url')
                                            ->label('YouTube')
                                            ->url()
                                            ->placeholder('https://youtube.com/@tucanal'),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('Personalización')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Section::make('CSS Personalizado')
                                    ->schema([
                                        Textarea::make('custom_css')
                                            ->label('CSS Adicional')
                                            ->rows(10)
                                            ->helperText('CSS que se inyectará en el frontend.'),
                                    ]),

                                Section::make('Scripts Adicionales')
                                    ->schema([
                                        Textarea::make('header_scripts')
                                            ->label('Scripts en Header')
                                            ->rows(5)
                                            ->helperText('Scripts que se insertarán en <head> (ej: Google Analytics).'),

                                        Textarea::make('footer_scripts')
                                            ->label('Scripts en Footer')
                                            ->rows(5)
                                            ->helperText('Scripts que se insertarán antes de </body>.'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Mantenimiento')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Modo de Mantenimiento')
                                    ->description('Activa el modo de mantenimiento para realizar actualizaciones')
                                    ->schema([
                                        Toggle::make('maintenance_mode')
                                            ->label('Activar Modo de Mantenimiento')
                                            ->helperText('El sitio mostrará un mensaje de mantenimiento.')
                                            ->live(),

                                        RichEditor::make('maintenance_message')
                                            ->label('Mensaje de Mantenimiento')
                                            ->default('Estamos realizando mejoras. Volveremos pronto.')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'blockquote',
                                                'h2',
                                                'h3',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                                'redo',
                                                'undo',
                                            ])
                                            ->visible(fn ($get) => $get('maintenance_mode'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Configuración')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            SiteSetting::current()->update($data);

            Notification::make()
                ->success()
                ->title('Configuración guardada')
                ->body('La configuración del sitio se ha actualizado correctamente.')
                ->send();
        } catch (Halt $exception) {
            return;
        }
    }
}
