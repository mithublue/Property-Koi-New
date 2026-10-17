<?php

namespace App\Services;

use App\Models\Setting;

class MessageTemplateService
{
    const OPTION_PREFIX = 'adm_msg_template_';

    /**
     * Get all template definitions, default contents, and supported placeholder tags.
     */
    public static function getDefinitions(): array
    {
        $siteName = config('app.name', 'PropertyKoi.com');

        return [
            'magic_login_profile' => [
                'key'         => 'magic_login_profile',
                'title'       => '🔑 এজেন্ট অটো-লগইন / ম্যাজিক লিঙ্ক (WhatsApp)',
                'category'    => 'লগইন ও প্রোফাইল',
                'description' => 'ইউজার প্রোফাইল বা ইউজার তালিকা থেকে এজেন্ট/ওনারকে হোয়াটসঅ্যাপে ম্যাজিক লগইন লিঙ্ক পাঠানোর বার্তা।',
                'default'     => "আসসালামু আলাইকুম %name%,\n{$siteName}-এ আপনার অ্যাকাউন্টে পাসওয়ার্ড ছাড়াই সরাসরি লগইন করার সিকিউর লিঙ্ক:\n%login_magic_link%\n\nধন্যবাদ।",
                'variables'   => [
                    '%name%'             => ['label' => 'ইউজারের পুরো নাম', 'sample' => 'মোঃ রফিকুল ইসলাম'],
                    '%login_magic_link%' => ['label' => 'ওয়ান-ক্লিক অটো লগইন URL', 'sample' => url('/agent-portal?adm_token=3YVT9AJMQ7VTOjv7SvGhALIYOC7xrvNB')],
                    '%site_name%'        => ['label' => 'সাইট বা প্ল্যাটফর্মের নাম', 'sample' => $siteName],
                    '%phone%'            => ['label' => 'মোবাইল / হোয়াটসঅ্যাপ নম্বর', 'sample' => '01712345678'],
                    '%role%'             => ['label' => 'ইউজারের রোল বা পদবি', 'sample' => 'Direct Agent'],
                    '%portal_url%'       => ['label' => 'এজেন্ট পোর্টাল লিঙ্ক', 'sample' => url('/agent-portal')],
                ],
            ],

            'property_listing_notification' => [
                'key'         => 'property_listing_notification',
                'title'       => '📢 প্রপার্টি লিস্টিং ও ইনসার্টার নোটিফিকেশন (Listing Confirmation)',
                'category'    => 'প্রপার্টি লিস্টিং',
                'description' => 'প্রপার্টি টেবিলে হোয়াটসঅ্যাপ নোটিফিকেশন বাটনে ক্লিক করলে বা অটো-অ্যাসাইনের পর এজেন্ট/ওনারকে প্রপার্টি নিশ্চিতকরণ বার্তা।',
                'default'     => "হ্যালো %name%,\n\nআমরা {$siteName} থেকে বলছি।\n\nআপনার প্রপার্টি '%property_title%' (কোড: %property_code%%location%%price%) আমাদের প্ল্যাটফর্মে %source_tag% তালিকাভুক্ত করা হয়েছে।\n\nআমাদের নিয়মিত ক্লায়েন্ট/বায়ারদের থেকে কোনো রিক্যোয়ারমেন্ট বা লিড পেলে আমরা সরাসরি আপনার সাথে যোগাযোগ করাবো।\n\n🔗 প্রপার্টি লিস্টিং দেখুন: %property_url%\n🔑 আপনার এজেন্ট পোর্টালে সরাসরি লগইন লিঙ্ক: %login_magic_link%\n\nবিস্তারিত জানতে বা কোনো তথ্য সংশোধন/আপডেট করতে এই নম্বরে আমাদের রিপ্লাই দিন।\n\nধন্যবাদ,\n{$siteName} টিম",
                'variables'   => [
                    '%name%'             => ['label' => 'কন্টাক্ট / এজেন্টের নাম', 'sample' => 'ইঞ্জিনিয়ার শাহরিয়ার হোসেন'],
                    '%property_code%'    => ['label' => 'প্রপার্টি কোড', 'sample' => 'AF-LAND-84920'],
                    '%property_title%'   => ['label' => 'প্রপার্টি শিরোনাম', 'sample' => 'আফতাবনগর এল ব্লক ৫ কাঠা দক্ষিণমুখী প্লট'],
                    '%property_url%'     => ['label' => 'প্রপার্টি বিস্তারিত পেজের URL', 'sample' => url('/property/af-land-84920')],
                    '%price%'            => ['label' => 'মূল্য (ফরম্যাটেড)', 'sample' => ' | মূল্য: ১ কোটি ২৫ লাখ'],
                    '%location%'         => ['label' => 'অবস্থান (এলাকা, ব্লক, সেক্টর)', 'sample' => ' | আফতাবনগর, ব্লক এল'],
                    '%source_tag%'       => ['label' => 'লিস্টিং সোর্স বিবরণ', 'sample' => 'Google Sheets সিঙ্ক রেকর্ডের মাধ্যমে'],
                    '%site_name%'        => ['label' => 'প্ল্যাটফর্মের নাম', 'sample' => $siteName],
                    '%login_magic_link%' => ['label' => 'এজেন্টের অটো-লগইন URL', 'sample' => url('/agent-portal?adm_token=xDTWINKVpT4zi09PviIQyE1XSGlAnuYk')],
                    '%phone%'            => ['label' => 'ফোন নম্বর', 'sample' => '01819223344'],
                ],
            ],

            'lead_enquiry_notification' => [
                'key'         => 'lead_enquiry_notification',
                'title'       => '🎯 নতুন ক্লায়েন্ট ইনকোয়ারি ও লিড নোটিফিকেশন (Lead Alert)',
                'category'    => 'সিআরএম ও লিড',
                'description' => 'কোনো প্রপার্টির জন্য নতুন বায়ার রিকোয়ারমেন্ট বা ভিজিট বুকিং আসলে এজেন্ট/ওনারকে হোয়াটসঅ্যাপে পাঠানোর বার্তা।',
                'default'     => "হ্যালো %name%,\n\n{$siteName} থেকে বলছি।\n\nআপনার তালিকাভুক্ত প্রপার্টি '%property_title%' (%property_code%)-এর জন্য আমাদের প্ল্যাটফর্মে একটি নতুন ক্লায়েন্ট ইনকোয়ারি এসেছে:\n\n📌 ইনকোয়ারির ধরন: %enquiry_type%\n%lead_details%\n\nআমাদের প্ল্যাটফর্ম টিম বায়ারের সাথে আলোচনা করে পরবর্তী পদক্ষেপ সমন্বয় করছে। আপনি কি এই বিষয়ে বিস্তারিত আপডেট পেতে আগ্রহী? অনুগ্রহ করে আমাদের রিপ্লাই দিন।\n\n🔑 আপনার এজেন্ট পোর্টাল: %login_magic_link%\n\nধন্যবাদ,\n{$siteName} টিম",
                'variables'   => [
                    '%name%'             => ['label' => 'প্রপার্টি মালিক / এজেন্টের নাম', 'sample' => 'হাজী হাফিজুর রহমান'],
                    '%property_code%'    => ['label' => 'প্রপার্টি কোড', 'sample' => 'AF-FLAT-55219'],
                    '%property_title%'   => ['label' => 'প্রপার্টির নাম', 'sample' => '১৫৫০ বর্গফুট রেডি বিলাসবহুল ফ্ল্যাট'],
                    '%enquiry_type%'     => ['label' => 'ইনকোয়ারির ধরন', 'sample' => 'সরাসরি সাইট ভিজিট বুকিং'],
                    '%lead_details%'     => ['label' => 'ইনকোয়ারির বিস্তারিত বিবরণ', 'sample' => "ভিজিটের তারিখ: ০২ অক্টোবর ২০২৬\nবায়ার নোট: \"আমি সরাসরি পরিবার নিয়ে ফ্ল্যাটটি দেখতে চাই।\""],
                    '%lead_message%'     => ['label' => 'বায়ারের মূল মেসেজ', 'sample' => 'আমি সরাসরি পরিবার নিয়ে ফ্ল্যাটটি দেখতে চাই।'],
                    '%site_name%'        => ['label' => 'সাইটের নাম', 'sample' => $siteName],
                    '%login_magic_link%' => ['label' => 'এজেন্ট পোর্টাল লিঙ্ক', 'sample' => url('/agent-portal?adm_token=kYz5Pj511fokMxZs1tCEymThWDd1QG6T')],
                    '%phone%'            => ['label' => 'এজেন্টের ফোন নম্বর', 'sample' => '01711002233'],
                ],
            ],

            'agent_general_contact' => [
                'key'         => 'agent_general_contact',
                'title'       => '💬 সাধারণ যোগাযোগ ও প্রশাসনিক বার্তা (General Contact)',
                'category'    => 'অ্যাডমিন যোগাযোগ',
                'description' => 'এজেন্ট ও ওনার তালিকা বা ড্রয়ার থেকে এজেন্টের সাথে সরাসরি হোয়াটসঅ্যাপ চ্যাট শুরু করার প্রারম্ভিক বার্তা।',
                'default'     => "হ্যালো %name%! {$siteName} অ্যাডমিন প্যানেল থেকে আপনার সাথে যোগাযোগ করা হচ্ছে।",
                'variables'   => [
                    '%name%'             => ['label' => 'এজেন্টের নাম', 'sample' => 'আরিফুল ইসলাম'],
                    '%site_name%'        => ['label' => 'সাইটের নাম', 'sample' => $siteName],
                    '%phone%'            => ['label' => 'ফোন নম্বর', 'sample' => '01911445566'],
                    '%login_magic_link%' => ['label' => 'অটো-লগইন লিঙ্ক', 'sample' => url('/agent-portal?adm_token=mbeYhWNNJv5PPRXjPX0MfwpezMAqJQth')],
                    '%role%'             => ['label' => 'ব্যবহারকারীর রোল', 'sample' => 'Direct Agent'],
                ],
            ],

            'agent_penalty_warning' => [
                'key'         => 'agent_penalty_warning',
                'title'       => '⚠️ এজেন্ট সতর্কতা ও পেনাল্টি নোটিশ (Warning / Penalty)',
                'category'    => 'শৃঙ্খলা ও নিয়ন্ত্রণ',
                'description' => 'কোনো এজেন্টের অ্যাকাউন্টে পেনাল্টি বা বাইপাস সতর্কবার্তা জারি করা হলে তাকে হোয়াটসঅ্যাপে পাঠানোর নোটিশ।',
                'default'     => "আসসালামু আলাইকুম %name%,\n\n{$siteName} অ্যাডমিন প্যানেল থেকে অবগত করা হচ্ছে যে, আপনার অ্যাকাউন্টে একটি প্রশাসনিক সতর্কতা জারি করা হয়েছে।\n\nসতর্কতার কারণ: %penalty_reason%\n\nঅনুগ্রহ করে নীতিমালা মেনে চলুন অথবা যেকোনো পর্যালোচনা বা ব্যাখ্যার জন্য সরাসরি অ্যাডমিন সাপোর্টে যোগাযোগ করুন।\n\n🔑 আপনার পোর্টাল অ্যাকাউন্ট: %login_magic_link%\n\nধন্যবাদ,\n{$siteName} কর্তৃপক্ষ",
                'variables'   => [
                    '%name%'             => ['label' => 'এজেন্টের পুরো নাম', 'sample' => 'ইসমাইল হোসাইন'],
                    '%penalty_reason%'   => ['label' => 'পেনাল্টি বা সতর্কতার কারণ', 'sample' => 'ক্লায়েন্ট বাইপাস চেষ্টার অভিযোগে রেটিং সাময়িক স্থগিত।'],
                    '%site_name%'        => ['label' => 'সাইটের নাম', 'sample' => $siteName],
                    '%phone%'            => ['label' => 'মোবাইল নম্বর', 'sample' => '01611889900'],
                    '%login_magic_link%' => ['label' => 'এজেন্ট পোর্টাল লিঙ্ক', 'sample' => url('/agent-portal?adm_token=Marq9QMqaC5jq2NSl7g3jtERm2PnKV8w')],
                ],
            ],
        ];
    }

    /**
     * Get single template content (from Setting model or fallback to default).
     */
    public static function getTemplate(string $key): string
    {
        $defs = self::getDefinitions();
        if (!isset($defs[$key])) {
            return '';
        }

        $saved = Setting::get(self::OPTION_PREFIX . $key);
        if ($saved !== null && is_string($saved) && trim($saved) !== '') {
            return $saved;
        }

        return $defs[$key]['default'] ?? '';
    }

    /**
     * Save a specific template.
     */
    public static function saveTemplate(string $key, string $content): void
    {
        Setting::set(self::OPTION_PREFIX . $key, $content);
    }

    /**
     * Render a template with variable substitution.
     */
    public static function render(string $key, array $variables = []): string
    {
        $template = self::getTemplate($key);
        if (empty($template)) {
            return '';
        }
        return self::renderText($template, $variables);
    }

    /**
     * Replace %placeholder% in raw text with given variables.
     */
    public static function renderText(string $text, array $variables = []): string
    {
        if (!isset($variables['%site_name%']) && !isset($variables['site_name'])) {
            $variables['%site_name%'] = config('app.name', 'PropertyKoi.com');
        }

        $replacements = [];
        foreach ($variables as $tag => $val) {
            $formattedTag = str_starts_with($tag, '%') && str_ends_with($tag, '%') ? $tag : '%' . trim($tag, '%') . '%';
            $replacements[$formattedTag] = (string) $val;
        }

        return strtr($text, $replacements);
    }
}
