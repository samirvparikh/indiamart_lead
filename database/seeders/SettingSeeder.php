<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Company Profile
            ['group' => 'company', 'key' => 'name', 'value' => 'LeadCRM', 'type' => 'string'],
            ['group' => 'company', 'key' => 'email', 'value' => 'info@gmail.com', 'type' => 'string'],
            ['group' => 'company', 'key' => 'phone', 'value' => '', 'type' => 'string'],
            ['group' => 'company', 'key' => 'address', 'value' => '', 'type' => 'text'],
            ['group' => 'company', 'key' => 'gst_number', 'value' => '', 'type' => 'string'],
            ['group' => 'company', 'key' => 'logo', 'value' => '', 'type' => 'string'],

            // Email SMTP
            ['group' => 'smtp', 'key' => 'host', 'value' => '', 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'port', 'value' => '587', 'type' => 'integer'],
            ['group' => 'smtp', 'key' => 'username', 'value' => '', 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'password', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'smtp', 'key' => 'encryption', 'value' => 'tls', 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'from_address', 'value' => '', 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'from_name', 'value' => 'LeadCRM', 'type' => 'string'],

            // SMS Gateway
            ['group' => 'sms', 'key' => 'provider', 'value' => '', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'api_key', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'sms', 'key' => 'sender_id', 'value' => '', 'type' => 'string'],

            // WhatsApp API
            ['group' => 'whatsapp', 'key' => 'provider', 'value' => '', 'type' => 'string'],
            ['group' => 'whatsapp', 'key' => 'api_key', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'whatsapp', 'key' => 'phone_number_id', 'value' => '', 'type' => 'string'],

            // IndiaMART API
            ['group' => 'indiamart', 'key' => 'api_key', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'indiamart', 'key' => 'glusr_id', 'value' => '', 'type' => 'string'],
            ['group' => 'indiamart', 'key' => 'access_token', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'indiamart', 'key' => 'auto_sync', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'indiamart', 'key' => 'sync_interval', 'value' => '30', 'type' => 'integer'],
            ['group' => 'indiamart', 'key' => 'last_sync_at', 'value' => '', 'type' => 'datetime'],
            ['group' => 'indiamart', 'key' => 'sync_status', 'value' => 'idle', 'type' => 'string'],
            ['group' => 'indiamart', 'key' => 'retry_count', 'value' => '3', 'type' => 'integer'],

            // Lead Auto Assignment
            ['group' => 'assignment', 'key' => 'auto_assign', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'assignment', 'key' => 'method', 'value' => 'round_robin', 'type' => 'string'],

            // Working Hours
            ['group' => 'working_hours', 'key' => 'start_time', 'value' => '09:00', 'type' => 'string'],
            ['group' => 'working_hours', 'key' => 'end_time', 'value' => '18:00', 'type' => 'string'],
            ['group' => 'working_hours', 'key' => 'working_days', 'value' => '1,2,3,4,5,6', 'type' => 'string'],

            // General
            ['group' => 'general', 'key' => 'lead_number_prefix', 'value' => 'LD', 'type' => 'string'],
            ['group' => 'general', 'key' => 'quotation_number_prefix', 'value' => 'QT', 'type' => 'string'],
            ['group' => 'general', 'key' => 'dashboard_refresh_interval', 'value' => '30', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['group' => $setting['group'], 'key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_encrypted' => $setting['is_encrypted'] ?? false,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command?->info('Settings seeded: '.count($settings));
    }
}
