<?php

namespace Paymenter\Extensions\Others\NavigationManager;

use App\Classes\Extension\Extension;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource;
use Paymenter\Extensions\Others\NavigationManager\Models\NavigationItem;

class NavigationManager extends Extension
{
    public function getConfig($values = [])
    {
        try {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('Use this extension to manage custom navigation items. To create or edit navigation items, go to <a class="text-primary-600" href="' . NavigationItemResource::getUrl() . '">Navigation Items</a>.'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('You can use this extension to manage custom navigation items. You\'ll need to enable this extension above to get started.'),
                ],
            ];
        }
    }

    public function getMetadata()
    {
        return [
            'name' => 'Navigation Manager',
            'description' => 'Manage custom navigation items with role-based visibility, ordering, and target window control',
            'version' => '1.1.0',
            'author' => 'Dankata Pich',
        ];
    }

    public function enabled()
    {
        // Run migrations
        Artisan::call('migrate', [
            '--path' => 'extensions/Others/NavigationManager/database/migrations/2024_12_19_000000_create_navigation_items_table.php',
            '--force' => true
        ]);
        
        // Run the target_blank migration
        Artisan::call('migrate', [
            '--path' => 'extensions/Others/NavigationManager/database/migrations/2024_12_20_000000_add_target_blank_to_navigation_items.php',
            '--force' => true
        ]);
    }

    public function disabled()
    {
        // Optional: Clean up when disabled
    }

    public function boot()
    {
        // Register routes if needed
        if (file_exists(__DIR__ . '/routes/web.php')) {
            require __DIR__ . '/routes/web.php';
        }

        // Register views
        View::addNamespace('navigationmanager', __DIR__ . '/resources/views');

        // Listen to navigation events and inject custom items
        Event::listen('navigation', function () {
            $items = NavigationItem::where('is_enabled', true)
                ->where('location', 'main')
                ->orderBy('sort_order', 'asc')
                ->get();

            $navigationItems = [];

            foreach ($items as $item) {
                if (!$this->canUserSeeItem($item)) {
                    continue;
                }

                $navItem = [
                    'name' => $item->name,
                    'priority' => $item->sort_order,
                ];

                // Handle different link types
                switch ($item->link_type) {
                    case 'route':
                        try {
                            // Verify route exists before adding
                            if (\Route::has($item->link_value)) {
                                $navItem['route'] = $item->link_value;
                                if ($item->route_params) {
                                    $navItem['params'] = $item->route_params ?: [];
                                }
                            } else {
                                // If route doesn't exist, skip this item
                                continue 2;
                            }
                        } catch (\Exception $e) {
                            // Skip invalid routes
                            continue 2;
                        }
                        break;
                    case 'url':
                    case 'custom':
                        // For external URLs and custom paths, use redirect route
                        $navItem['route'] = 'navigation.redirect';
                        $navItem['params'] = ['id' => $item->id];
                        $navItem['spa'] = false;
                        break;
                }

                // Add icon if specified
                if ($item->icon) {
                    $navItem['icon'] = $item->icon;
                }

                // Add target="_blank" if specified
                if ($item->target_blank) {
                    $navItem['target'] = '_blank';
                }

                // Handle children/dropdown items
                $children = NavigationItem::where('parent_id', $item->id)
                    ->where('is_enabled', true)
                    ->orderBy('sort_order', 'asc')
                    ->get();

                if ($children->count() > 0) {
                    $navItem['children'] = [];
                    foreach ($children as $child) {
                        if (!$this->canUserSeeItem($child)) {
                            continue;
                        }

                        $childItem = [
                            'name' => $child->name,
                        ];

                        switch ($child->link_type) {
                            case 'route':
                                try {
                                    if (\Route::has($child->link_value)) {
                                        $childItem['route'] = $child->link_value;
                                        if ($child->route_params) {
                                            $childItem['params'] = $child->route_params ?: [];
                                        }
                                    } else {
                                        continue 2;
                                    }
                                } catch (\Exception $e) {
                                    continue 2;
                                }
                                break;
                            case 'url':
                            case 'custom':
                                $childItem['route'] = 'navigation.redirect';
                                $childItem['params'] = ['id' => $child->id];
                                $childItem['spa'] = false;
                                break;
                        }

                        // Add target="_blank" if specified for child items
                        if ($child->target_blank) {
                            $childItem['target'] = '_blank';
                        }

                        $navItem['children'][] = $childItem;
                    }
                }

                $navigationItems[] = $navItem;
            }

            return $navigationItems;
        });

        // Listen to account dropdown navigation events
        Event::listen('navigation.account-dropdown', function () {
            $items = NavigationItem::where('is_enabled', true)
                ->where('location', 'account_dropdown')
                ->orderBy('sort_order', 'asc')
                ->get();

            $navigationItems = [];

            foreach ($items as $item) {
                if (!$this->canUserSeeItem($item)) {
                    continue;
                }

                $navItem = [
                    'name' => $item->name,
                    'priority' => $item->sort_order,
                ];

                switch ($item->link_type) {
                    case 'route':
                        try {
                            if (\Route::has($item->link_value)) {
                                $navItem['route'] = $item->link_value;
                                if ($item->route_params) {
                                    $navItem['params'] = $item->route_params ?: [];
                                }
                            } else {
                                continue 2;
                            }
                        } catch (\Exception $e) {
                            continue 2;
                        }
                        break;
                    case 'url':
                    case 'custom':
                        $navItem['route'] = 'navigation.redirect';
                        $navItem['params'] = ['id' => $item->id];
                        $navItem['spa'] = false;
                        break;
                }

                // Add target="_blank" if specified for account dropdown items
                if ($item->target_blank) {
                    $navItem['target'] = '_blank';
                }

                $navigationItems[] = $navItem;
            }

            return $navigationItems;
        });

        // Listen to dashboard navigation events
        Event::listen('navigation.dashboard', function () {
            $items = NavigationItem::where('is_enabled', true)
                ->where('location', 'dashboard')
                ->orderBy('sort_order', 'asc')
                ->get();

            $navigationItems = [];

            foreach ($items as $item) {
                if (!$this->canUserSeeItem($item)) {
                    continue;
                }

                $navItem = [
                    'name' => $item->name,
                    'priority' => $item->sort_order,
                ];

                switch ($item->link_type) {
                    case 'route':
                        try {
                            if (\Route::has($item->link_value)) {
                                $navItem['route'] = $item->link_value;
                                if ($item->route_params) {
                                    $navItem['params'] = $item->route_params ?: [];
                                }
                            } else {
                                continue 2;
                            }
                        } catch (\Exception $e) {
                            continue 2;
                        }
                        break;
                    case 'url':
                    case 'custom':
                        $navItem['route'] = 'navigation.redirect';
                        $navItem['params'] = ['id' => $item->id];
                        $navItem['spa'] = false;
                        break;
                }

                if ($item->icon) {
                    $navItem['icon'] = $item->icon;
                }

                // Add target="_blank" if specified for dashboard items
                if ($item->target_blank) {
                    $navItem['target'] = '_blank';
                }

                $navigationItems[] = $navItem;
            }

            return $navigationItems;
        });
    }

    /**
     * Check if user can see this navigation item based on visibility rules
     * 
     * Made by the team at Capybara Hosting (https://capybarahost.xyz)
     */
    private function canUserSeeItem(NavigationItem $item): bool
    {
        switch ($item->visibility) {
            case 'public':
                return true;
            case 'logged_in':
                return Auth::check();
            case 'guest':
                return !Auth::check();
            case 'role':
                if (!Auth::check()) {
                    return false;
                }
                
                $allowedRoles = $item->allowed_roles ?: [];
                if (empty($allowedRoles)) {
                    return true;
                }
                
                $user = Auth::user();
                return $user->role_id !== null && in_array($user->role_id, $allowedRoles);
            default:
                return true;
        }
    }
}
