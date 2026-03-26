<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use HolartWeb\AxoraCMS\Models\TPanelSettings;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class SettingsController extends Controller
{
    /**
     * Get all settings.
     */
    public function index()
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can access settings
        if (!in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json(TPanelSettings::all_settings());
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can update settings
        if (!in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $data = $request->all();

        // Get old settings for logging
        $oldSettings = TPanelSettings::all_settings();
        $changes = [];

        foreach ($data as $key => $value) {
            $type = $this->getType($key);
            $oldValue = $oldSettings[$key] ?? null;

            // Track changes
            if ($oldValue !== $value) {
                $changes[$key] = ['old' => $oldValue, 'new' => $value];
            }

            TPanelSettings::set($key, $value, $type);
        }

        // Log activity if there were changes
        if (!empty($changes)) {
            $changedKeys = implode(', ', array_keys($changes));
            TAdminAction::log('changed', 'setting', null, 'Изменены настройки: ' . $changedKeys, $changes);
        }

        return response()->json(['message' => 'Настройки сохранены']);
    }

    /**
     * Upload logo.
     */
    public function uploadLogo(Request $request)
    {
        $user = Auth::guard('admin')->user();

        if (!in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old logo if exists
        $oldLogo = TPanelSettings::get('logo_path');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');

        // Save to settings
        TPanelSettings::set('logo_path', $path, 'string');

        // Log activity
        TAdminAction::log('uploaded', 'logo', null, 'Загружен новый логотип');

        return response()->json([
            'message' => 'Логотип загружен',
            'path' => $path,
            'url' => Storage::disk('public')->url($path)
        ]);
    }

    /**
     * Delete logo.
     */
    public function deleteLogo()
    {
        $user = Auth::guard('admin')->user();

        if (!in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $oldLogo = TPanelSettings::get('logo_path');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        TPanelSettings::set('logo_path', '', 'string');

        // Log activity
        TAdminAction::log('deleted', 'logo', null, 'Удален логотип');

        return response()->json(['message' => 'Логотип удален']);
    }

    /**
     * Get type for setting key.
     */
    protected function getType(string $key): string
    {
        return match ($key) {
            'phones', 'emails', 'addresses', 'header_template_settings', 'footer_template_settings', 'social_links' => 'json',
            'logo_width', 'logo_height', 'header_menu_id', 'footer_menu_id' => 'integer',
            default => 'string',
        };
    }
}
