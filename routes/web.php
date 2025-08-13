<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Others\NavigationManager\Models\NavigationItem;

// Route to handle external URL redirects
Route::get('/navigation-redirect/{id}', function ($id) {
    $item = NavigationItem::findOrFail($id);
    
    if ($item->link_type === 'url') {
        return redirect()->away($item->link_value);
    } elseif ($item->link_type === 'custom') {
        return redirect($item->link_value);
    }
    
    abort(404);
})->name('navigation.redirect');