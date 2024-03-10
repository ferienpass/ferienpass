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

namespace Ferienpass\CmsBundle\Form\CompundType;

use Ferienpass\CmsBundle\Form\SimpleType\AccessCodeType;
use Ferienpass\CoreBundle\Entity\Edition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessCodesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Edition $edition */
        foreach ($options['editions'] as $edition) {
            if (!$edition->getAccessCodeStrategy()) {
                continue;
            }

            $builder->add('accessCode'.$edition->getId(), AccessCodeType::class, [
                'accessCodeStrategy' => $edition->getAccessCodeStrategy(),
                'label' => 'apply.accessCode',
                'help' => 'apply.accessCodeHelp',
                'label_translation_parameters' => [
                    'edition' => $edition->getName(),
                ],
                'help_translation_parameters' => [
                    'edition' => $edition->getName(),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('editions');
        $resolver->setRequired('editions');
        $resolver->addAllowedTypes('editions', 'array');

        $resolver->setDefaults([
            'required' => false,
            'label' => false,
        ]);
    }
}
