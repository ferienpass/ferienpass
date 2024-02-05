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

use Contao\CoreBundle\DataContainer\PaletteManipulator;

(new PaletteManipulator())
    ->removeField('alias')
    ->removeField('ajax')
    ->removeField('allowTags')
    ->removeField('storeValues')
    ->removeField('customTpl')
    ->removeField('method')
    ->removeField('novalidate')
    ->removeField('attributes')
    ->removeField('formID')
    ->applyToPalette('default', 'tl_form')
;

unset(
    $GLOBALS['TL_DCA']['tl_form']['fields']['sendViaEmail']['filter'],
    $GLOBALS['TL_DCA']['tl_form']['fields']['skipEmpty']['filter'],
    $GLOBALS['TL_DCA']['tl_form']['fields']['storeValues']['filter'],
    $GLOBALS['TL_DCA']['tl_form']['fields']['method']['filter'],
    $GLOBALS['TL_DCA']['tl_form']['fields']['ajax']['filter'],
    $GLOBALS['TL_DCA']['tl_form']['fields']['allowTags']['filter'],
);
