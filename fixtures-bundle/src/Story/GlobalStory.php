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

namespace Ferienpass\FixturesBundle\Story;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\LayoutModel;
use Contao\MemberGroupModel;
use Contao\Model;
use Contao\PageModel;
use Contao\ThemeModel;
use Contao\UserModel;
use Zenstruck\Foundry\Story;

class GlobalStory extends Story
{
    public function build(): void
    {
        $theme = (new ThemeModel())->setRow([
            'name' => 'Ferienpass',
            'author' => 'ferienpass.online',
        ])->save();

        $adminUser = (new UserModel())->setRow([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.org',
            'language' => 'de',
            'password' => 'password',
            'admin' => '1',
        ])->save();

        (new MemberGroupModel())->setRow(['name' => 'Veranstalter:innen',])->save();

        $memberGroup = (new MemberGroupModel())->setRow(['name' => 'Eltern'])->save();

        $layout = (new LayoutModel())->setRow(['name' => 'Default layout', 'pid' => $theme->id, 'modules'=>'a:1:{i:0;a:3:{s:3:"mod";s:1:"0";s:3:"col";s:4:"main";s:6:"enable";s:1:"1";}}'])->save();

        $rootPage = (new PageModel())->setRow([
            'title' => 'Ferienpass Demo',
            'type' => 'root',
            'language' => 'de',
            'fallback' => '1',
            'useSSL' => '1',
            'includeLayout' => '1',
            'layout' => $layout->id,
            'published' => '1',
            'useFolderUrl' => '1',
        ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Startseite',
            'type' => 'regular',
            'alias' => 'index',
            'published' => '1',
        ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Account gelöscht',
            'type' => 'account_deleted',
            'alias' => 'account_deleted',
            'published' => '1',
        ])->save();

        (new PageModel())
            ->setRow([
                'pid' => $rootPage->id,
                'title' => 'Angebote',
                'type' => 'offer_list',
                'alias' => 'angebote',
                'published' => '1',
            ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Meine Anmeldungen',
            'type' => 'regular',
            'alias' => 'anmeldungen',
            'protected' => '1',
            'groups' => [$memberGroup->id],
            'published' => '1',
        ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Meine Kinder & Daten',
            'type' => 'regular',
            'alias' => 'meine-daten',
            'protected' => '1',
            'groups' => [$memberGroup->id],
            'published' => '1',
        ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Impressum',
            'type' => 'regular',
            'alias' => 'impressum',
            'published' => '1',
        ])->save();

        (new PageModel())->setRow([
            'pid' => $rootPage->id,
            'title' => 'Datenschutz',
            'type' => 'regular',
            'alias' => 'datenschutz',
            'published' => '1',
        ])->save();

        // Veranstalter
        $layout = (new LayoutModel())->setRow(['name' => 'Default layout', 'pid' => $theme->id, 'modules'=>'a:1:{i:0;a:3:{s:3:"mod";s:1:"0";s:3:"col";s:4:"main";s:6:"enable";s:1:"1";}}'])->save();

        $rootPage = (new PageModel())->setRow([
            'title' => 'Ferienpass Demo – Veranstalter:innen-Portal',
            'type' => 'root',
            'language' => 'de',
            'fallback' => '1',
            'useSSL' => '1',
            'includeLayout' => '1',
            'layout' => $layout->id,
            'published' => '1',
            'useFolderUrl' => '1',
        ])->save();
    }
}
