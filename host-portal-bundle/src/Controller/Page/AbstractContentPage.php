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

namespace Ferienpass\HostPortalBundle\Controller\Page;

use Contao\ArticleModel;
use Ferienpass\HostPortalBundle\Controller\AbstractController;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Ferienpass\HostPortalBundle\Page\PageBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A base class for page controllers that have content composition enabled.
 * The articles will be added automatically to the page.
 */
class AbstractContentPage extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();

        $pageModel = $request->get('pageModel');

        $pageBuilder = $this->createPageBuilder($pageModel);

        $articles = ArticleModel::findPublishedByPidAndColumn($pageModel->id, 'main');
        while (null !== $articles && $articles->next()) {
            $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.article', ['id' => $articles->id]));
        }

        $this->modifyPage($pageBuilder);

        return $pageBuilder->getResponse();
    }

    protected function modifyPage(PageBuilder $pageBuilder): void
    {
    }
}
