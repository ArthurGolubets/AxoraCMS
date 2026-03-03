<?php

namespace HolartWeb\HolartCMS\Services;

use Illuminate\Support\Facades\Http;

class LicenseService
{
    protected string $licenseServer = 'https://license.holart-dev.ru/api/check-licence-admin';

    /**
     * Check license key validity.
     */
    public function checkLicense(string $key, string $action = 'install'): bool
    {
        try {
            $response = Http::timeout(10)->post($this->licenseServer, [
                'key' => $key,
                'action' => $action,
            ]);

            if (!$response->successful()) {      
                return false;
            }

            $data = $response->json();

            return isset($data['status']) && $data['status'] === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save license key to file.
     */
    public function saveLicense(string $key): void
    {
        $licensePath = storage_path('holart-cms-license.key');
        file_put_contents($licensePath, $key);
    }

    /**
     * Get saved license key.
     */
    public function getSavedLicense(): ?string
    {
        $licensePath = storage_path('holart-cms-license.key');

        if (!file_exists($licensePath)) {
            return null;
        }

        return file_get_contents($licensePath);
    }

    /**
     * Check if license exists and is valid.
     */
    public function hasValidLicense(string $action = 'install'): bool
    {
        $key = $this->getSavedLicense();

        if (!$key) {
            return false;
        }

        return $this->checkLicense($key, $action);
    }
}
