<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PostPublishedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $postData;
    public $author;
    public $postUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($postData, $author, $postUrl)
    {
        $this->postData = $postData;
        $this->author = $author;
        $this->postUrl = $postUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your blog post has been published!')
                    ->view('emails.posts.published')
                    ->with([
                        'post' => (object) $this->postData,
                        'author' => $this->author,
                        'postUrl' => $this->postUrl
                    ]);
    }
}
