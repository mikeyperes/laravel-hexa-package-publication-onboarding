<?php

namespace hexa_package_publication_onboarding\Providers;

use hexa_package_publication_onboarding\Contracts\DestinationPublicationPort;
use hexa_package_publication_onboarding\Contracts\OnboardingOperationStore;
use hexa_package_publication_onboarding\Contracts\SourcePublicationPort;
use hexa_package_publication_onboarding\Orchestration\PublicationOnboardingService;
use hexa_package_publication_onboarding\Orchestration\VerificationPolicy;
use Illuminate\Support\ServiceProvider;

final class PublicationOnboardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/publication-onboarding.php', 'publication-onboarding');

        $this->app->singleton(VerificationPolicy::class);
        $this->app->singleton(
            PublicationOnboardingService::class,
            fn ($app): PublicationOnboardingService => new PublicationOnboardingService(
                $app->make(SourcePublicationPort::class),
                $app->make(DestinationPublicationPort::class),
                $app->make(OnboardingOperationStore::class),
                $app->make(VerificationPolicy::class),
                (array) $app['config']->get('publication-onboarding', []),
            ),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/publication-onboarding.php' => config_path('publication-onboarding.php'),
            ], 'publication-onboarding-config');
        }
    }
}
