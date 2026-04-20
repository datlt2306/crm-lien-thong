<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PaymentVerified extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $student;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->student = $payment->student;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác Nhận Thanh Toán & Phiếu Thu Lệ Phí - ' . $this->student->full_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.student.payment_verified',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->payment->receipt_path) {
            // Chúng ta không đính kèm trực tiếp từ Google Drive vì có thể gây chậm hoặc lỗi timeout khi gửi mail queue
            // Thay vào đó chúng ta sẽ gửi link tải trong nội dung email cho an toàn.
            // Nhưng nếu sếp muốn đính kèm thì dùng logic dưới đây:
            /*
            $attachments[] = Attachment::fromStorageDisk('google', $this->payment->receipt_path)
                ->as('Phieu_Thu_' . $this->payment->receipt_number . '.pdf');
            */
        }

        return $attachments;
    }
}
