<?php

namespace App\Services;

/**
 * Controlled failure while computing a Document Library image fingerprint.
 * Controllers map the message to a 422 validation response (never HTTP 500).
 */
final class DocumentImageFingerprintException extends \RuntimeException
{
}
