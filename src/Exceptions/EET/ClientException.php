<?php declare(strict_types=1);

namespace FilipSedivy\EET\Exceptions\EET;

use FilipSedivy\EET\Exceptions\RuntimeException;
use FilipSedivy\EET\Receipt;
use Throwable;

class ClientException extends RuntimeException
{
    private Receipt $receipt;

    private ?string $pkp;

    private ?string $bkp;

    public function __construct(Receipt $receipt, ?string $pkp, ?string $bkp, Throwable $previous)
    {
        $this->receipt = $receipt;
        $this->bkp = $bkp;
        $this->pkp = $pkp;

        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    public function getReceipt(): Receipt
    {
        return $this->receipt;
    }

    public function getBkp(): ?string
    {
        return $this->bkp;
    }

    public function getPkp(bool $encoded = true): ?string
    {
        $pkp = $this->pkp;

        if ($pkp === null) {
            return null;
        }

        if ($encoded) {
            $pkp = base64_encode($pkp);
        }

        return $pkp;
    }
}
