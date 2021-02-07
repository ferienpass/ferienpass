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

namespace Ferienpass\HostPortalBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ContentElement("host_headline", category="ferienpass")
 */
final class HeadlineController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        $headline = StringUtil::deserialize($model->headline);

        return $this->render('@FerienpassHostPortal/fragment/headline.html.twig', [
            'headline' => \is_array($headline) ? $headline['value'] : $headline,
            'hl' => \is_array($headline) ? $headline['unit'] : 'h1',
            'teaser' => $model->text,
        ]);
    }
}
