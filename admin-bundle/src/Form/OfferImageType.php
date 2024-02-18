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

namespace Ferienpass\AdminBundle\Form;

use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Studio;
use Ferienpass\CoreBundle\Entity\OfferMedia;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OfferImageType extends AbstractType
{
    public function __construct(private readonly Studio $studio, #[Autowire(service: 'contao.filesystem.mount_manager')] private readonly MountManager $mountManager, private readonly ContaoFramework $contaoFramework)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        /** @var OfferMedia|null $media */
        $media = $form->getParent()->getData()->getImage();

        if (null === $media) {
            return;
        }

        if (!($mount = $this->mountManager->getMounts()['offer_media']) instanceof LocalFilesystemAdapter) {
            return;
        }

        $r = new \ReflectionObject($mount);
        $p = $r->getProperty('rootLocation');
        $p->setAccessible(true);

        $path = $p->getValue($mount);

        $this->contaoFramework->initialize();

        $figure = $this->studio->createFigureBuilder()
            ->fromPath($path.'/'.$media->getPath(), false)
            ->setSize([200, 200, 'proportional'])
            ->build()
        ;

        $view->vars['figure'] = $figure;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('mapped', false);
        $resolver->setDefault('label', false);
    }
}
