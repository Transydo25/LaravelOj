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
            $subject = 'Your article has been published';
        } elseif ($this->status === 'reject') {
            $subject = 'Your article has been rejected';
        }

        return $this->view('emails.article_status')
            ->subject($subject)
            ->with([
                'article' => $this->article,
                'status' => $this->status,
                'reason' => $this->reason,
            ]);
    }
}
