<?php

declare(strict_types=1);

namespace NotificationService;

enum DispatchResult
{
    case Sent;
    case Duplicate;
    case Ignored;
}
