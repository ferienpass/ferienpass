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

namespace Ferienpass\CoreBundle\DependencyInjection;

use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Offer\OfferEntityInterface;
use Ferienpass\CoreBundle\Export\Offer\PrintSheet\PdfExports;
use Ferienpass\CoreBundle\Export\Offer\Xml\XmlExports;
use Ferienpass\CoreBundle\Export\ParticipantList\WordExport;
use Ferienpass\CoreBundle\Messenger\MessageLogMiddleware;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Ferienpass\CoreBundle\Repository\ResetPasswordRequestRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class FerienpassCoreExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $this->configurePersistence($config['entities'], $container);
        $container->addAliases([
            OfferRepositoryInterface::class => 'ferienpass.repository.offer',
        ]);

        // Parameters
        $container->setParameter('ferienpass.logos_dir', $config['logos_dir']);
        $container->setParameter('ferienpass.images_dir', $config['images_dir']);

        $expressionLanguage = new ExpressionLanguage();
        $container->setParameter('ferienpass.receipt_number_prefix', null === $config['receipt_number_prefix'] ? '' : $expressionLanguage->evaluate($config['receipt_number_prefix'], ['date' => new \DateTimeImmutable()]));

        // Injection
        if (isset($config['export'])) {
            $pdfConfigs = $container->getDefinition(PdfExports::class);
            foreach ($config['export']['pdf'] as $configName => $pdfConfig) {
                $pdfConfigs->addMethodCall('addConfig', [$configName, $pdfConfig]);
            }

            $xmlExports = $container->getDefinition(XmlExports::class);
            foreach ($config['export']['xml'] as $configName => $template) {
                $xmlExports->addMethodCall('addTemplate', [$configName, $template]);
            }
        }

        $docxParticipantList = $container->getDefinition(WordExport::class);
        $docxParticipantList->setArgument(2, $config['participant_list']['docx_template'] ?? null);
    }

    public function getAlias(): string
    {
        return 'ferienpass';
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('framework', [
            'mailer' => [
                'envelope' => [
                    'sender' => '%env(ADMIN_EMAIL)%',
                ],
            ],
        ]);

        // TODO only register if NOT customized
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'schema_ignore_classes' => [
                            Offer::class,
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'messenger.bus.default' => [
                        'middleware' => [
                            MessageLogMiddleware::class,
                            'doctrine_transaction',
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('symfonycasts_reset_password', [
            'request_password_repository' => ResetPasswordRequestRepository::class,
        ]);

        $this->prependWorkflow($container);
    }

    private function prependWorkflow(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'workflows' => [
                'offer' => [
                    'type' => 'state_machine',
                    'marking_store' => ['type' => 'method', 'property' => 'state'],
                    'supports' => [OfferEntityInterface::class],
                    'initial_marking' => OfferEntityInterface::STATE_DRAFT,
                    'places' => [OfferEntityInterface::STATE_DRAFT, OfferEntityInterface::STATE_COMPLETED, OfferEntityInterface::STATE_REVIEWED, OfferEntityInterface::STATE_PUBLISHED, OfferEntityInterface::STATE_CANCELLED, OfferEntityInterface::STATE_UNPUBLISHED],
                    'transitions' => [
                        OfferEntityInterface::TRANSITION_COMPLETE => ['from' => OfferEntityInterface::STATE_DRAFT, 'to' => OfferEntityInterface::STATE_COMPLETED],
                        OfferEntityInterface::TRANSITION_APPROVE => ['from' => [OfferEntityInterface::STATE_DRAFT, OfferEntityInterface::STATE_COMPLETED], 'to' => OfferEntityInterface::STATE_REVIEWED],
                        OfferEntityInterface::TRANSITION_UNAPPROVE => ['from' => [OfferEntityInterface::STATE_REVIEWED], 'to' => OfferEntityInterface::STATE_COMPLETED],
                        OfferEntityInterface::TRANSITION_PUBLISH => ['from' => [OfferEntityInterface::STATE_DRAFT, OfferEntityInterface::STATE_COMPLETED, OfferEntityInterface::STATE_REVIEWED, OfferEntityInterface::STATE_UNPUBLISHED], 'to' => OfferEntityInterface::STATE_PUBLISHED],
                        OfferEntityInterface::TRANSITION_CANCEL => ['from' => [OfferEntityInterface::STATE_PUBLISHED], 'to' => OfferEntityInterface::STATE_CANCELLED],
                        OfferEntityInterface::TRANSITION_RELAUNCH => ['from' => [OfferEntityInterface::STATE_CANCELLED], 'to' => OfferEntityInterface::STATE_PUBLISHED],
                        OfferEntityInterface::TRANSITION_UNPUBLISH => ['from' => [OfferEntityInterface::STATE_CANCELLED, OfferEntityInterface::STATE_PUBLISHED], 'to' => OfferEntityInterface::STATE_UNPUBLISHED],
                    ],
                ],
            ],
        ]);
    }
}
