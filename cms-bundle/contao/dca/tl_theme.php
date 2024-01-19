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

$GLOBALS['TL_DCA']['tl_theme']['config']['closed'] = true;

unset(
    $GLOBALS['TL_DCA']['tl_theme']['global_operations']['importTheme'],
    $GLOBALS['TL_DCA']['tl_theme']['global_operations']['store'],
    $GLOBALS['TL_DCA']['tl_theme']['operations']['edit'],
    $GLOBALS['TL_DCA']['tl_theme']['operations']['delete'],
    $GLOBALS['TL_DCA']['tl_theme']['operations']['show'],
    $GLOBALS['TL_DCA']['tl_theme']['operations']['imageSizes'],
    $GLOBALS['TL_DCA']['tl_theme']['operations']['exportTheme']
);
