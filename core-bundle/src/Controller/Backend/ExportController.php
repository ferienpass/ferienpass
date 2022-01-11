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

namespace Ferienpass\CoreBundle\Controller\Backend;

use Contao\CoreBundle\Exception\ResponseException;
use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Export\Offer\Excel\ExcelExports;
use Ferienpass\CoreBundle\Export\Offer\PrintSheet\PdfExports;
use Ferienpass\CoreBundle\Export\Offer\Xml\XmlExports;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/export", name="backend_export")
 */
final class ExportController extends AbstractController
{
    public function __construct(private OfferRepository $offerRepository, private PdfExports $pdfExports, private ExcelExports $excelExports, private XmlExports $xmlExports)
    {
    }

    public function __invoke(Request $request): Response
    {
        $types = array_values(array_merge($this->pdfExports->getNames(), $this->excelExports->getNames(), $this->xmlExports->getNames()));
        $form = $this->createFormBuilder()
            ->add('type', ChoiceType::class, [
                'label' => 'Export',
                'choices' => array_combine($types, $types),
                'choice_label' => fn ($choice, $key, $value) => strtoupper($key),
            ])
            ->add('editions', EntityType::class, [
                'class' => Edition::class,
                'choice_label' => 'name',
                'label' => 'Ferienpässe',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('hosts', EntityType::class, [
                'class' => Host::class,
                'choice_label' => 'name',
                'required' => false,
                'label' => 'Veranstalter',
                'multiple' => true,
                'expanded' => false,
            ])
            ->add('published', CheckboxType::class, [
                'label' => 'nur veröffentlichte',
                'required' => false,
            ])
            ->add('export', SubmitType::class, ['label' => 'Export starten'])
            ->add('request_token', ContaoRequestTokenType::class)
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->get('type')->getData();
            $offers = $this->queryOffers($form);

            return $this->exportOffers($type, $offers);
        }

        return $this->renderForm('@FerienpassCore/Backend/be_export.html.twig', [
            'form' => $form,
        ]);
    }

    protected function checkToken(): void
    {
        $token = $this->container->get('security.token_storage')->getToken();
        if (null === $token || $this->get('security.authentication.trust_resolver')->isAnonymous($token)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }

        if (!$this->container->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw new ResponseException(new Response('Access Denied', Response::HTTP_UNAUTHORIZED));
        }
    }

    private function exportOffers(string $type, iterable $offers): BinaryFileResponse
    {
        if ($this->pdfExports->has($type)) {
            ini_set('pcre.backtrack_limit', '100000000');

            return $this->file($this->pdfExports->get($type)->generate($offers));
        }

        if ($this->excelExports->has($type)) {
            return $this->file($this->excelExports->get($type)->generate($offers));
        }

        if ($this->xmlExports->has($type)) {
            return $this->file($this->xmlExports->get($type)->generate($offers));
        }

        throw new \InvalidArgumentException(sprintf('Type "%s" is not supported', $type));
    }

    private function queryOffers(FormInterface $form): iterable
    {
        $qb = $this->offerRepository
            ->createQueryBuilder('offer')
            ->leftJoin('offer.dates', 'dates')
            ->orderBy('dates.begin', 'ASC')
        ;

        if ($form->get('published')->getData()) {
            $qb->andWhere('offer.published = 1');
        }

        if (($editions = $form->get('editions')->getData())
            && $editions instanceof Collection
            && $editions->count()) {
            /** @psalm-suppress QueryBuilderSetParameter */
            $qb->andWhere('offer.edition IN (:editions)')->setParameter('editions', $editions);
        }

        if (($hosts = $form->get('hosts')->getData())
            && $hosts instanceof Collection
            && $hosts->count()) {
            /** @psalm-suppress QueryBuilderSetParameter */
            $qb->innerJoin('offer.hosts', 'hosts')->andWhere('hosts.id IN (:hosts)')->setParameter('hosts', $hosts);
        }

        return $qb->getQuery()->getResult();
    }
}
