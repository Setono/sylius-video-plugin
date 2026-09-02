<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * A file video must carry a file: either a stored path (persisted video) or a pending upload
 * (new video). The `path` column is nullable at the database level because the table is shared by
 * every subtype, so the rule is enforced here. The violation is attached to the `file` field.
 */
final class HasVideoFile extends Constraint
{
    public string $message = 'setono_sylius_video.file_video.file.not_blank';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
