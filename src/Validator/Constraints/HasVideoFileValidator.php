<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Validator\Constraints;

use Setono\SyliusVideoPlugin\Model\FileProductVideoInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class HasVideoFileValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasVideoFile) {
            throw new UnexpectedTypeException($constraint, HasVideoFile::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof FileProductVideoInterface) {
            throw new UnexpectedValueException($value, FileProductVideoInterface::class);
        }

        if (null !== $value->getPath() || $value->hasFile()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('file')
            ->addViolation()
        ;
    }
}
