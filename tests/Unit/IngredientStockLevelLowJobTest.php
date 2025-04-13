<?php

namespace Tests\Unit;

use App\Jobs\IngredientStockLevelLowJob;
use App\Mail\IngredientLowStockAlertMail;
use App\Models\Ingredient;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IngredientStockLevelLowJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_email_to_merchant_when_stock_is_low(): void
    {
        // Fake the mailer
        Mail::fake();

        $merchant   = Merchant::factory()->create(['email' => 'merchant@example.com']);
        $ingredient = Ingredient::factory()->create(['merchant_id' => $merchant->id]);

        // Dispatch the job
        (new IngredientStockLevelLowJob($ingredient->id))->handle();

        // Assert the mail was sent to the correct email
        Mail::assertSent(IngredientLowStockAlertMail::class, function ($mail) use ($merchant, $ingredient) {
            return $mail->hasTo($merchant->email) &&
                $mail->ingredientId === $ingredient->id;
        });
    }
}
