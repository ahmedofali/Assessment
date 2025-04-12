<?php

namespace App\Mail;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IngredientLowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public int $ingredientId)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ingredient Low Stock Alert',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $ingredient = Ingredient::query()->findOrFail($this->ingredientId);

        return new Content(
            markdown: 'mail.ingredients.low-stock',
            with: [
                'ingredient' => $ingredient
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
