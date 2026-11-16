<?php

namespace Spatie\NovaTranslatable\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Attachments\Attachment;
use Laravel\Nova\Fields\Attachments\PendingAttachment;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Controllers\FieldAttachmentController;
use Laravel\Nova\Http\Requests\CreateResourceRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Spatie\NovaTranslatable\Translatable;
use Spatie\Translatable\HasTranslations;
use Spatie\Translatable\TranslatableServiceProvider;

class ResourceTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TranslatableServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('translated_posts', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('body')->nullable();
        });

        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/nova/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Translatable::defaultLocales(['en', 'fr']);
        Nova::resources([TranslatedPostResource::class]);
        Storage::fake('public');
    }

    public function test_it_creates_and_updates_translations_without_overwriting_omitted_locales(): void
    {
        $request = CreateResourceRequest::create('/', 'POST', [
            'translations_title_en' => 'Hello',
            'translations_title_fr' => 'Bonjour',
        ]);

        [$post] = TranslatedPostResource::fill($request, new TranslatedPost());
        $post->save();

        $this->assertSame(['en' => 'Hello', 'fr' => 'Bonjour'], $post->fresh()->getTranslations('title'));

        $request = UpdateResourceRequest::create('/', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'translations_title_en' => 'Updated',
        ]));
        [$post] = TranslatedPostResource::fillForUpdate($request, $post->fresh());
        $post->save();

        $this->assertSame(['en' => 'Updated', 'fr' => 'Bonjour'], $post->fresh()->getTranslations('title'));

        $request->merge(['translations_title_fr' => '']);
        [$post] = TranslatedPostResource::fillForUpdate($request, $post->fresh());
        $post->save();

        $this->assertSame('', $post->fresh()->getTranslation('title', 'fr', false));
    }

    public function test_it_validates_each_locale_with_creation_and_update_rules(): void
    {
        $request = CreateResourceRequest::create('/', 'POST', [
            'translations_title_en' => '',
            'translations_title_fr' => 'ab',
        ]);

        $validator = TranslatedPostResource::validatorForCreation($request);

        $this->assertSame(['translations_title_en', 'translations_title_fr'], array_keys($validator->errors()->toArray()));

        $request->merge(['translations_title_en' => 'Hello', 'translations_title_fr' => 'Bonjour']);
        $this->assertTrue(TranslatedPostResource::validatorForCreation($request)->passes());

        $request = UpdateResourceRequest::create('/', 'PUT', [
            'translations_title_en' => 'An excessively long title',
            'translations_title_fr' => 'Salut',
        ]);

        $this->assertSame(['translations_title_en'], array_keys(TranslatedPostResource::validatorForUpdate($request)->errors()->toArray()));
    }

    public function test_it_uploads_persists_and_deletes_a_translated_trix_attachment(): void
    {
        $request = NovaRequest::create('/', 'POST', [
            'draftId' => 'french-draft',
        ], [], ['attachment' => UploadedFile::fake()->create('notes.txt', 1)]);
        $route = new Route('POST', '/', [FieldAttachmentController::class, 'store']);
        $route->bind($request);
        $route->setParameter('resource', TranslatedPostResource::uriKey());
        $route->setParameter('field', 'translations_body_fr');
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $controller = new FieldAttachmentController();
        $payload = $controller->store($request)->getData(true);

        Storage::disk('public')->assertExists($payload['path']);
        $this->assertSame(1, PendingAttachment::count());

        $request = CreateResourceRequest::create('/', 'POST', [
            'translations_title_en' => 'Hello',
            'translations_title_fr' => 'Bonjour',
            'translations_body_fr' => '<div>Pièce jointe</div>',
            'translations_body_frDraftId' => 'french-draft',
        ]);
        $this->app->instance('request', $request);
        [$post, $callbacks] = TranslatedPostResource::fill($request, new TranslatedPost());
        $post->save();
        collect($callbacks)->each->__invoke();

        $this->assertSame('<div>Pièce jointe</div>', $post->fresh()->getTranslation('body', 'fr'));
        $this->assertSame(0, PendingAttachment::count());
        $this->assertSame($post->id, Attachment::firstOrFail()->attachable_id);

        $request->merge(['attachmentUrl' => $payload['url']]);
        $request->setRouteResolver(fn () => $route);
        $this->assertSame(200, $controller->destroyAttachment($request)->getStatusCode());
        Storage::disk('public')->assertMissing($payload['path']);
        $this->assertSame(0, Attachment::count());
    }

    public function test_attachment_fields_are_not_duplicated_when_chaining_configuration(): void
    {
        $request = NovaRequest::create('/');
        $route = new Route('POST', '/', [FieldAttachmentController::class, 'store']);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $fields = Translatable::make([Trix::make('Body')])
            ->locales(['de', 'nl'])
            ->rulesFor('body', 'de', ['required']);

        $this->assertSame(['translations_body_de', 'translations_body_nl'], array_column($fields->data, 'attribute'));
    }
}

class TranslatedPost extends Model
{
    use HasTranslations;

    public $timestamps = false;

    public $translatable = ['title', 'body'];

    protected $guarded = [];
}

class TranslatedPostResource extends Resource
{
    public static $model = TranslatedPost::class;

    public function fields(NovaRequest $request): array
    {
        return [
            Translatable::make([
                Text::make('Title'),
                Trix::make('Body')->withFiles('public'),
            ])->creationRulesFor('title', 'en', ['required'])
                ->rulesFor('title', 'fr', ['nullable', 'min:3'])
                ->updateRulesFor('title', 'en', ['max:12']),
        ];
    }
}
