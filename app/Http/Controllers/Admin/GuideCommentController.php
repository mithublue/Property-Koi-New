<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideComment;
use App\Services\GuideCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideCommentController extends Controller
{
    /**
     * List all guide inquiries & comments for admin moderation.
     */
    public function index(Request $request): View
    {
        $query = GuideComment::with(['guide', 'user', 'replies.user'])->latest('id');

        $status = $request->query('status', 'all');
        if ($status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($status === 'approved') {
            $query->where('is_approved', true);
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->q);
            $query->where(function ($sub) use ($keyword) {
                $sub->where('author_name', 'like', "%{$keyword}%")
                    ->orWhere('author_phone', 'like', "%{$keyword}%")
                    ->orWhere('author_email', 'like', "%{$keyword}%")
                    ->orWhere('comment', 'like', "%{$keyword}%")
                    ->orWhereHas('guide', function ($gq) use ($keyword) {
                        $gq->where('title', 'like', "%{$keyword}%");
                    });
            });
        }

        $comments = $query->paginate(20)->withQueryString();

        $stats = [
            'total'    => GuideComment::count(),
            'pending'  => GuideComment::where('is_approved', false)->count(),
            'approved' => GuideComment::where('is_approved', true)->count(),
        ];

        return view('admin.guides.comments', compact('comments', 'stats', 'status'));
    }

    /**
     * Approve a pending comment.
     */
    public function approve(GuideComment $comment): RedirectResponse
    {
        $comment->update(['is_approved' => true]);

        return back()->with('success', "মন্তব্যটি অনুমোদন করা হয়েছে এবং লাইভ সাইটে প্রকাশিত হয়েছে।");
    }

    /**
     * Reject or un-approve a comment.
     */
    public function reject(GuideComment $comment): RedirectResponse
    {
        $comment->update(['is_approved' => false]);

        return back()->with('success', "মন্তব্যটি স্থগিত/অপ্রকাশিত করা হয়েছে।");
    }

    /**
     * Admin reply to a comment.
     */
    public function reply(Request $request, GuideComment $comment): RedirectResponse
    {
        $request->validate([
            'reply' => 'required|string|min:3|max:3000',
        ]);

        GuideCommentService::replyAsAdmin($comment, $request->reply, auth()->user());

        // Also ensure parent comment is approved when admin replies
        if (!$comment->is_approved) {
            $comment->update(['is_approved' => true]);
        }

        return back()->with('success', "আপনার এক্সপার্ট উত্তরটি সফলভাবে প্রকাশিত হয়েছে!");
    }

    /**
     * Delete a comment.
     */
    public function destroy(GuideComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', "মন্তব্যটি মুছে ফেলা হয়েছে।");
    }
}
