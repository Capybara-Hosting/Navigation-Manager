<?php

namespace Paymenter\Extensions\Others\NavigationManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavigationItem extends Model
{
    use HasFactory;

    protected $table = 'ext_navigation_items';

    protected $fillable = [
        'name',
        'link_type',
        'link_value',
        'target_blank',
        'route_params',
        'icon',
        'location',
        'visibility',
        'allowed_roles',
        'parent_id',
        'sort_order',
        'is_enabled',
        'description',
    ];

    protected $casts = [
        'route_params' => 'array',
        'allowed_roles' => 'array',
        'is_enabled' => 'boolean',
        'target_blank' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Available link types
     */
    public const LINK_TYPES = [
        'route' => 'Laravel Route',
        'url' => 'External URL',
        'custom' => 'Custom Path',
    ];

    /**
     * Available locations
     */
    public const LOCATIONS = [
        'main' => 'Main Navigation',
        'account_dropdown' => 'Account Dropdown',
        'dashboard' => 'Dashboard Navigation',
    ];

    /**
     * Available visibility options
     */
    public const VISIBILITY_OPTIONS = [
        'public' => 'Public (Everyone)',
        'logged_in' => 'Logged In Users Only',
        'guest' => 'Guests Only',
        'role' => 'Specific Roles Only',
    ];

    /**
     * Get child navigation items
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get parent navigation item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    /**
     * Scope for enabled items
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for specific location
     */
    public function scopeLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope for root items (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the display text for link type
     */
    public function getLinkTypeDisplayAttribute(): string
    {
        return self::LINK_TYPES[$this->link_type] ?? $this->link_type;
    }

    /**
     * Get the display text for location
     */
    public function getLocationDisplayAttribute(): string
    {
        return self::LOCATIONS[$this->location] ?? $this->location;
    }

    /**
     * Get the display text for visibility
     */
    public function getVisibilityDisplayAttribute(): string
    {
        return self::VISIBILITY_OPTIONS[$this->visibility] ?? $this->visibility;
    }

    /**
     * Get the full URL for this navigation item
     */
    public function getUrlAttribute(): ?string
    {
        switch ($this->link_type) {
            case 'route':
                try {
                    $params = $this->route_params ?? [];
                    return route($this->link_value, $params);
                } catch (\Exception $e) {
                    return null;
                }
            case 'url':
                return $this->link_value;
            case 'custom':
                return url($this->link_value);
            default:
                return null;
        }
    }

    /**
     * Check if this item has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all available routes for the dropdown
     * This could be extended to dynamically fetch routes
     */
    public static function getAvailableRoutes(): array
    {
        return [
            'home' => 'Home',
            'dashboard' => 'Dashboard',
            'account' => 'Account',
            'account.security' => 'Account Security',
            'account.credits' => 'Account Credits',
            'tickets' => 'Tickets',
            'category.show' => 'Category (requires slug parameter)',
            // Add more common routes as needed
        ];
    }

    /**
     * Get roles that are allowed to see this item (for display purposes)
     */
    public function roles()
    {
        if (!$this->allowed_roles || $this->visibility !== 'role') {
            return collect();
        }

        return \App\Models\Role::whereIn('id', $this->allowed_roles)->get();
    }
}
