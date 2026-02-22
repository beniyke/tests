<?php

declare(strict_types=1);

namespace Tests\System\Support\Mail;

use Helpers\Data\Data;
use Mail\Contracts\Mailable;
use Mail\Core\EmailBuilder;

class StubMailable implements Mailable
{
    public function toMail(EmailBuilder $builder): Data
    {
        return Data::make([]);
    }
}
