<?php

namespace App\Providers;

use App\Listeners\GrantSignupCredits;
use App\Services\Generation\ProviderRegistry;
use App\Services\PromptEngine\Modules\ColorPaletteModule;
use App\Services\PromptEngine\Modules\CompositionModule;
use App\Services\PromptEngine\Modules\EmotionModule;
use App\Services\PromptEngine\Modules\IdentityModule;
use App\Services\PromptEngine\Modules\LayoutModule;
use App\Services\PromptEngine\Modules\LightingModule;
use App\Services\PromptEngine\Modules\NegativePromptModule;
use App\Services\PromptEngine\Modules\PoseModule;
use App\Services\PromptEngine\Modules\PrintSpecsModule;
use App\Services\PromptEngine\Modules\ProductModule;
use App\Services\PromptEngine\Modules\SceneModule;
use App\Services\PromptEngine\Modules\StyleModule;
use App\Services\PromptEngine\Modules\UserOverrideModule;
use App\Services\PromptEngine\PromptEngine;
use App\Services\PromptEngine\PromptModule;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class);

        $this->app->tag([
            IdentityModule::class,
            PoseModule::class,
            SceneModule::class,
            EmotionModule::class,
            LightingModule::class,
            ColorPaletteModule::class,
            StyleModule::class,
            LayoutModule::class,
            ProductModule::class,
            PrintSpecsModule::class,
            UserOverrideModule::class,
            NegativePromptModule::class,
            CompositionModule::class,
        ], 'prompt.modules');

        $this->app->singleton(PromptEngine::class, function ($app): PromptEngine {
            /** @var iterable<PromptModule> $modules */
            $modules = $app->tagged('prompt.modules');

            return new PromptEngine($modules);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        Event::listen(Registered::class, GrantSignupCredits::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
