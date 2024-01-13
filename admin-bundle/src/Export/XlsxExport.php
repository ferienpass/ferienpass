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

namespace Ferienpass\AdminBundle\Export;

use Contao\StringUtil;
use Doctrine\ORM\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class XlsxExport implements QueryBuilderExportInterface
{
    public function __construct(private readonly NormalizerInterface $normalizer, private readonly TranslatorInterface $translator)
    {
    }

    public function generate(QueryBuilder $qb, string $destination = null): string
    {
        $tmpPath = sys_get_temp_dir().'/xlsx';
        $xlsxPath = sprintf('%s/%s.xlsx', $tmpPath, random_int(0, 10000));

        $filesystem = new Filesystem();
        $filesystem->mkdir($tmpPath);

        $writer = new XlsxWriter($this->createSpreadsheet($qb));
        $writer->save($xlsxPath);

        if (null !== $destination) {
            $filesystem->copy($xlsxPath, $destination);
        }

        return $destination ?? $xlsxPath;
    }

    protected function createSpreadsheet(QueryBuilder $qb): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $sheet = $spreadsheet->getActiveSheet();

        $normalized = $this->normalizer->normalize($qb->getQuery()->getResult(), 'xlsx', [
            'groups' => 'admin_list',
            DateTimeNormalizer::FORMAT_KEY => 'd.m.Y H:i:s',
        ]);

        if (empty($normalized)) {
            return $spreadsheet;
        }

        $columns = array_keys($normalized[0]);

        // Header row
        $iCol = 1;
        foreach ($columns as $column) {
            $label = $this->translator->trans('export.xlsx.'.$column, domain: 'admin');

            $sheet->getStyle(CellAddress::fromColumnAndRow($iCol, 1))->getFont()->setBold(true);
            $sheet->getCell(Coordinate::stringFromColumnIndex($iCol). 1)->setValue($label);

            ++$iCol;
        }

        // Content rows
        $iRow = 2;
        foreach ($normalized as $row) {
            $iCol = 1;

            foreach ($columns as $column) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($iCol).$iRow);

                // TODO find a way to pass this to the context (without normalizing each again).
                $style = $sheet->getStyle(CellAddress::fromColumnAndRow($iCol, $iRow));

                $cell->setValue(StringUtil::decodeEntities($row[$column]));

                ++$iCol;
            }

            ++$iRow;
        }

        return $spreadsheet;
    }
}
