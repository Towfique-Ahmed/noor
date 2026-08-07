<?php
/**
 * Zakat and Sadaqat al-Fitr maths, kept free of any HTML so the rules stay
 * readable and testable.
 */

declare(strict_types=1);

/**
 * Work out Zakat on a set of assets and liabilities.
 *
 * @param array<string, float> $assets      Named asset amounts in one currency.
 * @param float                $liabilities Debts due within the year.
 * @param float                $goldPrice   Price of one gram of gold.
 * @param float                $silverPrice Price of one gram of silver.
 * @param string               $nisabBasis  'silver' (default, more generous) or 'gold'.
 *
 * @return array<string, mixed>
 */
function calculateZakat(array $assets, float $liabilities, float $goldPrice, float $silverPrice, string $nisabBasis = 'silver'): array
{
    $totalAssets = 0.0;
    foreach ($assets as $value) {
        $totalAssets += max(0.0, $value);
    }

    $liabilities = max(0.0, $liabilities);
    $net         = $totalAssets - $liabilities;

    $goldNisab   = $goldPrice * (float) config('zakat.nisab_gold_grams');
    $silverNisab = $silverPrice * (float) config('zakat.nisab_silver_grams');
    $nisab       = $nisabBasis === 'gold' ? $goldNisab : $silverNisab;

    $rate    = (float) config('zakat.rate');
    $payable = ($nisab > 0 && $net >= $nisab) ? $net * $rate : 0.0;

    return [
        'total_assets' => $totalAssets,
        'liabilities'  => $liabilities,
        'net_worth'    => $net,
        'gold_nisab'   => $goldNisab,
        'silver_nisab' => $silverNisab,
        'nisab'        => $nisab,
        'nisab_basis'  => $nisabBasis,
        'rate'         => $rate,
        'eligible'     => $nisab > 0 && $net >= $nisab,
        'has_nisab_input' => $goldPrice > 0 || $silverPrice > 0,
        'payable'      => $payable,
    ];
}

/**
 * Value of gold or silver holdings from a weight and a per-gram price.
 */
function metalValue(float $grams, float $pricePerGram): float
{
    return max(0.0, $grams) * max(0.0, $pricePerGram);
}

/**
 * Sadaqat al-Fitr for a household.
 *
 * One sa' of the chosen staple is due per person; the amount is quoted both in
 * weight and in money using the price per kilogram supplied by the visitor.
 *
 * @return array<string, mixed>
 */
function calculateFitrah(int $people, string $staple, float $pricePerKg): array
{
    $measures = (array) config('fitrah.sa_kg');
    $staple   = array_key_exists($staple, $measures) ? $staple : 'wheat';
    $people   = max(0, $people);

    $perPersonKg = (float) $measures[$staple];
    $totalKg     = $perPersonKg * $people;
    $pricePerKg  = max(0.0, $pricePerKg);

    return [
        'people'        => $people,
        'staple'        => $staple,
        'per_person_kg' => $perPersonKg,
        'total_kg'      => $totalKg,
        'price_per_kg'  => $pricePerKg,
        'per_person'    => $perPersonKg * $pricePerKg,
        'total'         => $totalKg * $pricePerKg,
        'measures'      => $measures,
    ];
}

/**
 * Human readable label for a staple key.
 */
function stapleLabel(string $key): string
{
    return [
        'wheat'  => 'Wheat / flour',
        'barley' => 'Barley',
        'dates'  => 'Dates',
        'raisin' => 'Raisins',
        'rice'   => 'Rice',
    ][$key] ?? ucfirst($key);
}
