<!-- resources/views/emails/ingredients/low-stock.blade.php -->
@component('mail::message')
    # Low Stock Alert

    The stock level for **{{ $ingredient->name }}** has dropped below 50%.

    ## Current Stock Details
    - Current Amount: **{{ number_format($ingredient->stock, 2) }} **
    - Initial Stock: **{{ number_format($ingredient->initial_stock, 2) }}**

    Please reorder this ingredient soon to avoid running out of stock.

    Thank you,<br>
    {{ config('app.name') }}
@endcomponent
