<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;
use Zend\Diactoros\UploadedFile;

class UploadTest extends PHPUnit_Framework_TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(new PDO('sqlite::memory:'));

        $this->db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "file" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT,
    `file`        TEXT
);
EOT
            );
        });
    }

    public function testUpload()
    {
        $db = $this->db;
        $db->setAttribute(SimpleCrud::ATTR_UPLOADS, __DIR__.'/tmp');

        $content = 'New file content';
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);

        $file = $db->file->create([
            'name' => 'New file',
            'file' => new UploadedFile($stream, strlen($content), UPLOAD_ERR_OK, 'My file.txt'),
        ]);

        $file->save();

        $this->assertEquals('New file', $db->file[1]->name);
        $this->assertEquals('/file/file/my-file.txt', $db->file[1]->file);

        $this->assertTrue(is_file(__DIR__.'/tmp/file/file/my-file.txt'));
        $this->assertEquals($content, file_get_contents(__DIR__.'/tmp/file/file/my-file.txt'));

        unlink(__DIR__.'/tmp/file/file/my-file.txt');
        rmdir(__DIR__.'/tmp/file/file');
        rmdir(__DIR__.'/tmp/file');
        rmdir(__DIR__.'/tmp');
    }
}
