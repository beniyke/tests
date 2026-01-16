<?php

declare(strict_types=1);

namespace Tests\System\Helpers;

use Helpers\Data;
use Mail\Contracts\Mailable;
use Mail\Core\EmailBuilder;

class TestNotification implements Mailable
{
    public function __construct(private array $data = [])
    {
    }

    public function toMail(EmailBuilder $builder): Data
    {
        return Data::make($this->data);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
