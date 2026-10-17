<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রপার্টি চাহিদা সফলভাবে গৃহীত হয়েছে</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

        <!-- Header -->
        <div style="background: linear-gradient(135deg, #059669 0%, #0d9488 100%); padding: 32px 24px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">PropertyKoi.com</h1>
            <p style="margin: 6px 0 0; font-size: 13px; color: #d1fae5;">📋 আপনার প্রপার্টি চাহিদা সফলভাবে গৃহীত হয়েছে</p>
        </div>

        <!-- Greeting -->
        <div style="padding: 24px 30px 16px;">
            <p style="margin: 0 0 12px; font-size: 16px; line-height: 1.5; color: #0f172a;">
                আসসালামু আলাইকুম <strong>{{ $demand->buyer_name }}</strong>,
            </p>
            <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6;">
                PropertyKoi.com-এ আপনার প্রপার্টি চাহিদা সফলভাবে রেকর্ড করা হয়েছে। আপনার চাহিদা রেফারেন্স কোড:
                <span style="display: inline-block; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 8px; font-weight: 800; font-size: 14px;">
                    #{{ $demand->requirement_code }}
                </span>
            </p>
        </div>

        <!-- Demand Summary Card -->
        <div style="margin: 0 30px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 20px;">
            <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                📌 আপনার চাহিদার বিবরণ
            </h4>

            @php
                $typeLabel = match($demand->property_type) {
                    'land'       => '🌱 জমি / প্লট',
                    'flat'       => '🏢 ফ্ল্যাট',
                    'land_share' => '🤝 ল্যান্ড শেয়ার',
                    'commercial' => '🏬 কমার্শিয়াল স্পেস',
                    default      => 'যেকোনো ধরনের',
                };
                $areas = is_array($demand->preferred_areas) ? implode(', ', $demand->preferred_areas) : ($demand->preferred_areas ?: 'আফতাবনগর');
                $blocks = is_array($demand->preferred_blocks) ? implode(', ', $demand->preferred_blocks) : ($demand->preferred_blocks ?: 'সব ব্লক');
                $budget = ($demand->min_budget || $demand->max_budget)
                    ? (($demand->min_budget ? number_format($demand->min_budget) . ' - ' : '') . number_format($demand->max_budget) . ' টাকা')
                    : 'নির্দিষ্ট নেই';
                $channels = is_array($demand->notify_channels) ? implode(', ', $demand->notify_channels) : ($demand->notify_channels ?: 'Push, Email');
            @endphp

            <table style="width: 100%; font-size: 13px; line-height: 1.8; color: #334155; border-collapse: collapse;">
                <tr>
                    <td style="width: 40%; font-weight: 600; color: #64748b;">প্রপার্টির ধরন:</td>
                    <td style="font-weight: 700; color: #0f172a;">{{ $typeLabel }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; color: #64748b;">পছন্দের এরিয়া:</td>
                    <td style="font-weight: 700; color: #0f172a;">{{ $areas }}</td>
                </tr>
                @if(!empty($demand->preferred_blocks))
                <tr>
                    <td style="font-weight: 600; color: #64748b;">পছন্দের ব্লক:</td>
                    <td style="font-weight: 700; color: #0f172a;">ব্লক: {{ $blocks }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: 600; color: #64748b;">বাজেট পরিসীমা:</td>
                    <td style="font-weight: 700; color: #059669;">{{ $budget }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; color: #64748b;">নোটিফিকেশন চ্যানেল:</td>
                    <td style="font-weight: 600; color: #0f172a; text-transform: uppercase;">{{ $channels }}</td>
                </tr>
            </table>
        </div>

        <!-- AI Engine Status Note -->
        <div style="margin: 0 30px 24px; padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; font-size: 13px; color: #1e40af; line-height: 1.6;">
            🤖 <strong>AI Demand Engine সক্রিয় রয়েছে:</strong> আমাদের সিস্টেম নিয়মিত নতুন প্রপার্টি লিস্টিং পর্যবেক্ষণ করে। আপনার এই চাহিদার সাথে মিল পাওয়া নতুন কোনো জমি বা ফ্ল্যাট লিস্টিং হওয়ামাত্রই আমরা স্বয়ংক্রিয়ভাবে আপনাকে ইমেইল ও পুশ নোটিফিকেশনের মাধ্যমে জানিয়ে দেব।
        </div>

        @if(!empty($matches) && count($matches) > 0)
        <!-- Matching Properties -->
        <div style="padding: 0 30px 24px;">
            <h3 style="margin: 0 0 12px; font-size: 15px; font-weight: 800; color: #059669;">
                🎯 প্রাথমিক ম্যাচিং প্রপার্টিসমূহ ({{ count($matches) }}টি পাওয়া গেছে):
            </h3>
            @foreach($matches as $m)
                @php
                    $p = $m['property'];
                    $loc = ($p->area ?: 'আফতাবনগর') . ($p->block ? ' • ব্লক ' . $p->block : '');
                @endphp
                <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; margin-bottom: 10px; background: #fafafa;">
                    <div style="font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 4px;">{{ $p->title }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">📍 {{ $loc }} | মূল্য: <strong style="color: #059669;">{{ $p->formatted_price }}</strong></div>
                    <a href="{{ route('property.show', $p->slug) }}" style="display: inline-block; padding: 6px 14px; background: #059669; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 12px; font-weight: 700;">
                        প্রপার্টি দেখুন &rarr;
                    </a>
                </div>
            @endforeach
        </div>
        @endif

        <!-- Action CTA -->
        <div style="text-align: center; padding: 0 30px 28px;">
            <a href="{{ route('chahida.show', $demand->requirement_code) }}" style="display: inline-block; padding: 12px 28px; background: #0f172a; color: #ffffff; text-decoration: none; border-radius: 12px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                চাহিদার বিস্তারিত ও স্ট্যাটাস দেখুন &rarr;
            </a>
        </div>

        <!-- Footer & Unsubscribe -->
        <div style="background: #f1f5f9; padding: 20px 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0 0 8px;">
                এই ইমেইলটি PropertyKoi.com মার্কেটপ্লেসের চাহিদা নিশ্চিতকরণ সার্ভিস হিসেবে পাঠানো হয়েছে।
            </p>
            @if(!empty($demand->unsubscribe_token))
            <p style="margin: 0;">
                ভবিষ্যতে অ্যালার্ট বন্ধ করতে চাইলে:
                <a href="{{ route('demand.unsubscribe', $demand->unsubscribe_token) }}" style="color: #e11d48; text-decoration: underline; font-weight: 600;">
                    আনসাবস্ক্রাইব করুন
                </a>
            </p>
            @endif
        </div>

    </div>
</body>
</html>
