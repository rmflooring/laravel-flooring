<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\ProductStyle;
use App\Models\MicrosoftCalendar;
use App\Observers\ProductStyleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Allow admins to bypass permission checks
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('admin') ? true : null;
        });

        ProductStyle::observe(ProductStyleObserver::class);

        View::composer('components.calendar.globals', function ($view) {
            $groupIds = [
                '451694e6-e1d4-4b5b-9c11-6cee3c9c8ca9', // Team RM
                'b8483c56-fc4b-4734-8011-335b88c7e4ad',  // RM – RFM / Measures
                'a6890136-56b9-42fc-ac2b-8e05c98c0e8c',  // RM – Installations
                '4bfd495c-4df2-4eaa-9d8c-987c4ef23b02',  // RM – Warehouse
            ];

            $options = MicrosoftCalendar::whereIn('group_id', $groupIds)
                ->selectRaw('MIN(id) as id, name, group_id')
                ->groupBy('group_id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name])
                ->values();

            $view->with('calendarOptions', $options);
        });
    }
}
