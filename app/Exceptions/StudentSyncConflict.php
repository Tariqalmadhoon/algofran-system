<?php

namespace App\Exceptions;

use App\Models\Student;
use RuntimeException;

class StudentSyncConflict extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly ?Student $student = null,
    ) {
        parent::__construct($message);
    }
}
