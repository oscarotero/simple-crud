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
    protected static $chars = [
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'À' => 'a', 'Á' => 'a', 'Ả' => 'a', 'Ã' => 'a', 'Ạ' => 'a',
        'Ă' => 'a', 'Ằ' => 'a', 'Ắ' => 'a', 'Ẳ' => 'a', 'Ẵ' => 'a', 'Ặ' => 'a',
        'Â' => 'a', 'Ầ' => 'a', 'Ấ' => 'a', 'Ẩ' => 'a', 'Ẫ' => 'a', 'Ậ' => 'a',
        'đ' => 'd', 'Đ' => 'd',
        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'È' => 'e', 'É' => 'e', 'Ẻ' => 'e', 'Ẽ' => 'e', 'Ẹ' => 'e',
        'Ê' => 'e', 'Ề' => 'e', 'Ế' => 'e', 'Ể' => 'e', 'Ễ' => 'e', 'Ệ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'Ì' => 'i', 'Í' => 'i', 'Ỉ' => 'i', 'Ĩ' => 'i', 'Ị' => 'i',
        'ñ' => 'n',
        'Ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'Ò' => 'o', 'Ó' => 'o', 'Ỏ' => 'o', 'Õ' => 'o', 'Ọ' => 'o',
        'Ô' => 'o', 'Ồ' => 'o', 'Ố' => 'o', 'Ổ' => 'o', 'Ỗ' => 'o', 'Ộ' => 'o',
        'Ơ' => 'o', 'Ờ' => 'o', 'Ớ' => 'o', 'Ở' => 'o', 'Ỡ' => 'o', 'Ợ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'Ù' => 'u', 'Ú' => 'u', 'Ủ' => 'u', 'Ũ' => 'u', 'Ụ' => 'u',
        'Ư' => 'u', 'Ừ' => 'u', 'Ứ' => 'u', 'Ử' => 'u', 'Ữ' => 'u', 'Ự' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'Y' => 'y', 'Ỳ' => 'y', 'Ý' => 'y', 'Ỷ' => 'y', 'Ỹ' => 'y', 'Ỵ' => 'y',
    ];

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if ($data instanceof UploadedFileInterface) {
            return $this->upload($data);
        }

        if ($data instanceof SplFileInfo) {
            return $data->getFilename();
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

        return preg_replace(['/[^\w\.]/', '/[\-]{2,}/'], '-', trim(strtolower(strtr($name, static::$chars))));
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
