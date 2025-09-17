<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * AdminSettingsController manages global application settings and configuration.
 * Provides centralized control over system-wide preferences and branding.
 */
class AdminSettingsController extends Controller
{
    /**
     * Get all application settings grouped by category.
     * Returns organized settings for easy management.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse organized settings data
     */
    public function index(Request $request)
    {
        $settings = DB::table('settings')
            ->leftJoin('users', 'settings.updated_by', '=', 'users.id')
            ->select([
                'settings.key',
                'settings.value_json',
                'settings.updated_by',
                'users.name as updated_by_name',
                'settings.created_at',
                'settings.updated_at'
            ])
            ->orderBy('settings.key')
            ->get()
            ->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => json_decode($setting->value_json, true),
                    'updated_by' => [
                        'id' => $setting->updated_by,
                        'name' => $setting->updated_by_name,
                    ],
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            });
        
        // Group settings by category
        $groupedSettings = $this->groupSettingsByCategory($settings);
        
        return response()->json([
            'settings' => $groupedSettings,
            'total_count' => $settings->count(),
        ]);
    }
    
    /**
     * Get a specific setting by key.
     * Returns individual setting with metadata.
     *
     * @param Request $request HTTP request
     * @param string $key Setting key
     * @return \Illuminate\Http\JsonResponse setting data
     */
    public function show(Request $request, string $key)
    {
        $setting = DB::table('settings')
            ->leftJoin('users', 'settings.updated_by', '=', 'users.id')
            ->select([
                'settings.key',
                'settings.value_json',
                'settings.updated_by',
                'users.name as updated_by_name',
                'settings.created_at',
                'settings.updated_at'
            ])
            ->where('settings.key', $key)
            ->first();
        
        if (!$setting) {
            return response()->json([
                'error' => 'Setting not found',
                'key' => $key,
            ], 404);
        }
        
        return response()->json([
            'key' => $setting->key,
            'value' => json_decode($setting->value_json, true),
            'updated_by' => [
                'id' => $setting->updated_by,
                'name' => $setting->updated_by_name,
            ],
            'created_at' => $setting->created_at,
            'updated_at' => $setting->updated_at,
        ]);
    }
    
    /**
     * Update or create a setting.
     * Handles both individual and batch setting updates.
     *
     * @param Request $request HTTP request with setting data
     * @return \Illuminate\Http\JsonResponse updated settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'required',
        ]);
        
        $updatedSettings = [];
        $userId = auth()->id();
        
        DB::transaction(function () use ($request, $userId, &$updatedSettings) {
            foreach ($request->get('settings') as $settingData) {
                $key = $settingData['key'];
                $value = $settingData['value'];
                
                // Validate setting key format
                if (!$this->isValidSettingKey($key)) {
                    throw new \InvalidArgumentException("Invalid setting key format: {$key}");
                }
                
                // Validate setting value based on key
                $this->validateSettingValue($key, $value);
                
                $settingRecord = [
                    'key' => $key,
                    'value_json' => json_encode($value),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ];
                
                // Check if setting exists
                $existingSetting = DB::table('settings')->where('key', $key)->first();
                
                if ($existingSetting) {
                    // Update existing setting
                    DB::table('settings')
                        ->where('key', $key)
                        ->update($settingRecord);
                } else {
                    // Create new setting
                    $settingRecord['created_at'] = now();
                    DB::table('settings')->insert($settingRecord);
                }
                
                $updatedSettings[] = [
                    'key' => $key,
                    'value' => $value,
                    'action' => $existingSetting ? 'updated' : 'created',
                ];
                
                // Log the setting change in audit logs
                $this->logSettingChange($key, $value, $existingSetting ? 'updated' : 'created');
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'updated_settings' => $updatedSettings,
        ]);
    }
    
    /**
     * Delete a setting.
     * Removes setting from the system with audit logging.
     *
     * @param Request $request HTTP request
     * @param string $key Setting key to delete
     * @return \Illuminate\Http\JsonResponse deletion result
     */
    public function destroy(Request $request, string $key)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'error' => 'Setting not found',
                'key' => $key,
            ], 404);
        }
        
        // Prevent deletion of critical settings
        if ($this->isCriticalSetting($key)) {
            return response()->json([
                'error' => 'Cannot delete critical system setting',
                'key' => $key,
            ], 403);
        }
        
        DB::table('settings')->where('key', $key)->delete();
        
        // Log the setting deletion
        $this->logSettingChange($key, null, 'deleted');
        
        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully',
            'key' => $key,
        ]);
    }
    
    /**
     * Get default settings template.
     * Provides a template of all available settings with descriptions.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse settings template
     */
    public function template(Request $request)
    {
        $template = [
            'branding' => [
                'app.name' => [
                    'description' => 'Application name displayed throughout the platform',
                    'type' => 'string',
                    'default' => 'AlFawz Qur\'an Institute',
                    'required' => true,
                ],
                'app.logo_url' => [
                    'description' => 'URL to the application logo',
                    'type' => 'string',
                    'default' => null,
                    'required' => false,
                ],
                'app.primary_color' => [
                    'description' => 'Primary brand color (hex code)',
                    'type' => 'string',
                    'default' => '#10B981',
                    'required' => false,
                ],
                'app.secondary_color' => [
                    'description' => 'Secondary brand color (hex code)',
                    'type' => 'string',
                    'default' => '#6B7280',
                    'required' => false,
                ],
            ],
            'learning' => [
                'hasanat.multiplier' => [
                    'description' => 'Multiplier for hasanat calculation (per Arabic letter)',
                    'type' => 'number',
                    'default' => 10,
                    'required' => true,
                ],
                'assignments.max_attempts' => [
                    'description' => 'Maximum submission attempts per assignment',
                    'type' => 'number',
                    'default' => 3,
                    'required' => true,
                ],
                'feedback.auto_approve_threshold' => [
                    'description' => 'Score threshold for automatic approval',
                    'type' => 'number',
                    'default' => 85,
                    'required' => false,
                ],
            ],
            'notifications' => [
                'email.enabled' => [
                    'description' => 'Enable email notifications',
                    'type' => 'boolean',
                    'default' => true,
                    'required' => true,
                ],
                'email.from_address' => [
                    'description' => 'Default from email address',
                    'type' => 'string',
                    'default' => 'noreply@alfawz.com',
                    'required' => true,
                ],
                'push.enabled' => [
                    'description' => 'Enable push notifications',
                    'type' => 'boolean',
                    'default' => false,
                    'required' => false,
                ],
            ],
            'security' => [
                'session.timeout' => [
                    'description' => 'Session timeout in minutes',
                    'type' => 'number',
                    'default' => 120,
                    'required' => true,
                ],
                'password.min_length' => [
                    'description' => 'Minimum password length',
                    'type' => 'number',
                    'default' => 8,
                    'required' => true,
                ],
                'rate_limit.api_requests' => [
                    'description' => 'API rate limit per minute',
                    'type' => 'number',
                    'default' => 60,
                    'required' => true,
                ],
            ],
            'integrations' => [
                'paystack.enabled' => [
                    'description' => 'Enable Paystack payment integration',
                    'type' => 'boolean',
                    'default' => true,
                    'required' => false,
                ],
                'openai.enabled' => [
                    'description' => 'Enable OpenAI Whisper integration',
                    'type' => 'boolean',
                    'default' => true,
                    'required' => false,
                ],
                'quran_api.cache_duration' => [
                    'description' => 'Quran API cache duration in minutes',
                    'type' => 'number',
                    'default' => 60,
                    'required' => true,
                ],
            ],
        ];
        
        return response()->json([
            'template' => $template,
            'categories' => array_keys($template),
        ]);
    }
    
    /**
     * Group settings by category based on key prefix.
     * @param \Illuminate\Support\Collection $settings Settings collection
     * @return array grouped settings
     */
    private function groupSettingsByCategory($settings): array
    {
        $grouped = [];
        
        foreach ($settings as $setting) {
            $parts = explode('.', $setting['key']);
            $category = $parts[0] ?? 'general';
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][] = $setting;
        }
        
        return $grouped;
    }
    
    /**
     * Validate setting key format.
     * @param string $key Setting key
     * @return bool is valid
     */
    private function isValidSettingKey(string $key): bool
    {
        // Allow alphanumeric, dots, underscores, and hyphens
        return preg_match('/^[a-zA-Z0-9._-]+$/', $key) && strlen($key) <= 255;
    }
    
    /**
     * Validate setting value based on key.
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @throws \InvalidArgumentException if validation fails
     */
    private function validateSettingValue(string $key, $value): void
    {
        // Basic validation rules based on common setting patterns
        $validationRules = [
            'color' => function ($val) {
                return is_string($val) && preg_match('/^#[0-9A-Fa-f]{6}$/', $val);
            },
            'email' => function ($val) {
                return is_string($val) && filter_var($val, FILTER_VALIDATE_EMAIL);
            },
            'url' => function ($val) {
                return is_string($val) && filter_var($val, FILTER_VALIDATE_URL);
            },
            'number' => function ($val) {
                return is_numeric($val) && $val >= 0;
            },
            'boolean' => function ($val) {
                return is_bool($val);
            },
        ];
        
        // Apply validation based on key patterns
        if (str_contains($key, 'color')) {
            if (!$validationRules['color']($value)) {
                throw new \InvalidArgumentException("Invalid color format for {$key}");
            }
        } elseif (str_contains($key, 'email')) {
            if (!$validationRules['email']($value)) {
                throw new \InvalidArgumentException("Invalid email format for {$key}");
            }
        } elseif (str_contains($key, 'url')) {
            if ($value !== null && !$validationRules['url']($value)) {
                throw new \InvalidArgumentException("Invalid URL format for {$key}");
            }
        } elseif (str_contains($key, 'enabled') || str_contains($key, 'boolean')) {
            if (!$validationRules['boolean']($value)) {
                throw new \InvalidArgumentException("Invalid boolean value for {$key}");
            }
        } elseif (preg_match('/(timeout|limit|length|multiplier|threshold|duration)/', $key)) {
            if (!$validationRules['number']($value)) {
                throw new \InvalidArgumentException("Invalid number value for {$key}");
            }
        }
    }
    
    /**
     * Check if a setting is critical and should not be deleted.
     * @param string $key Setting key
     * @return bool is critical
     */
    private function isCriticalSetting(string $key): bool
    {
        $criticalSettings = [
            'app.name',
            'hasanat.multiplier',
            'session.timeout',
            'password.min_length',
        ];
        
        return in_array($key, $criticalSettings);
    }
    
    /**
     * Log setting changes to audit log.
     * @param string $key Setting key
     * @param mixed $value New value
     * @param string $action Action performed
     */
    private function logSettingChange(string $key, $value, string $action): void
    {
        DB::table('admin_audit_logs')->insert([
            'actor_id' => auth()->id(),
            'action' => "setting_{$action}",
            'entity_type' => 'setting',
            'entity_id' => $key,
            'meta_json' => json_encode([
                'key' => $key,
                'value' => $value,
                'action' => $action,
            ]),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}