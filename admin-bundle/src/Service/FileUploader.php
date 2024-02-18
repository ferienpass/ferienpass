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

namespace Ferienpass\AdminBundle\Service;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(private readonly VirtualFilesystemInterface $storage, private readonly SluggerInterface $slugger)
    {
    }

    public function upload(UploadedFile $file): ?FilesystemItem
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $stream = fopen($file->getRealPath(), 'r+');
        $this->storage->writeStream($fileName, $stream);
        fclose($stream);

        dd($this->storage->listContents('')->toArray(), $this->storage->has($fileName), $fileName, $this->storage->get($fileName));

        return $this->storage->get($fileName);
    }
}
