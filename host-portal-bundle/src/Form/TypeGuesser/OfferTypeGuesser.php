<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\HostPortalBundle\Form\TypeGuesser;

use Contao\Config;
use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Dto\OfferDto;
use Ferienpass\HostPortalBundle\Dto\Annotation\EntityType as EntityTypeAnnotation;
use Ferienpass\HostPortalBundle\Form\CompoundType\DatesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

class OfferTypeGuesser implements FormTypeGuesserInterface
{
    public function guessType(string $class, string $property): ?TypeGuess
    {
        if (!is_subclass_of($class, OfferDto::class)) {
            return null;
        }

        $reflectionProperty = new \ReflectionProperty($class, $property);
        $type = $reflectionProperty->getType();
        if ($type instanceof \ReflectionNamedType && is_a($type->getName(), Collection::class, true)) {
            if ($type = current(array_merge(...array_map(fn (\ReflectionAttribute $attribute) => $attribute->getArguments(), $reflectionProperty->getAttributes(EntityTypeAnnotation::class))))) {
                return new TypeGuess(EntityType::class, [
                    'class' => $type,
                    'choice_label' => 'name',
                    'multiple' => true,
                ], Guess::HIGH_CONFIDENCE);
            }
        }

        if ($type instanceof \ReflectionNamedType && 'bool' === $type->getName()) {
            return new TypeGuess(CheckboxType::class, [], Guess::HIGH_CONFIDENCE);
        }

        return match ($property) {
            'description' => new TypeGuess(TextareaType::class, [], Guess::HIGH_CONFIDENCE),

            'minParticipants' => new TypeGuess(IntegerType::class, [], Guess::HIGH_CONFIDENCE),
            'maxParticipants' => new TypeGuess(IntegerType::class, [], Guess::HIGH_CONFIDENCE),

            'minAge' => new TypeGuess(IntegerType::class, [], Guess::HIGH_CONFIDENCE),
            'maxAge' => new TypeGuess(IntegerType::class, [], Guess::HIGH_CONFIDENCE),

            'fee' => new TypeGuess(MoneyType::class, [
                'divisor' => 100,
                'html5' => true,
            ], Guess::HIGH_CONFIDENCE),

            'dates' => new TypeGuess(DatesType::class, [
                'help' => 'Sie können eine zusätzliche Zeit eintragen, wenn die gleiche Gruppe von Kindern an mehreren Terminen erscheinen muss. Wenn Sie das Angebot mehrmals anbieten, verwenden Sie stattdessen die Kopierfunktion auf der Übersichtsseite.',
            ], Guess::HIGH_CONFIDENCE),

            'accessibility' => new TypeGuess(ChoiceType::class, [
                'label' => 'Offer.accessibility.0',
                'choices' => [
                    'barrierefrei',
                    'koerperliches-handicap',
                    'assistenz',
                    'geistiges-handicap',
                ],
                'choice_label' => function (string $choice) {
                    return sprintf('accessibility.%s.label', $choice);
                },
                'expanded' => true,
                'multiple' => true,
            ], Guess::HIGH_CONFIDENCE),

            'applicationDeadline' => new TypeGuess(DateType::class, [
                'help' => 'Offer.applicationDeadline.1',
                'input_format' => Config::get('dateFormat'),
                'widget' => 'single_text',
            ], Guess::HIGH_CONFIDENCE),

            default => null,
        };
    }

    public function guessRequired(string $class, string $property): ?ValueGuess
    {
        return null;
    }

    public function guessMaxLength(string $class, string $property): ?ValueGuess
    {
        return null;
    }

    public function guessPattern(string $class, string $property): ?ValueGuess
    {
        return null;
    }
}
