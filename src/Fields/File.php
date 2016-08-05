<?php

namespace SimpleCrud\Fields;

use Psr\Http\Message\UploadedFileInterface;
use SimpleCrud\SimpleCrud;
use SimpleCrud\SimpleCrudException;
use SplFileInfo;

/**
 * To save files.
 */
class File extends Field
{
    protected $directory;

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if ($data instanceof UploadedFileInterface) {
            return $this->upload($data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        if (!empty($data)) {
            return new SplFileInfo($this->getDirectory().$data);
        }

        return $data;
    }

    /**
     * Upload the file and return the value.
     * 
     * @param UploadedFileInterface $file
     * 
     * @return string
     */
    private function upload(UploadedFileInterface $file)
    {
        $filename = $this->getFilename($file);
        $targetPath = $this->getDirectory();

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        $file->moveTo($targetPath.$filename);

        return $filename;
    }

    /**
     * Get the name used to save the file in lowercase and without spaces.
     * 
     * @param UploadedFilenameInterface $file
     * 
     * @return string
     */
    protected function getFilename(UploadedFileInterface $file)
    {
        $name = $file->getClientFilename();

        if ($name === '') {
            return uniqid();
        }

        return preg_replace(['/[^\w\.]/', '/[\-]{2,}/'], '-', strtolower($name));
    }

    /**
     * Get the directory where the file will be saved.
     * 
     * @return string
     */
    protected function getDirectory()
    {
        if ($this->directory === null) {
            $root = $this->table->getDatabase()->getAttribute(SimpleCrud::ATTR_UPLOADS);

            if (empty($root)) {
                throw new SimpleCrudException('No ATTR_UPLOADS attribute found to upload files');
            }

            return $this->directory = "{$root}/{$this->table->name}/{$this->name}/";
        }

        return $this->directory;
    }
}
