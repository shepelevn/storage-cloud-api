<?php

declare(strict_types=1);

namespace Models\ShareRecord;

class ShareRecordFactory
{
    public static function createShareRecordTemplate(): ShareRecord
    {
        return new ShareRecord(
            -1,
            -1,
            -1,
        );
    }
}
