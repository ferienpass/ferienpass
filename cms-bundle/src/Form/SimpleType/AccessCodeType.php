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

namespace Ferienpass\CmsBundle\Form\SimpleType;

use Ferienpass\CoreBundle\Entity\AccessCode;
use Ferienpass\CoreBundle\Entity\AccessCodeStrategy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessCodeType extends AbstractType implements DataTransformerInterface
{
    private AccessCodeStrategy $accessCodeStrategy;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->accessCodeStrategy = $options['accessCodeStrategy'];

        $builder->addModelTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('accessCodeStrategy');
        $resolver->setRequired('accessCodeStrategy');
        $resolver->setAllowedTypes('accessCodeStrategy', AccessCodeStrategy::class);
        $resolver->setDefaults([
            'invalid_message' => 'Der Zugangscode ist leider nicht gültig oder bereits eingelöst worden.',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function transform(mixed $value)
    {
        if ($value instanceof AccessCode) {
            return $value->getCode();
        }

        return null;
    }

    public function reverseTransform(mixed $value)
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if (null === ($code = $this->accessCodeStrategy->findCode($value)) || !$this->accessCodeStrategy->getMax() || $code->getParticipants()->count() >= $this->accessCodeStrategy->getMax()) {
            throw new TransformationFailedException();
        }

        return $code;
    }
}
