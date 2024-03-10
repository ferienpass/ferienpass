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

namespace Ferienpass\CmsBundle\Components\SignIn;

use Ferienpass\CmsBundle\Form\UserLoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Login extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function targetPath(Request $request): string
    {
        // If the form was submitted and the credentials were wrong, take the target
        // path from the submitted data as otherwise it would take the current page
        if ($request->isMethod('POST') && $request->request->has('_target_path')) {
            $targetPath = base64_decode((string) $request->request->get('_target_path'), true);
        } elseif ($request->query->has('redirect')) {
            // We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
            if ($this->container->get('uri_signer')->checkRequest($request)) {
                $targetPath = $request->query->get('redirect');
            }
        }

        if (null === ($targetPath ?? null)) {
            $targetPath = $request->getSchemeAndHttpHost().$request->getRequestUri();
        }

        return $targetPath ?? '';
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UserLoginType::class);
    }
}
