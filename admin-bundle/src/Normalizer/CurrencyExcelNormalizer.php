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

namespace Ferienpass\AdminBundle\Normalizer;

use Ferienpass\CoreBundle\Dto\Currency;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CurrencyExcelNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        /** @var Currency $object */
        $object = $object->getAmount() / $object->getDivisor();

        /** @var Style $style */
        if ($style = ($context['xlsx_cell_style'] ?? null)) {
            $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR);
        }

        return $object;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = [])
    {
        return $data instanceof Currency && 'xlsx' === $format;
    }
}
