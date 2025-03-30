<?php

namespace AtylabLibs\Totp;

class Totp
{
    private const DEFAULT_TIME_STEP = 30;
    private const BASE_32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private $timeStep;

    public function __construct()
    {
        $this->timeStep = self::DEFAULT_TIME_STEP;
    }

    public function setTimeStep(int $timeStep): void
    {
        if ($timeStep <= 0) {
            throw new \InvalidArgumentException('Time step must be a positive integer.');
        }
        $this->timeStep = $timeStep;
    }

    public function convertSecret(string $seed): string
    {
        return $this->base32Encode($seed);
    }

    public function challengeTotp(string $seed, string $totp): bool
    {
        $currentSteps = $this->getCurrentSteps();

        $n = -1;
        for ($i = -1; $i <= 1; $i++) {
            $otp = $this->generateTotp($seed, $currentSteps + $n++);
            if ($otp === $totp) {
                return true;
            }
        }

        return false;
    }

    public function createTotp(string $seed, array $steps): array
    {
        $result = [];
        $currentSteps = $this->getCurrentSteps();

        foreach ($steps as $step) {
            $result[(string) $step] = $this->generateTotp($seed, $currentSteps + $step);
        }

        return $result;
    }

    private function base32Encode(string $string): string
    {
        $byteLength = strlen($string);

        $dataBuffer = 0;
        $dataBufferBitLength = 0;

        $byteOffset = 0;

        $result = '';

        while ($dataBufferBitLength > 0 || $byteOffset < $byteLength) {
            if ($dataBufferBitLength < 5) {
                if ($byteOffset < $byteLength) {
                    $dataBuffer <<= 8;
                    $dataBuffer |= ord($string[$byteOffset++]);
                    $dataBufferBitLength += 8;
                } else {
                    $dataBuffer <<= 5 - $dataBufferBitLength;
                    $dataBufferBitLength = 5;
                }
            }

            $dataBufferBitLength -= 5;
            $value = $dataBuffer >> $dataBufferBitLength & 0x1f;

            $result .= self::BASE_32_ALPHABET[$value];
        }

        return rtrim($result, '=');
    }

    private function dynamicTruncate(string $digest): int
    {
        $offset = ord($digest[19]) & 0xf;

        $binary = (
            ord($digest[$offset    ]) << 24 |
            ord($digest[$offset + 1]) << 16 |
            ord($digest[$offset + 2]) <<  8 |
            ord($digest[$offset + 3])
        );

        $binaryMasked = $binary & 0x7fffffff;
        return $binaryMasked;
    }

    private function generateHotp(string $seed, string $counter): string
    {
        $digest = hash_hmac('sha1', $counter, $seed, true);

        $otp = $this->dynamicTruncate($digest) % 1000000;

        $otpString = str_pad($otp, 6, '0', STR_PAD_LEFT);

        return $otpString;
    }

    private function getCurrentSteps(): int
    {
        return intdiv(time(), $this->timeStep);
    }

    private function int64ToBytesInBigEndian(int $number): string
    {
        $bytes = '';

        $bytes[0] = chr($number >> 56 & 0xff);
        $bytes[1] = chr($number >> 48 & 0xff);
        $bytes[2] = chr($number >> 40 & 0xff);
        $bytes[3] = chr($number >> 32 & 0xff);
        $bytes[4] = chr($number >> 24 & 0xff);
        $bytes[5] = chr($number >> 16 & 0xff);
        $bytes[6] = chr($number >>  8 & 0xff);
        $bytes[7] = chr($number       & 0xff);

        return $bytes;
    }

    private function generateTotp(string $seed, int $steps): string
    {
        $stepsString = $this->int64ToBytesInBigEndian($steps);

        $otpString = $this->generateHotp($seed, $stepsString);

        return $otpString;
    }

    public function createSeed($byteLength = 20): string
    {
        if ($byteLength < 1 || $byteLength > 32) {
            throw new \InvalidArgumentException('Seed length must be between 1 and 32 bytes.');
        }

        $seed_tmp = random_bytes($byteLength);
        return bin2hex($seed_tmp);
    }
}
