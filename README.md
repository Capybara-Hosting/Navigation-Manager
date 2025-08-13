# Navigation Manager Extension

A comprehensive navigation management extension for Paymenter that allows you to create and manage custom navigation items with advanced visibility controls and role-based access.

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Technical Details](#technical-details)
- [Support](#support)
- [License](#license)

## ✨ Features

- **Custom Navigation Items**: Add custom links to the main navigation, account dropdown, and dashboard navigation
- **Multiple Link Types**: Support for Laravel routes, external URLs, and custom paths
- **Target Window Control**: Configure links to open in new windows/tabs using `target="_blank"`
- **Role-Based Visibility**: Control who can see navigation items based on user roles
- **Visibility Options**:
  - Public (everyone)
  - Logged-in users only
  - Guests only
  - Specific roles only
- **Hierarchical Navigation**: Support for parent-child relationships (dropdown menus)
- **Ordering**: Custom sort order for navigation items
- **Enable/Disable**: Easily enable or disable navigation items
- **Route Parameters**: Support for passing parameters to Laravel routes

## 🚀 Installation

1. Enable the NavigationManager extension in the Paymenter admin panel
2. The extension will automatically run the required database migrations
3. Navigate to **Navigation Items** in the admin panel to start managing your custom navigation

## 📖 Usage

### Creating Navigation Items

1. Go to **Extensions > Navigation Items** in the admin panel
2. Click **Create** to add a new navigation item
3. Fill in the required information:
   - **Display Name**: The text shown in the navigation
   - **Link Type**: Choose between Laravel Route, External URL, or Custom Path
   - **Link Value**: The actual route name, URL, or path
   - **Open in New Window**: Toggle to make the link open in a new tab/window (`target="_blank"`)
   - **Location**: Where the item should appear (Main Navigation, Account Dropdown, Dashboard)
   - **Visibility**: Who can see this item
   - **Sort Order**: The order in which items appear

### Link Types

#### Laravel Route
- Use this for internal Paymenter routes
- Example: `home`, `dashboard`, `account`
- Can include route parameters in JSON format

#### External URL
- Use for external websites
- Example: `https://example.com`
- Can be configured to open in new window using the "Open in New Window" toggle

#### Custom Path
- Use for custom paths within your domain
- Example: `/custom-page`, `/help`
- Can be configured to open in new window using the "Open in New Window" toggle

### Visibility Options

#### Public
Anyone can see this navigation item

#### Logged In Users Only
Only authenticated users can see this item

#### Guests Only
Only non-authenticated users can see this item

#### Specific Roles Only
Only users with specific roles can see this item. Select the allowed roles when this option is chosen.

### Creating Dropdown Menus

To create dropdown menus:
1. Create a parent navigation item (this will be the dropdown trigger)
2. Create child items and select the parent in the "Parent Item" field
3. Child items will appear in a dropdown menu under the parent

## 💡 Examples

### Adding a Help Link
- **Name**: Help Center
- **Link Type**: External URL
- **Link Value**: https://help.yourdomain.com
- **Open in New Window**: ✓ (enabled)
- **Location**: Main Navigation
- **Visibility**: Public

### Adding a Role-Specific Admin Link
- **Name**: Admin Panel
- **Link Type**: Laravel Route
- **Link Value**: filament.admin.pages.dashboard
- **Location**: Account Dropdown
- **Visibility**: Specific Roles Only
- **Allowed Roles**: Administrator

### Adding a Product Category
- **Name**: Web Hosting
- **Link Type**: Laravel Route
- **Link Value**: category.show
- **Route Parameters**: `{"category": "web-hosting"}`
- **Location**: Main Navigation
- **Visibility**: Public

## ⚙️ Configuration

The extension uses the following database table: `ext_navigation_items`

No additional configuration is required beyond enabling the extension.

## 🔧 Technical Details

### Database Schema

The extension creates and manages the `ext_navigation_items` table with the following key columns:
- `target_blank` (boolean): Controls whether links open in new windows

### Theme Integration

This extension includes modifications to Paymenter's default theme to support the `target="_blank"` functionality:

#### Modified Theme Files

1. **Navigation Link Component** (`themes/default/views/components/navigation/link.blade.php`)
   - Added support for the `target` parameter.
   - Automatically applies `target="_blank"` when needed.
   - The entire file should be replaced with:
   ```blade
   @props(['href', 'spa' => true, 'target' => null])
   <a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex flex-row items-center p-3 gap-2 text-sm font-semibold text-wrap ' . ($href === request()->url() ? 'text-primary' : 'text-base hover:text-base/80')]) }} @if($spa) wire:navigate @endif @if($target) target="{{ $target }}" @endif>
       {{ $slot }}
   </a>
   ```

2. **Main Navigation** (`themes/default/views/components/navigation/index.blade.php`)
   - Updated all navigation link calls to pass the `target` attribute.
   - Supports `target` attributes for both parent and child navigation items.
   - Includes both desktop and mobile navigation implementations.
   - Requires modification on line 87. Replace with:
     ```blade
     <x-navigation.link :href="route($nav['route'], $nav['params'] ?? null)" :spa="isset($nav['spa']) ? $nav['spa'] : true" :target="$nav['target'] ?? null">
     ```

3. **Sidebar Navigation** (`themes/default/views/components/navigation/sidebar-links.blade.php`)
   - Updated sidebar navigation links to support `target` attributes.
   - Covers both accordion-style and direct navigation links.
   - Add the following after line 94:
     ```blade
     :target="$nav['target'] ?? null"
     ```

#### Integration Points

The extension integrates with Paymenter's navigation system through Laravel events:
- Navigation data includes `target` key when `target_blank` is enabled
- Theme components automatically render the correct HTML attributes
- Works seamlessly across all navigation locations (main, account dropdown, dashboard, mobile sidebar)

### Migration Files

- `2025_08_12_create_navigation_items_table.php` - Initial table creation
- `2025_08_13_add_target_blank_to_navigation_items.php` - Adds target_blank functionality

Both migrations run automatically when the extension is enabled.

### Admin Interface Enhancements

The admin interface includes several features for managing the target window functionality:

1. **Form Toggle**: "Open in New Window" toggle in the Link Configuration section
2. **Table Indicator**: Visual icon in the admin table showing which items open in new windows
3. **Backwards Compatibility**: Existing navigation items continue to work without modification

### Version History

- **v1.0.0**: Initial release with basic navigation management
- **v1.1.0**: Added `target="_blank"` functionality with theme integration

### Livewire Component Registration Error

If you encounter an error like:
```
Unable to find component: [paymenter.extensions.others.navigation-manager.admin.resources.navigation-item-resource.pages.create-navigation-item]
```

This indicates that Livewire components aren't properly registered. To fix this:

1. **Clear all caches:**
   ```bash
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Optimize Filament:**
   ```bash
   php artisan filament:optimize
   ```

3. **Regenerate autoloader:**
   ```bash
   composer dump-autoload
   ```

4. **Verify the fix:**
   - Check that the NavigationManager extension is enabled in the admin panel
   - Navigate to Extensions > Navigation Items - it should load without errors
   - The create/edit forms should work properly

This issue typically occurs after extension installation or server cache changes, and the above steps will refresh all component registrations.

## 🆘 Support

This extension integrates with Paymenter's existing navigation system using Laravel events. It listens to:
- `navigation` - for main navigation items
- `navigation.account-dropdown` - for account dropdown items  
- `navigation.dashboard` - for dashboard navigation items

The extension respects Paymenter's existing navigation structure and adds items seamlessly alongside core navigation items.

## 📄 License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

### What This Means

The AGPL-3.0 is a strong copyleft license that ensures:

- **Freedom to Use**: You can use this software for any purpose
- **Freedom to Study**: You can examine how the software works
- **Freedom to Modify**: You can change the software to suit your needs
- **Freedom to Distribute**: You can share the software with others

### Key Requirements

**Any derivative work or modification of this software must also be licensed under AGPL-3.0 and remain open source.** This includes:

- Modified versions of the software
- Software that incorporates this code
- Software that interacts with this code over a network
- Web applications that use this code

### Network Use Provision

If you run a modified version of this software on a server and let other users communicate with it over a network, you must also provide them with the source code of your modified version.

### Full License Text

For the complete license text, see [LICENSE](LICENSE) file or visit [https://www.gnu.org/licenses/agpl-3.0.en.html](https://www.gnu.org/licenses/agpl-3.0.en.html).

### Why AGPL-3.0?

This license was chosen to ensure that:
1. **Innovation remains open**: Any improvements or modifications must be shared back with the community
2. **Network services are transparent**: Users of web applications built with this code have access to the source
3. **Freedom is preserved**: The software and its derivatives remain free and open source forever

---

**Note**: This extension is designed to work with Paymenter, an open-source hosting billing panel. The AGPL-3.0 license ensures that both the extension and any modifications remain open source, contributing to the broader open-source ecosystem.
