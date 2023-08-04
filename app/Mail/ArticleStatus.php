<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ArticleStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $article;
    public $status;
    public $reason;

    public function __construct($article, $status, $reason = null)
    {
        $this->article = $article;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function build()
    {
        $subject = '';
        if ($this->status === 'published') {
            $subject = 'Your request updated article has been published';
        }
        if ($this->status === 'reject') {
            $subject = 'Your request update article has been rejected';
        }
        if ($this->status === 'pending') {
            $subject = 'Request approved article by revision';
        }

        return $this->view('emails.article_status')
            ->subject($subject)
            ->with([
                'subject' => $subject,
                'article' => $this->article,
                'status' => $this->status,
                'reason' => $this->reason,
            ]);
    }
}
