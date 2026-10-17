<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>প্রপার্টি ডিমান্ড ম্যাচ অ্যালার্ট</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

        <!-- Header -->
        <div style="background: linear-gradient(135deg, #059669 0%, #0d9488 100%); padding: 30px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">PropertyKoi.com</h1>
            <p style="margin: 6px 0 0; font-size: 13px; color: #d1fae5;">🎯 প্রপার্টি ম্যাচ অ্যালার্ট</p>
        </div>

        <!-- Greeting -->
        <div style="padding: 24px 30px 16px;">
            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.5;">
                আসসালামু আলাইকুম <strong>{{ $demand->buyer_name }}</strong>,
            </p>
            <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6;">
                আপনার সংরক্ষিত চাহিদা (কোড: <code style="background: #ecfdf5; color: #059669; padding: 2px 6px; border-radius: 6px; font-weight: 700;">{{ $demand->requirement_code }}</code>) এর সাথে মিলে এমন <strong>{{ count($matches) }}টি</strong> নতুন প্রপার্টি পাওয়া গেছে:
            </p>
        </div>

        <!-- Property Items -->
        <div style="padding: 0 30px 24px;">
            @foreach($matches as $m)
                @php
                    $p = $m['property'];
                    $loc = ($p->area ?: 'আফতাবনগর') . ($p->block ? ' • ব্লক ' . $p->block : '');
                @endphp
                <div style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; margin-bottom: 14px; background: #fafafa;">
                    <h3 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0f172a;">
                        {{ $p->title }}
                    </h3>
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">
                        📍 অবস্থান: {{ $loc }}
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span style="font-size: 16px; font-weight: 800; color: #059669;">
                            {{ $p->formatted_price }}
                        </span>
                        <a href="{{ route('property.show', $p->slug) }}" style="display: inline-block; padding: 8px 16px; background: #059669; color: #ffffff; text-decoration: none; border-radius: 10px; font-size: 12px; font-weight: 700;">
                            বিস্তারিত দেখুন &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer & Unsubscribe -->
        <div style="background: #f1f5f9; padding: 20px 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0 0 8px;">
                এই ইমেইলটি PropertyKoi.com মার্কেটপ্লেসের ডিমান্ড সাবস্ক্রিপশন অনুযায়ী পাঠানো হয়েছে।
            </p>
            <p style="margin: 0;">
                ভবিষ্যতে এই অ্যালার্ট বন্ধ করতে চাইলে:
                <a href="{{ route('demand.unsubscribe', $demand->unsubscribe_token) }}" style="color: #e11d48; text-decoration: underline; font-weight: 600;">
                    এক ক্লিকে আনসাবস্ক্রাইব করুন
                </a>
            </p>
        </div>

    </div>
</body>
</html>
