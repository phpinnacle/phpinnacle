<?php

namespace PHPinnacle\Money\Services;

use InvalidArgumentException;
use PHPinnacle\Money\Currencies;
use PHPinnacle\Money\Money;

class MoneyParser
{
    private const string DECIMAL_PATTERN = '/^(?P<sign>-)?(?P<digits>\d+)?\.?(?P<fraction>\d+)?$/';

    public static function parse(Money|int|string|array|null $amount, ?string $currency = null): Money
    {
        if ($amount instanceof Money) {
            return $amount;
        }

        if (!is_array($amount) && $currency === null) {
            throw new InvalidArgumentException('Currency is required when amount is a string or integer.');
        }

        if (is_array($amount)) {
            ['amount' => $amount, 'currency' => $currency] = array_replace(['currency' => $currency], $amount);
        }

        if ($amount === null) {
            $amount = 0;
        }

        return is_int($amount)
            ? new Money($amount, $currency)
            : self::parseDecimal($amount, $currency);
    }

    private static function parseDecimal(string $money, string $currency): Money
    {
        $decimal = str_replace([','], '', trim($money));

        if ($decimal === '') {
            return new Money(0, $currency);
        }

        if (!preg_match(self::DECIMAL_PATTERN, $decimal, $matches) || !array_key_exists('digits', $matches)) {
            throw new InvalidArgumentException(sprintf('Cannot parse "%s" to Money.', $decimal));
        }

        $negative = ($matches['sign'] ?? null) === '-';
        $decimal = $matches['digits'];

        if ($negative) {
            $decimal = '-' . $decimal;
        }

        $subunit = Currencies::subunit($currency);

        $fraction = $matches['fraction'] ?? null;

        if ($fraction !== null) {
            $fractionDigits = strlen($fraction);

            $decimal .= $fraction;
            $decimal = self::roundMoneyValue($decimal, $subunit, $fractionDigits);

            if ($fractionDigits > $subunit) {
                $decimal = substr($decimal, 0, $subunit - $fractionDigits);
            } elseif ($fractionDigits < $subunit) {
                $decimal .= str_pad('', $subunit - $fractionDigits, '0');
            }
        } else {
            $decimal .= str_pad('', $subunit, '0');
        }

        if ($negative) {
            $decimal = '-' . ltrim(substr($decimal, 1), '0');
        } else {
            $decimal = ltrim($decimal, '0');
        }

        if ($decimal === '' || $decimal === '-') {
            $decimal = '0';
        }

        return new Money((int) $decimal, $currency);
    }

    private static function roundMoneyValue(string $moneyValue, int $targetDigits, int $havingDigits): string
    {
        $valueLength = strlen($moneyValue);
        $shouldRound = $targetDigits < $havingDigits && ($valueLength - $havingDigits + $targetDigits) > 0;

        if ($shouldRound && $moneyValue[$valueLength - $havingDigits + $targetDigits] >= 5) {
            $position = $valueLength - $havingDigits + $targetDigits;
            $addend = 1;

            while ($position > 0) {
                $newValue = (string) ((int) $moneyValue[$position - 1] + $addend);

                if ($newValue >= 10) {
                    $moneyValue[$position - 1] = $newValue[1];
                    $addend = $newValue[0];
                    $position--;
                    if ($position === 0) {
                        $moneyValue = $addend . $moneyValue;
                    }
                } else {
                    if ($moneyValue[$position - 1] === '-') {
                        $moneyValue[$position - 1] = $newValue[0];
                        $moneyValue = '-' . $moneyValue;
                    } else {
                        $moneyValue[$position - 1] = $newValue[0];
                    }

                    break;
                }
            }
        }

        return $moneyValue;
    }
}
