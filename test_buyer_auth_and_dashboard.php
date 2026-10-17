<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BuyerDemand;
use App\Models\BuyerInteraction;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "====================================================\n";
echo "TESTING GMAIL / EMAIL AUTH & BUYER DASHBOARD PARITY\n";
echo "====================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest($condition, $name) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
    }
}

// ----------------------------------------------------
// TEST 1: User Registration with Username, Pass & Activation Token
// ----------------------------------------------------
$testUsername = 'testbuyer_' . random_int(1000, 9999);
$testEmail = $testUsername . '@example.com';
$testPhone = '017' . random_int(10000000, 99999999);
$testPass = 'secret123456';

$authCtrl = app(\App\Http\Controllers\AuthController::class);
$regReq = Request::create('/register', 'POST', [
    'name' => 'Mithu Buyer',
    'username' => $testUsername,
    'email' => $testEmail,
    'password' => $testPass,
    'password_confirmation' => $testPass,
    'whatsapp_number' => $testPhone,
]);

$response = $authCtrl->register($regReq);
$createdUser = User::where('username', $testUsername)->first();

assertTest($createdUser !== null, "User successfully registered in database with username '$testUsername'");
assertTest($createdUser && !$createdUser->isEmailVerified(), "User email_verified_at is initially NULL before activation");
assertTest($createdUser && !empty($createdUser->email_verification_token), "User has generated email_verification_token");
assertTest($createdUser && !empty($createdUser->email_verification_code), "User has generated 6-digit OTP code: " . ($createdUser->email_verification_code ?? ''));
assertTest(Auth::check() && Auth::id() === $createdUser->id, "User is automatically logged in after registration");
assertTest($response->getTargetUrl() === route('buyer.dashboard'), "Registration redirects directly to buyer-dashboard");

// ----------------------------------------------------
// TEST 2: Email Activation via Link
// ----------------------------------------------------
$activationToken = $createdUser->email_verification_token;
$verifyResponse = $authCtrl->verifyEmail($activationToken);
$createdUser->refresh();

assertTest($createdUser->isEmailVerified(), "Email is marked as verified after following activation link");
assertTest($createdUser->email_verification_token === null, "Activation token is cleared after verification");
assertTest(str_contains($verifyResponse->getTargetUrl(), 'email_verified=1'), "Activation link redirects to buyer-dashboard with email_verified=1");

// ----------------------------------------------------
// TEST 3: Login with Username, Email, and Phone
// ----------------------------------------------------
Auth::logout();
assertTest(!Auth::check(), "User logged out successfully");

// 3a: Login using Username
$loginReq1 = Request::create('/login', 'POST', [
    'email_or_phone' => $testUsername,
    'password' => $testPass,
]);
$loginResp1 = $authCtrl->login($loginReq1);
assertTest(Auth::check() && Auth::user()->username === $testUsername, "Login via USERNAME works correctly");

// 3b: Verify redirection goes to buyer-dashboard
$redirectUrl = AccessControlService::getLoginRedirectUrl(Auth::user());
assertTest($redirectUrl === route('buyer.dashboard'), "Login redirect target is /buyer-dashboard for buyers");

// 3c: Login using Email
Auth::logout();
$loginReq2 = Request::create('/login', 'POST', [
    'email_or_phone' => $testEmail,
    'password' => $testPass,
]);
$authCtrl->login($loginReq2);
assertTest(Auth::check() && Auth::user()->email === $testEmail, "Login via EMAIL works correctly");

// 3d: Login using Phone
Auth::logout();
$loginReq3 = Request::create('/login', 'POST', [
    'email_or_phone' => $testPhone,
    'password' => $testPass,
]);
$authCtrl->login($loginReq3);
assertTest(Auth::check() && Auth::user()->phone === $testPhone, "Login via PHONE works correctly");

// ----------------------------------------------------
// TEST 4: Resend & OTP Code Verification
// ----------------------------------------------------
$createdUser->update(['email_verified_at' => null]);
$resendResp = $authCtrl->resendVerification(Request::create('/email/resend', 'POST'));
$createdUser->refresh();
$newCode = $createdUser->email_verification_code;

assertTest(!empty($newCode) && strlen($newCode) === 6, "Resend verification generates new 6-digit OTP: $newCode");

$codeReq = Request::create('/email/verify-code', 'POST', ['code' => $newCode]);
$codeResp = $authCtrl->verifyEmailCode($codeReq);
$createdUser->refresh();

assertTest($createdUser->isEmailVerified(), "6-digit OTP code verification successfully activates email");

// ----------------------------------------------------
// TEST 5: Buyer Dashboard Controller & 5 Tabs Data
// ----------------------------------------------------
$dashCtrl = app(\App\Http\Controllers\BuyerDashboardController::class);
$view = $dashCtrl->index(Request::create('/buyer-dashboard', 'GET'));
$viewData = $view->getData();

assertTest(isset($viewData['profile']) && $viewData['profile']['display_name'] === 'Mithu Buyer', "Tab 5 (Profile) data present in dashboard");
assertTest(isset($viewData['favorites']) && is_array($viewData['favorites']), "Tab 1 (Favorites) array present in dashboard");
assertTest(isset($viewData['demands']) && is_array($viewData['demands']), "Tab 2 (Demands) array present in dashboard");
assertTest(isset($viewData['interactions']) && is_array($viewData['interactions']), "Tab 3 (Interactions) array present in dashboard");
assertTest(isset($viewData['myProperties']) && is_array($viewData['myProperties']), "Tab 4 (My Properties) array present in dashboard");
assertTest(isset($viewData['stats']) && count($viewData['stats']) === 6, "6-metric stats bar data present in dashboard");

// ----------------------------------------------------
// TEST 6: Favorites Management (Toggle & Remove)
// ----------------------------------------------------
$sampleProperty = Property::first();
if (!$sampleProperty) {
    $sampleProperty = Property::create([
        'title' => 'Test Land in Block A',
        'slug' => 'test-land-block-a-' . time(),
        'type' => 'land',
        'status' => 'live',
        'block' => 'A',
        'asking_price' => 7500000,
    ]);
}

$favReq1 = Request::create('/buyer-dashboard/favorite/toggle', 'POST', ['property_id' => $sampleProperty->id]);
$favResp1 = $dashCtrl->toggleFavorite($favReq1);
$favData1 = json_decode($favResp1->getContent(), true);

assertTest($favData1['success'] === true && $favData1['is_favorite'] === true, "Favorite toggle adds property to favorites");
assertTest(Favorite::where('user_id', $createdUser->id)->where('property_id', $sampleProperty->id)->exists(), "Favorite record exists in favorites table");

$favReq2 = Request::create('/buyer-dashboard/favorite/remove', 'POST', ['property_id' => $sampleProperty->id]);
$favResp2 = $dashCtrl->removeFavorite($favReq2);
$favData2 = json_decode($favResp2->getContent(), true);

assertTest($favData2['success'] === true, "Favorite remove action succeeds");
assertTest(!Favorite::where('user_id', $createdUser->id)->where('property_id', $sampleProperty->id)->exists(), "Favorite record removed from favorites table");

// ----------------------------------------------------
// TEST 7: Buyer Demands Management (Store, Toggle Status, Delete)
// ----------------------------------------------------
$demandReq = Request::create('/buyer-dashboard/demand', 'POST', [
    'property_type' => 'land',
    'preferred_areas' => ['Aftabnagar'],
    'min_budget' => 5000000,
    'max_budget' => 15000000,
    'notify_channels' => ['push', 'email', 'phone'],
]);
$demandResp = $dashCtrl->storeDemand($demandReq);
$demandData = json_decode($demandResp->getContent(), true);
$createdDemand = $demandData['demand'] ?? null;

assertTest($demandData['success'] === true && !empty($createdDemand['id']), "New Buyer Demand created via modal");

$toggleReq = Request::create('/buyer-dashboard/demand/toggle', 'POST', [
    'demand_id' => $createdDemand['id'],
    'status' => 'closed',
]);
$toggleResp = $dashCtrl->toggleDemandStatus($toggleReq);
$toggleData = json_decode($toggleResp->getContent(), true);
assertTest($toggleData['success'] === true && $toggleData['status'] === 'closed', "Demand status toggled to closed");

$deleteReq = Request::create('/buyer-dashboard/demand/delete', 'POST', [
    'demand_id' => $createdDemand['id'],
]);
$deleteResp = $dashCtrl->deleteDemand($deleteReq);
$deleteData = json_decode($deleteResp->getContent(), true);
assertTest($deleteData['success'] === true, "Demand successfully deleted");

// ----------------------------------------------------
// TEST 8: Profile Update & Email Addition
// ----------------------------------------------------
$newAltEmail = 'mithu.updated.' . time() . '@example.com';
$newAltPhone = '018' . random_int(10000000, 99999999);
$profReq = Request::create('/buyer-dashboard/profile', 'POST', [
    'displayName' => 'Mithu Updated Buyer',
    'email' => $newAltEmail,
    'whatsappNumber' => $newAltPhone,
    'phone' => $newAltPhone,
    'preferredType' => 'flat',
    'preferredBlocks' => 'Block C, D',
    'budgetRange' => '1.5 - 2 Crore',
]);
$profResp = $dashCtrl->updateProfile($profReq);
$profData = json_decode($profResp->getContent(), true);
$createdUser->refresh();

assertTest($profData['success'] === true, "Profile update succeeds");
assertTest($createdUser->email === $newAltEmail, "Email address updated to new email: $newAltEmail");
assertTest($createdUser->whatsapp_number === $newAltPhone, "WhatsApp number updated to $newAltPhone");
assertTest($createdUser->preferred_type === 'flat', "Preferred type preference saved");
assertTest(!$createdUser->isEmailVerified(), "Changing email resets verification status and sends activation link");

// ----------------------------------------------------
// TEST 9: Google OAuth Mock / Direct Auth
// ----------------------------------------------------
$mockReq = Request::create('/auth/google/mock', 'POST', [
    'mock_email' => 'google.user.' . time() . '@gmail.com',
    'mock_name' => 'Google Verified User',
]);
$mockResp = $authCtrl->handleGoogleMockLogin($mockReq);
$googleUser = Auth::user();

assertTest($googleUser !== null && $googleUser->isEmailVerified(), "Google sign-in creates pre-verified buyer account");
assertTest(!empty($googleUser->google_id), "Google user has google_id linked");
assertTest($mockResp->getTargetUrl() === route('buyer.dashboard'), "Google login redirects directly to buyer-dashboard");

echo "\n----------------------------------------------------\n";
echo "SUMMARY: $testsPassed / $totalTests tests passed.\n";
echo "----------------------------------------------------\n";
