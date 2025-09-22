<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        // Ensure items are loaded for the view
        $this->order->loadMissing('items');
    }

    public function build(): self
    {
        return $this->subject('注文 ' . $this->order->order_number . ' が確定しました')
            ->view('emails.order_confirmed')
            ->with([
                'order' => $this->order,
            ]);
    }
}

