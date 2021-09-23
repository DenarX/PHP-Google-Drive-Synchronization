<?php

/**
 * PHP Google Drive Synchronization using google/apiclient:^2.11
 * 
 * Comparison based on filename and modify date
 * @param string $syncDir path to synchronization folder on your server
 * @param string $googleDriveDir folder ID on Google Drive
 * @param string $credentials path to credentials.json
 * @author Denar
 * @link https://github.com/DenarX
 */
class sync
{
    /** string $syncDir path to synchronization folder on your server */
    protected $syncDir;
    protected $uploadedFiles = [];
    protected $googleDriveDir;
    protected $client;
    protected $service;
    static $r;

    function __construct($syncDir, $googleDriveDir, $credentials = 'credentials.json')
    {
        if (!class_exists('Google_Client')) throw new Exception("Google Api Client not found, for install run command: composer require google/apiclient:^2.11 ");
        if (!file_exists($syncDir)) throw new Exception("Synchronization directory not found, check 1st parameter");
        if (!file_exists($credentials)) throw new Exception("credentials.json not found, check 3rd parameter");
        $this->syncDir = $syncDir;
        $this->googleDriveDir = $googleDriveDir;
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets API PHP Quickstart');
        $this->client->setScopes(array('https://www.googleapis.com/auth/drive.file'));
        $this->client->setAccessType('offline');
        $this->client->setAuthConfig($credentials);
        $this->service = new Google_Service_Drive($this->client);
        $this->getFiles();
        $this->sync();
    }
    protected function getFiles()
    {
        $optParams = [
            'fields' => 'nextPageToken, files(id, name, modifiedTime)',
            'q' => "'{$this->googleDriveDir}' in parents"
        ];
        $files = $this->service->files->listFiles($optParams);
        foreach ($files as $file) {
            $this->uploadedFiles[$file->name] = [
                'id' => $file->id,
                'name' => $file->name,
                'modifiedTime' => strtotime($file->getModifiedTime()),
            ];
        }
    }
    protected function upload($name)
    {
        $file = new Google_Service_Drive_DriveFile([
            'name' => $name,
            'parents' => [$this->googleDriveDir],
        ]);
        $data = file_get_contents($this->syncDir . '/' . $name);
        $createdFile = $this->service->files->create($file, [
            'data' => $data,
            'uploadType' => 'multipart'
        ]);
        self::$r[$name]['success'] = !empty($createdFile->id);
    }
    protected function sync()
    {
        $dir = scandir($this->syncDir);
        foreach ($dir as $name) {
            if (in_array($name, [".", ".."])) continue;
            $file = $this->syncDir . '/' . $name;
            if (empty($this->uploadedFiles[$name])) {
                self::$r[$name]['message'] = 'uploaded';
                $this->upload($name);
            } elseif ($this->uploadedFiles[$name]['modifiedTime'] < filemtime($file)) {
                $this->service->files->delete($this->uploadedFiles[$name]['id']);
                self::$r[$name]['message'] = 'updated';
                $this->upload($name);
            }
        }
        self::$r['message'] = 'All files are up to date';
        self::$r['success'] = !in_array(false, array_column(self::$r, 'message'));;
    }
    /** 
     * Get sum result of synchronization
     * @param bool $json convert array in JSON
     * @return array|object
     * */
    static function result($json = false)
    {
        return $json ? json_encode(self::$r) : self::$r;
    }
    /** Google errors formatter */
    static function getError($t)
    {
        $str = $t->getMessage();
        $arr = json_decode($str, true);
        $arr = $arr['error']['code'] ?? $arr;
        if ($arr == 404) {
            $arr = "Invalid googleDriveFolderId, check 2nd parameter";
        }
        self::$r['error'] = $arr ? $arr : $str;
        self::$r['success'] = false;
    }
}
