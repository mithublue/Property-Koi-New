<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাকাউন্ট ভেরিফিকেশন</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }
        .container {
            max-width: 580px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            padding: 35px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #a7f3d0;
        }
        .content {
            padding: 35px 30px;
            font-size: 14px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .btn-box {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background-color: #059669;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .otp-box {
            background-color: #f0fdf4;
            border: 2px dashed #86efac;
            border-radius: 14px;
            padding: 18px;
            text-align: center;
            margin: 25px 0;
        }
        .otp-label {
            font-size: 12px;
            font-weight: 700;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 30px;
            font-weight: 900;
            color: #065f46;
            letter-spacing: 8px;
            margin: 4px 0;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .url-fallback {
            word-break: break-all;
            font-size: 11px;
            color: #64748b;
            background: #f8fafc;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PropertyKoi</h1>
            <p>আফতাবনগর সরাসরি প্রপার্টি মার্কেটপ্লেস</p>
        </div>
        <div class="content">
            <div class="greeting">আসসালামু আলাইকুম, {{ $user->name }}!</div>
            <p>
                PropertyKoi মার্কেটপ্লেসে আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে। আপনার ইমেইল ঠিকানাটি নিশ্চিত করতে এবং ক্রেতা ড্যাশবোর্ডে পূর্ণ এক্সেস পেতে নিচের বাটনে ক্লিক করুন:
            </p>

            <div class="btn-box">
                <a href="{{ $activationUrl }}" class="btn">✓ অ্যাকাউন্ট অ্যাক্টিভেট ও প্রবেশ করুন &rarr;</a>
            </div>

            <div class="otp-box">
                <div class="otp-label">অথবা ৬ সংখ্যার ওটিপি কোড ব্যবহার করুন:</div>
                <div class="otp-code">{{ $activationCode }}</div>
                <div style="font-size: 11px; color: #15803d; margin-top: 4px;">ড্যাশবোর্ডের প্রোফাইল সেকশনে এই কোডটি লিখেও ভেরিফাই করতে পারেন।</div>
            </div>

            <p style="font-size: 12px; color: #64748b; margin-top: 25px;">
                বাটনে কাজ না করলে নিচের লিঙ্কটি কপি করে আপনার ব্রাউজারের অ্যাড্রেস বারে পেস্ট করুন:
            </p>
            <div class="url-fallback">{{ $activationUrl }}</div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} PropertyKoi. সর্বস্বত্ব সংরক্ষিত।<br>
            আফতাবনগর, ঢাকা, বাংলাদেশ।
        </div>
    </div>
</body>
</html>
