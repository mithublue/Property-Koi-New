<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentProfile;
use App\Models\FcmDeviceToken;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\BuyerDemandMatchService;
use App\Services\ClarityService;
use App\Services\FacebookAutoPostService;
use App\Services\FcmService;
use App\Services\GoogleSheetSyncService;
use App\Services\MessageTemplateService;
use App\Services\SeoService;
use App\Services\TelegramService;
use App\Services\WatermarkService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $currentTab = $request->query('tab', 'general');

        $settings = Setting::pluck('value', 'key')->toArray();

        // 1. General Tab (Branding & Global Controls)
        $site_name = Setting::get('adm_site_name', config('app.name', 'PropertyKoi.com'));
        $site_tagline = Setting::get('adm_site_tagline', 'সরাসরি প্রপার্টি মার্কেটপ্লেস');
        $site_logo = Setting::get('adm_site_logo', '');
        $site_logo_dark = Setting::get('adm_site_logo_dark', '');
        $site_favicon = Setting::get('adm_site_favicon', '');
        $site_email = Setting::get('adm_site_email', 'support@propertykoi.com');
        $site_phone = Setting::get('adm_site_phone', '');
        $site_footer_about = Setting::get('adm_site_footer_about', 'PropertyKoi.com – Your Trusted Property Classifieds Website in Bangladesh. Discover the Best Opportunities to Buy, Sell, or Rent Real Estate, Apartments, and Commercial Properties with Ease and Confidence.');
        $site_copyright = Setting::get('adm_site_copyright', '© ' . date('Y') . ' ' . $site_name . '. সর্বস্বত্ব সংরক্ষিত।');

        $dev_mode = Setting::get('adm_developer_mode', '1') === '1';
        $admin_wa = Setting::get('adm_admin_whatsapp_number', Setting::get('admin_whatsapp_number', ''));
        $enable_wa_auth = Setting::get('adm_enable_whatsapp_auth', 'yes') === 'yes';
        $auto_approve_visits = Setting::get('adm_auto_approve_visits', 'no') === 'yes';
        $show_owner_identity = Setting::get('adm_show_owner_identity', 'no') === 'yes';

        // 2. Media Tab
        $watermark_enabled = Setting::get('adm_watermark_enabled', 'yes') === 'yes';
        $watermark_text = Setting::get('adm_watermark_text', 'PropertyKoi.com');
        $watermark_position = Setting::get('adm_watermark_position', 'bottom-right');
        $watermark_opacity = (int) Setting::get('adm_watermark_opacity', 82);

        $cld_name = Setting::get('adm_cloudinary_cloud_name', '');
        $cld_key = Setting::get('adm_cloudinary_api_key', '');
        $cld_secret = Setting::get('adm_cloudinary_api_secret', '');
        $cld_folder = Setting::get('adm_cloudinary_folder', 'aftabnagar_marketplace');
        $is_cld_set = !empty($cld_name) && !empty($cld_key) && !empty($cld_secret);

        // 3. Sheets Tab
        $google_sheet_url = Setting::get('adm_google_sheet_url', Setting::get('google_sheet_url', ''));
        $google_sheet_direct_live = Setting::get('adm_google_sheet_direct_live', 'no') === 'yes';
        $google_sheet_default_status = $google_sheet_direct_live ? 'live' : 'pending_verification';
        $google_sheet_last_time = Setting::get('adm_google_sheet_last_sync_time', '');
        $google_sheet_last_report = Setting::get('adm_google_sheet_last_sync_report', null);

        // 4. Notifications & Firebase / Telegram / Cron
        $fb_api_key = Setting::get('adm_firebase_api_key', '');
        $fb_auth_domain = Setting::get('adm_firebase_auth_domain', '');
        $fb_project_id = Setting::get('adm_firebase_project_id', '');
        $fb_app_id = Setting::get('adm_firebase_app_id', '');
        $fb_sender_id = Setting::get('adm_firebase_messaging_sender_id', '');
        $is_fb_set = !empty($fb_api_key) && !empty($fb_project_id);

        $fcm_enabled = Setting::get('adm_fcm_notifications_enabled', 'yes') === 'yes';
        $fcm_notify_property = Setting::get('adm_fcm_notify_property_submit', 'yes') === 'yes';
        $fcm_notify_visit = Setting::get('adm_fcm_notify_visit_booking', 'yes') === 'yes';
        $fcm_notify_offer = Setting::get('adm_fcm_notify_offer', 'yes') === 'yes';
        $fcm_notify_more_info = Setting::get('adm_fcm_notify_more_info', 'yes') === 'yes';
        $fcm_notify_whatsapp = Setting::get('adm_fcm_notify_whatsapp_click', 'yes') === 'yes';
        $fcm_notify_lead = Setting::get('adm_fcm_notify_lead_submit', 'yes') === 'yes';
        $fcm_notify_agent = Setting::get('adm_fcm_notify_agent_enabled', 'yes') === 'yes';
        $fcm_devices_count = FcmService::getDeviceCount();

        $tele_bot_token = Setting::get('adm_telegram_bot_token', '');
        $tele_bot_username = Setting::get('adm_telegram_bot_username', 'AftabnagarPropertyBot');
        $tele_admin_chat_id = Setting::get('adm_telegram_admin_chat_id', '');
        $tele_channel_url = Setting::get('adm_telegram_channel_url', '');
        $tele_channel_username = Setting::get('adm_telegram_channel_username', '');
        $tele_notify_demand = Setting::get('adm_telegram_notify_new_demand', 'yes') === 'yes';
        $tele_notify_property = Setting::get('adm_telegram_notify_new_property', 'yes') === 'yes';
        $is_tele_ready = TelegramService::isConfigured();

        $mail_mailer = Setting::get('adm_mail_mailer', env('MAIL_MAILER', 'log'));
        $mail_host = Setting::get('adm_mail_host', env('MAIL_HOST', ''));
        $mail_port = Setting::get('adm_mail_port', env('MAIL_PORT', '587'));
        $mail_username = Setting::get('adm_mail_username', env('MAIL_USERNAME', ''));
        $mail_password = Setting::get('adm_mail_password', env('MAIL_PASSWORD', ''));
        $mail_encryption = Setting::get('adm_mail_encryption', env('MAIL_ENCRYPTION', 'tls'));
        $mail_from_address = Setting::get('adm_mail_from_address', env('MAIL_FROM_ADDRESS', ''));
        $mail_from_name = Setting::get('adm_mail_from_name', env('MAIL_FROM_NAME', config('app.name', 'PropertyKoi')));

        $cron_secret_key = Setting::get('adm_cron_secret_key', 'cron_sec_' . substr(md5(config('app.key', 'secret')), 0, 10));

        // Microsoft Clarity Analytics
        $clarity_enabled = Setting::get('adm_clarity_enabled', 'yes') === 'yes';
        $clarity_project_id = Setting::get('adm_clarity_project_id', ClarityService::getProjectId() ?? '');
        $clarity_exclude_admin = Setting::get('adm_clarity_exclude_admin', 'yes') === 'yes';
        $clarity_track_roles = Setting::get('adm_clarity_track_roles', 'yes') === 'yes';
        $clarity_mask_inputs = Setting::get('adm_clarity_mask_inputs', 'yes') === 'yes';
        $is_clarity_ready = ClarityService::isConfigured();
        $clarity_dashboard_url = ClarityService::getDashboardUrl();
        $clarity_recordings_url = ClarityService::getRecordingsUrl();
        $clarity_heatmaps_url = ClarityService::getHeatmapsUrl();

        // 5. Access & Policies
        $available_roles = [
            'administrator' => '👑 Administrator (অ্যাডমিনিস্ট্রেটর)',
            'adm_agent'     => '💼 Direct Agent (ভেরিফাইড এজেন্ট)',
            'adm_owner'     => '🏠 Direct Owner (প্রপার্টি মালিক)',
            'adm_buyer'     => '🛒 Buyer / Customer (ক্রেতা / কাস্টমার)',
            'subscriber'    => '👤 Subscriber (সাবস্ক্রাইবার)',
            'editor'        => '✏️ Editor (এডিটর)',
            'author'        => '📝 Author (অথর)',
            'contributor'   => '📄 Contributor (কন্ট্রিবিউটর)',
            'default'       => '🌐 Default Fallback (অন্যান্য সকল রোল / ফলব্যাক)',
        ];

        $roles_summary = [];
        foreach ($available_roles as $rKey => $rName) {
            $count = 0;
            if ($rKey !== 'default') {
                try {
                    $count = User::where('role', $rKey)->count();
                } catch (\Throwable $e) {
                    $count = 0;
                }
            }
            $roles_summary[$rKey] = [
                'key'   => $rKey,
                'name'  => $rName,
                'count' => $count,
            ];
        }

        $access_admin_bar = Setting::get('adm_role_admin_bar', [
            'administrator' => 1,
            'adm_agent'     => 0,
            'adm_owner'     => 0,
            'adm_buyer'     => 0,
            'subscriber'    => 0,
            'editor'        => 1,
            'author'        => 0,
            'contributor'   => 0,
            'default'       => 0,
        ]);
        if (is_string($access_admin_bar)) {
            $access_admin_bar = json_decode($access_admin_bar, true) ?: [];
        }

        $access_admin_panel = Setting::get('adm_role_admin_panel', [
            'administrator' => 1,
            'adm_agent'     => 0,
            'adm_owner'     => 0,
            'adm_buyer'     => 0,
            'subscriber'    => 0,
            'editor'        => 1,
            'author'        => 0,
            'contributor'   => 0,
            'default'       => 0,
        ]);
        if (is_string($access_admin_panel)) {
            $access_admin_panel = json_decode($access_admin_panel, true) ?: [];
        }

        $access_redirects = Setting::get('adm_role_admin_redirects', [
            'administrator' => 'auto',
            'adm_agent'     => 'auto',
            'adm_owner'     => 'auto',
            'adm_buyer'     => 'auto',
            'subscriber'    => 'auto',
            'editor'        => 'auto',
            'author'        => 'auto',
            'contributor'   => 'auto',
            'default'       => 'auto',
        ]);
        if (is_string($access_redirects)) {
            $access_redirects = json_decode($access_redirects, true) ?: [];
        }

        $agent_policy_notice = Setting::get('adm_agent_policy_notice', '');
        $safety_tips = Setting::get('adm_safety_tips', '');

        $pages_list = [
            ['id' => '1', 'title' => 'হোমপেজ', 'slug' => 'home'],
            ['id' => '2', 'title' => 'এজেন্ট পোর্টাল', 'slug' => 'agent-portal'],
            ['id' => '3', 'title' => 'বায়ার ড্যাশবোর্ড', 'slug' => 'buyer-dashboard'],
            ['id' => '4', 'title' => 'প্রপার্টি বিক্রয় পেজ', 'slug' => 'sell-property'],
            ['id' => '5', 'title' => 'চাহিদা সাবমিট পেজ', 'slug' => 'chahida'],
            ['id' => '6', 'title' => 'জমি ও প্লট সার্চ', 'slug' => 'land-search'],
            ['id' => '7', 'title' => 'রেডি ফ্ল্যাট সার্চ', 'slug' => 'flat-search'],
            ['id' => '8', 'title' => 'বাণিজ্যিক স্পেস', 'slug' => 'commercial-search'],
            ['id' => '9', 'title' => 'লগইন ও সাইনআপ', 'slug' => 'login-register'],
        ];

        $saved_rules = Setting::get('adm_role_redirect_rules', '');
        if (is_string($saved_rules) && !empty($saved_rules)) {
            $saved_rules = json_decode($saved_rules, true);
        }
        if (empty($saved_rules) || !is_array($saved_rules)) {
            $saved_rules = [
                ['role' => 'administrator', 'type' => 'custom_url', 'page_id' => '', 'custom_url' => url('/admin')],
                ['role' => 'adm_agent',     'type' => 'page',       'page_id' => '2', 'custom_url' => ''],
                ['role' => 'adm_owner',     'type' => 'page',       'page_id' => '2', 'custom_url' => ''],
                ['role' => 'adm_buyer',     'type' => 'page',       'page_id' => '3', 'custom_url' => ''],
                ['role' => 'subscriber',    'type' => 'page',       'page_id' => '3', 'custom_url' => ''],
                ['role' => 'default',       'type' => 'page',       'page_id' => '3', 'custom_url' => ''],
            ];
        }

        // 6. Messages Tab
        $template_definitions = MessageTemplateService::getDefinitions();
        $saved_templates = [];
        foreach ($template_definitions as $k => $def) {
            $saved_templates[$k] = MessageTemplateService::getTemplate($k);
        }

        // 7. SEO Tab
        $seo_default_og_image = Setting::get('adm_seo_default_og_image', '');
        $seo_chahida_og_image = Setting::get('adm_seo_chahida_og_image', '');
        $seo_sell_og_image = Setting::get('adm_seo_sell_og_image', '');
        $seo_land_og_image = Setting::get('adm_seo_land_og_image', '');
        $seo_flat_og_image = Setting::get('adm_seo_flat_og_image', '');
        $seo_commercial_og_image = Setting::get('adm_seo_commercial_og_image', '');
        $indexnow_key = SeoService::getIndexNowKey();

        // 8. Facebook Tab
        $fb_autopost_enabled = Setting::get('adm_fb_autopost_enabled', 'no') === 'yes';
        $fb_page_id = Setting::get('adm_fb_page_id', Setting::get('fb_page_id', ''));
        $fb_page_token = Setting::get('adm_fb_page_access_token', Setting::get('fb_access_token', ''));
        $fb_caption_tpl = Setting::get('adm_fb_caption_template', '');
        $is_fb_post_ready = FacebookAutoPostService::isConfigured();

        // 9. Penalty Tab
        $penalized_agents = AgentProfile::with('user')
            ->where(function($q) {
                $q->where('penalty_status', '!=', 'verified')
                  ->orWhereNotNull('admin_fixed_rating')
                  ->orWhereNotNull('penalty_reason');
            })
            ->orderByDesc('updated_at')
            ->get();

        $all_agents_list = User::whereIn('role', ['agent', 'owner', 'adm_agent', 'adm_owner'])
            ->orWhereHas('agentProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'role']);

        // 10. WP Live Import
        $wp_live_url = Setting::get('adm_wp_live_url', 'https://propertykoi.com');
        $wp_live_username = Setting::get('adm_wp_live_username', '');
        $wp_live_app_password = Setting::get('adm_wp_live_app_password', '');

        return view('admin.settings', compact(
            'wp_live_url',
            'wp_live_username',
            'wp_live_app_password',
            'settings',
            'currentTab',
            'dev_mode',
            'admin_wa',
            'enable_wa_auth',
            'auto_approve_visits',
            'show_owner_identity',
            'watermark_enabled',
            'watermark_text',
            'watermark_position',
            'watermark_opacity',
            'cld_name',
            'cld_key',
            'cld_secret',
            'cld_folder',
            'is_cld_set',
            'google_sheet_url',
            'google_sheet_direct_live',
            'google_sheet_default_status',
            'google_sheet_last_time',
            'google_sheet_last_report',
            'fb_api_key',
            'fb_auth_domain',
            'fb_project_id',
            'fb_app_id',
            'fb_sender_id',
            'is_fb_set',
            'fcm_enabled',
            'fcm_notify_property',
            'fcm_notify_visit',
            'fcm_notify_offer',
            'fcm_notify_more_info',
            'fcm_notify_whatsapp',
            'fcm_notify_lead',
            'fcm_notify_agent',
            'fcm_devices_count',
            'tele_bot_token',
            'tele_bot_username',
            'tele_admin_chat_id',
            'tele_channel_url',
            'tele_channel_username',
            'tele_notify_demand',
            'tele_notify_property',
            'is_tele_ready',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'cron_secret_key',
            'available_roles',
            'roles_summary',
            'access_admin_bar',
            'access_admin_panel',
            'access_redirects',
            'agent_policy_notice',
            'safety_tips',
            'pages_list',
            'saved_rules',
            'template_definitions',
            'saved_templates',
            'seo_default_og_image',
            'seo_chahida_og_image',
            'seo_sell_og_image',
            'seo_land_og_image',
            'seo_flat_og_image',
            'seo_commercial_og_image',
            'indexnow_key',
            'fb_autopost_enabled',
            'fb_page_id',
            'fb_page_token',
            'fb_caption_tpl',
            'is_fb_post_ready',
            'penalized_agents',
            'all_agents_list',
            'clarity_enabled',
            'clarity_project_id',
            'clarity_exclude_admin',
            'clarity_track_roles',
            'clarity_mask_inputs',
            'is_clarity_ready',
            'clarity_dashboard_url',
            'clarity_recordings_url',
            'clarity_heatmaps_url',
            'site_name',
            'site_tagline',
            'site_logo',
            'site_logo_dark',
            'site_favicon',
            'site_email',
            'site_phone',
            'site_footer_about',
            'site_copyright'
        ));
    }

    public function update(Request $request)
    {
        $tab = $request->input('tab', 'general');
        $inputs = $request->except(['_token', 'tab', 'adm_action']);

        // Branding File Uploads & Reset Handling
        $brandingDir = public_path('uploads/branding');
        if (!file_exists($brandingDir)) {
            @mkdir($brandingDir, 0755, true);
        }

        // 1. Primary Site Logo Upload
        if ($request->hasFile('adm_site_logo_file')) {
            $file = $request->file('adm_site_logo_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'logo_' . time() . '.' . $ext;
            $file->move($brandingDir, $filename);
            $inputs['adm_site_logo'] = 'uploads/branding/' . $filename;
        } elseif ($request->input('adm_remove_site_logo') === '1') {
            $inputs['adm_site_logo'] = '';
        }

        // 2. Dark / Footer Logo Upload
        if ($request->hasFile('adm_site_logo_dark_file')) {
            $file = $request->file('adm_site_logo_dark_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'logo_dark_' . time() . '.' . $ext;
            $file->move($brandingDir, $filename);
            $inputs['adm_site_logo_dark'] = 'uploads/branding/' . $filename;
        } elseif ($request->input('adm_remove_site_logo_dark') === '1') {
            $inputs['adm_site_logo_dark'] = '';
        }

        // 3. Browser Favicon Upload
        if ($request->hasFile('adm_site_favicon_file')) {
            $file = $request->file('adm_site_favicon_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'favicon_' . time() . '.' . $ext;
            $file->move($brandingDir, $filename);
            $inputs['adm_site_favicon'] = 'uploads/branding/' . $filename;
        } elseif ($request->input('adm_remove_site_favicon') === '1') {
            $inputs['adm_site_favicon'] = '';
        }

        unset($inputs['adm_remove_site_logo'], $inputs['adm_remove_site_logo_dark'], $inputs['adm_remove_site_favicon']);

        // Checkbox definition mapping by tab to ensure unchecked checkboxes persist as 'no' or '0'
        $checkboxesByTab = [
            'general' => [
                'adm_developer_mode'       => '0',
                'adm_enable_whatsapp_auth' => 'no',
                'adm_auto_approve_visits'  => 'no',
                'adm_show_owner_identity'  => 'no',
            ],
            'media' => [
                'adm_watermark_enabled' => 'no',
            ],
            'sheets' => [
                'adm_google_sheet_direct_live' => 'no',
            ],
            'notifications' => [
                'adm_fcm_notifications_enabled'   => 'no',
                'adm_fcm_notify_property_submit'  => 'no',
                'adm_fcm_notify_visit_booking'    => 'no',
                'adm_fcm_notify_offer'            => 'no',
                'adm_fcm_notify_more_info'        => 'no',
                'adm_fcm_notify_whatsapp_click'   => 'no',
                'adm_fcm_notify_lead_submit'      => 'no',
                'adm_fcm_notify_agent_enabled'    => 'no',
                'adm_telegram_notify_new_demand'  => 'no',
                'adm_telegram_notify_new_property'=> 'no',
                'adm_clarity_enabled'             => 'no',
                'adm_clarity_exclude_admin'       => 'no',
                'adm_clarity_track_roles'         => 'no',
                'adm_clarity_mask_inputs'         => 'no',
            ],
            'facebook' => [
                'adm_fb_autopost_enabled' => 'no',
            ],
        ];

        if (isset($checkboxesByTab[$tab])) {
            foreach ($checkboxesByTab[$tab] as $checkboxKey => $defaultOffValue) {
                if (!$request->has($checkboxKey)) {
                    $inputs[$checkboxKey] = $defaultOffValue;
                }
            }
        }

        // Keep aliases synchronized
        if (isset($inputs['adm_google_sheet_url'])) {
            $inputs['google_sheet_url'] = $inputs['adm_google_sheet_url'];
        }
        if (isset($inputs['adm_admin_whatsapp_number'])) {
            $inputs['admin_whatsapp_number'] = $inputs['adm_admin_whatsapp_number'];
        }
        if (isset($inputs['adm_fb_page_id'])) {
            $inputs['fb_page_id'] = $inputs['adm_fb_page_id'];
        }
        if (isset($inputs['adm_fb_page_access_token'])) {
            $inputs['fb_access_token'] = $inputs['adm_fb_page_access_token'];
        }
        if (isset($inputs['adm_fb_autopost_enabled'])) {
            $inputs['fb_autopost_enabled'] = $inputs['adm_fb_autopost_enabled'];
        }

        foreach ($inputs as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'প্লাগইন সেটিংস সফলভাবে সেভ করা হয়েছে!');
    }

    public function testWatermark(Request $request)
    {
        try {
            $testDir = public_path('uploads/adm-test');
            if (!file_exists($testDir)) {
                @mkdir($testDir, 0755, true);
            }
            $testFile = $testDir . '/watermark-preview.jpg';

            $w = 900;
            $h = 600;
            $im = imagecreatetruecolor($w, $h);

            $sky = imagecolorallocate($im, 70, 130, 180);
            $ground = imagecolorallocate($im, 46, 125, 50);
            imagefilledrectangle($im, 0, 0, $w, 400, $sky);
            imagefilledrectangle($im, 0, 400, $w, $h, $ground);

            $building = imagecolorallocate($im, 248, 250, 252);
            imagefilledrectangle($im, 200, 140, 700, 520, $building);

            $win = imagecolorallocate($im, 96, 165, 250);
            for ($row = 0; $row < 4; $row++) {
                for ($col = 0; $col < 5; $col++) {
                    imagefilledrectangle($im, 240 + ($col * 90), 170 + ($row * 80), 300 + ($col * 90), 225 + ($row * 80), $win);
                }
            }

            imagejpeg($im, $testFile, 92);
            imagedestroy($im);

            $success = WatermarkService::applyWatermark($testFile);
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'data'    => ['message' => 'ওয়াটারমার্ক জেনারেট করা যায়নি। PHP GD লাইব্রেরি চেক করুন।']
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'   => 'ওয়াটারমার্ক টেস্ট সফল হয়েছে! নিচে প্রিভিউ দেখুন।',
                    'image_url' => asset('uploads/adm-test/watermark-preview.jpg') . '?v=' . time(),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'ত্রুটি: ' . $e->getMessage()]
            ]);
        }
    }

    public function testCloudinary(Request $request)
    {
        $cloudName = Setting::get('adm_cloudinary_cloud_name');
        $apiKey = Setting::get('adm_cloudinary_api_key');
        $apiSecret = Setting::get('adm_cloudinary_api_secret');

        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'ক্লাউডিনারি ক্রেডেনশিয়ালস সেট করা নেই। আগে সেভ করুন।']
            ]);
        }

        try {
            $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/ping");
            curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:{$apiSecret}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return response()->json([
                    'success' => true,
                    'data'    => ['message' => 'ক্লাউডিনারি ক্লাউড স্টোরেজ সফলভাবে সংযুক্ত হয়েছে!']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'data'    => ['message' => "ক্লাউডিনারি কানেকশন ব্যর্থ হয়েছে (HTTP {$httpCode})।"]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'ত্রুটি: ' . $e->getMessage()]
            ]);
        }
    }

    public function previewSheet(Request $request)
    {
        $sheetUrl = trim($request->input('sheet_url', Setting::get('adm_google_sheet_url', Setting::get('google_sheet_url', ''))));

        if (empty($sheetUrl)) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'অনুগ্রহ করে আগে Google Sheet এর লিংক প্রদান করুন।']
            ]);
        }

        $res = GoogleSheetSyncService::preview($sheetUrl);

        if ($res['success']) {
            return response()->json([
                'success' => true,
                'data'    => $res
            ]);
        }

        return response()->json([
            'success' => false,
            'data'    => ['message' => $res['error'] ?? 'প্রিভিউ লোড করা যায়নি।']
        ]);
    }

    public function syncSheetNow(Request $request)
    {
        $sheetUrl = trim($request->input('sheet_url', Setting::get('adm_google_sheet_url', Setting::get('google_sheet_url', ''))));
        $defaultStatus = $request->input('default_status', 'pending_verification');

        if (empty($sheetUrl)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'data'    => ['message' => 'অনুগ্রহ করে আগে Google Sheet এর লিংক প্রদান করুন।']
                ]);
            }
            return back()->with('error', 'Google Sheet URL কনফিগার করা নেই। অনুগ্রহ করে URL প্রদান করুন।');
        }

        Setting::set('adm_google_sheet_url', $sheetUrl);
        Setting::set('google_sheet_url', $sheetUrl);

        $options = ['status' => $defaultStatus];
        $result = GoogleSheetSyncService::sync($sheetUrl, $options);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'data'    => [
                    'message' => $result['message'] ?? ($result['error'] ?? 'সিঙ্ক সম্পন্ন হয়েছে'),
                    'report'  => $result['report'] ?? null,
                ]
            ]);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', "সিঙ্ক ব্যর্থ হয়েছে: " . ($result['error'] ?? 'অজানা ত্রুটি'));
    }

    public function linkParentlessProperties(Request $request)
    {
        $whatsappNumber = trim($request->input('whatsapp_number', ''));

        if (empty($whatsappNumber)) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'অনুগ্রহ করে WhatsApp নম্বর প্রদান করুন।']
            ]);
        }

        $result = GoogleSheetSyncService::linkParentlessProperties($whatsappNumber);

        return response()->json([
            'success' => $result['success'],
            'data'    => $result
        ]);
    }

    public function testPushNotification(Request $request)
    {
        $res = FcmService::sendToAdmins(
            '🔔 টেস্ট পুশ নোটিফিকেশন!',
            'Property Koi এর পুশ নোটিফিকেশন সফলভাবে কানেক্টেড এবং সক্রিয় রয়েছে।',
            route('admin.settings.index')
        );

        if ($res['success'] > 0) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'message' => "টেস্ট নোটিফিকেশন সফলভাবে {$res['success']}টি ডিভাইসে পাঠানো হয়েছে!",
                    'stats'   => $res,
                ]
            ]);
        } elseif (($res['total'] ?? 0) === 0) {
            return response()->json([
                'success' => false,
                'data'    => [
                    'message' => 'কোনো ডিভাইস রেজিস্টার্ড নেই! ব্রাউজারে নোটিফিকেশন পারমিশন Allow দিন।'
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'data'    => [
                    'message' => 'নোটিফিকেশন পাঠানো যায়নি। ফায়ারবেস কনফিগারেশন চেক করুন।',
                    'stats'   => $res,
                ]
            ]);
        }
    }

    public function testTelegram(Request $request)
    {
        $botToken = trim($request->input('bot_token', Setting::get('adm_telegram_bot_token', '')));
        $botUsername = trim($request->input('bot_username', Setting::get('adm_telegram_bot_username', '')));
        $adminChatId = trim($request->input('admin_chat_id', Setting::get('adm_telegram_admin_chat_id', '')));

        if (!empty($botToken)) {
            Setting::set('adm_telegram_bot_token', $botToken);
        }
        if (!empty($botUsername)) {
            Setting::set('adm_telegram_bot_username', $botUsername);
        }
        if (!empty($adminChatId)) {
            Setting::set('adm_telegram_admin_chat_id', $adminChatId);
        }

        $res = TelegramService::testConnection($botToken, $adminChatId);

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return back()->with('success', $res['message'] ?? 'টেলিগ্রাম টেস্ট মেসেজ সফলভাবে পাঠানো হয়েছে!');
        }

        return back()->with('error', 'টেলিগ্রাম মেসেজ পাঠানো যায়নি: ' . ($res['error'] ?? 'টোকেন অথবা চ্যাট আইডি সঠিক নয়'));
    }

    public function testEmail(Request $request)
    {
        $toEmail = trim($request->input('test_email', auth()->user()?->email ?: 'admin@propertykoi.com'));
        $siteName = config('app.name', 'PropertyKoi');

        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'সঠিক ইমেইল ঠিকানা প্রদান করুন।'
            ]);
        }

        // Apply posted SMTP parameters dynamically for test
        if ($request->filled('smtp_host')) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => trim($request->input('smtp_host')),
                'mail.mailers.smtp.port' => (int) $request->input('smtp_port', 587),
                'mail.mailers.smtp.encryption' => ($request->input('smtp_encryption') === 'none' || empty($request->input('smtp_encryption'))) ? null : $request->input('smtp_encryption', 'tls'),
                'mail.mailers.smtp.username' => trim($request->input('smtp_username')),
                'mail.mailers.smtp.password' => trim($request->input('smtp_password')),
                'mail.from.address' => trim($request->input('smtp_from_address', 'noreply@propertykoi.com')),
                'mail.from.name' => trim($request->input('smtp_from_name', $siteName)),
            ]);
        }

        try {
            \Illuminate\Support\Facades\Mail::html(
                "<div style='font-family: sans-serif; padding: 24px; line-height: 1.6; color: #1e293b; max-width: 500px; margin: auto; border: 1px solid #e2e8f0; border-radius: 16px;'>" .
                "<h2 style='color: #059669; margin: 0 0 12px;'>🎉 PropertyKoi ইমেইল টেস্ট সফল হয়েছে!</h2>" .
                "<p>অভিনন্দন! আপনার SMTP সার্ভার কনফিগারেশন সফলভাবে কাজ করছে।</p>" .
                "<p><strong>টাইমস্ট্যাম্প:</strong> " . now()->format('d M, Y h:i A') . "</p>" .
                "<p><strong>ড্রাইভার:</strong> " . config('mail.default') . " (" . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port') . ")</p>" .
                "<hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>" .
                "<p style='font-size: 11px; color: #64748b; margin: 0;'>এই টেস্ট ইমেইলটি PropertyKoi এডমিন প্যানেল থেকে পাঠানো হয়েছে।</p>" .
                "</div>",
                function ($msg) use ($toEmail, $siteName) {
                    $msg->to($toEmail)->subject("[{$siteName}] ইমেইল কনফিগারেশন টেস্ট সফল!");
                }
            );

            return response()->json([
                'success' => true,
                'message' => "টেস্ট ইমেইল সফলভাবে '{$toEmail}' ঠিকানায় পাঠানো হয়েছে! আপনার ইনবক্স (বা স্প্যাম ফোল্ডার) চেক করুন।"
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[SettingsController] Test email failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'ইমেইল পাঠানো যায়নি: ' . $e->getMessage()
            ]);
        }
    }

    public function triggerDemandCron(Request $request)
    {
        $result = BuyerDemandMatchService::runDailyDemandMatching(true);

        return response()->json([
            'success' => true,
            'data'    => [
                'message' => sprintf(
                    'ডিমান্ড ম্যাচিং সম্পন্ন হয়েছে! চেক করা হয়েছে: %d টি চাহিদা, নতুন ম্যাচ: %d টি, নোটিফিকেশন পাঠানো হয়েছে: %d টি।',
                    $result['total_requirements'] ?? 0,
                    $result['matched_demands'] ?? 0,
                    ($result['push_sent'] ?? 0) + ($result['email_sent'] ?? 0) + ($result['phone_logs'] ?? 0)
                ),
                'report' => $result,
            ]
        ]);
    }

    public function pingIndexNow(Request $request)
    {
        $res = SeoService::pingAllIndexNow();

        return response()->json([
            'success' => $res['success'],
            'data'    => $res,
        ]);
    }

    public function testFacebook(Request $request)
    {
        $res = FacebookAutoPostService::sendTestPost();

        if ($request->ajax() || $request->wantsJson()) {
            if (!empty($res['success'])) {
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'message' => '🎉 অভিনন্দন! Facebook পেজে টেস্ট পোস্ট সফল হয়েছে! Post ID: ' . ($res['post_id'] ?? ''),
                        'post_id' => $res['post_id'] ?? '',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'data'    => [
                    'message' => 'Facebook API ত্রুটি: ' . ($res['error'] ?? 'অজানা ত্রুটি'),
                ]
            ]);
        }

        if (!empty($res['success'])) {
            return back()->with('success', "ফেসবুক পেজে সফলভাবে টেস্ট পোস্ট পাবলিশ হয়েছে! (Post ID: {$res['post_id']})");
        }

        return back()->with('error', 'ফেসবুক টেস্ট পোস্ট ব্যর্থ: ' . ($res['error'] ?? 'টোকেন অথবা পেজ আইডি সঠিক নয়'));
    }

    public function updateAgentPenalty(Request $request)
    {
        $userId = (int) $request->input('user_id', 1);
        $penaltyStatus = $request->input('penalty_status', 'verified');
        $fixedRating = $request->input('admin_fixed_rating');
        $penaltyReason = $request->input('penalty_reason', '');

        AgentProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'penalty_status'     => $penaltyStatus,
                'admin_fixed_rating' => ($fixedRating !== null && $fixedRating !== '') ? (float) $fixedRating : null,
                'penalty_reason'     => $penaltyReason,
            ]
        );

        return redirect()->route('admin.settings.index', ['tab' => 'penalty'])
            ->with('success', '✅ এজেন্টের রেটিং ও পেনাল্টি স্ট্যাটাস সফলভাবে সেভ করা হয়েছে!');
    }

    /**
     * Dedicated Microsoft Clarity Admin View (1:1 with WordPress Microsoft Clarity plugin)
     */
    public function clarityDashboard()
    {
        $projectId = ClarityService::getProjectId();
        $isEnabled = ClarityService::isEnabled();
        $isConfigured = ClarityService::isConfigured();
        $dashboardUrl = ClarityService::getDashboardUrl();
        $recordingsUrl = ClarityService::getRecordingsUrl();
        $heatmapsUrl = ClarityService::getHeatmapsUrl();
        $settingsUrl = ClarityService::getSettingsUrl();
        $embedUrl = ClarityService::getEmbedUrl();
        $excludeAdmin = ClarityService::shouldExcludeAdmin();
        $trackRoles = ClarityService::shouldTrackRoles();
        $maskInputs = ClarityService::shouldMaskInputs();

        return view('admin.clarity', compact(
            'projectId',
            'isEnabled',
            'isConfigured',
            'dashboardUrl',
            'recordingsUrl',
            'heatmapsUrl',
            'settingsUrl',
            'embedUrl',
            'excludeAdmin',
            'trackRoles',
            'maskInputs'
        ));
    }

    public function saveClarityProject(Request $request)
    {
        $projectId = trim((string) $request->input('project_id', ''));
        $enabled = $request->has('enabled') ? ($request->boolean('enabled') ? '1' : '0') : '1';

        Setting::set('adm_clarity_project_id', $projectId);
        Setting::set('clarity_project_id', $projectId);
        Setting::set('adm_clarity_enabled', $enabled);

        if ($request->has('exclude_admin')) {
            Setting::set('adm_clarity_exclude_admin', $request->boolean('exclude_admin') ? '1' : '0');
        }
        if ($request->has('track_roles')) {
            Setting::set('adm_clarity_track_roles', $request->boolean('track_roles') ? '1' : '0');
        }
        if ($request->has('mask_inputs')) {
            Setting::set('adm_clarity_mask_inputs', $request->boolean('mask_inputs') ? '1' : '0');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'    => true,
                'project_id' => $projectId,
                'message'    => !empty($projectId)
                    ? "Microsoft Clarity Project ID ({$projectId}) সফলভাবে সংযুক্ত হয়েছে!"
                    : 'Microsoft Clarity Project ID বিচ্ছিন্ন করা হয়েছে।',
            ]);
        }

        return redirect()->route('admin.clarity')
            ->with('success', !empty($projectId)
                ? "✅ Microsoft Clarity Project ID ({$projectId}) সফলভাবে সংযুক্ত হয়েছে!"
                : 'Microsoft Clarity Project ID ক্লিয়ার করা হয়েছে।');
    }
}
