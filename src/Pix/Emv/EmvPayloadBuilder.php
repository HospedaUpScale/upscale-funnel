<?php

namespace App\Pix\Emv;

class EmvPayloadBuilder
{
    public static function build(
        string $pixKey,
        string $merchantName,
        string $merchantCity,
        float $amount,
        string $txId
    ): string {
        // Clean text
        $name = substr(self::removeAccents($merchantName), 0, 25);
        $city = substr(self::removeAccents($merchantCity), 0, 15);
        $amountFormatted = number_format($amount, 2, '.', '');
        $cleanTxId = preg_replace('/[^A-Za-z0-9]/', '', $txId);
        if (empty($cleanTxId)) {
            $cleanTxId = '***';
        }

        // Sub-payload 26 (Merchant Account Information)
        $gui = self::formatField('00', 'br.gov.bcb.pix');
        $key = self::formatField('01', $pixKey);
        $merchantAccount = self::formatField('26', $gui . $key);

        // Sub-payload 62 (Additional Data)
        $txField = self::formatField('05', $cleanTxId);
        $additionalData = self::formatField('62', $txField);

        $payload = self::formatField('00', '01')
            . $merchantAccount
            . self::formatField('52', '0000')
            . self::formatField('53', '986')
            . self::formatField('54', $amountFormatted)
            . self::formatField('58', 'BR')
            . self::formatField('59', strtoupper($name ?: 'LOJA'))
            . self::formatField('60', strtoupper($city ?: 'SAO PAULO'))
            . $additionalData
            . '6304'; // CRC placeholder

        $crc = self::calculateCRC16($payload);
        return $payload . $crc;
    }

    private static function formatField(string $id, string $value): string
    {
        $len = str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }

    public static function calculateCRC16(string $payload): string
    {
        $polynomial = 0x1021;
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private static function removeAccents(string $string): string
    {
        return preg_replace(
            '~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
            '$1',
            htmlentities($string, ENT_QUOTES, 'UTF-8')
        );
    }
}
