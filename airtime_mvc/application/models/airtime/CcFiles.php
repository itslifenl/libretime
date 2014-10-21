<?php

/**
 * Skeleton subclass for representing a row from the 'cc_files' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.campcaster
 */
class CcFiles extends BaseCcFiles {
	
    //fields we should never expose through our RESTful API
    private static $privateFields = array(
            'file_exists',
            'silan_check',
            'is_scheduled',
            'is_playlist'
    );
    
	public function getCueLength()
	{
		$cuein = $this->getDbCuein();
		$cueout = $this->getDbCueout();
		
		$cueinSec = Application_Common_DateHelper::calculateLengthInSeconds($cuein);
		$cueoutSec = Application_Common_DateHelper::calculateLengthInSeconds($cueout);
		$lengthSec = bcsub($cueoutSec, $cueinSec, 6);
		
		$length = Application_Common_DateHelper::secondsToPlaylistTime($lengthSec);
		
		return $length;
	}

    public function setDbTrackNumber($v)
    {
        $max = pow(2, 31)-1;
        $v = ($v > $max) ? $max : $v;

        return parent::setDbTrackNumber($v);
    }

    // returns true if the file exists and is not hidden
    public function visible() {
        return $this->getDbFileExists() && !$this->getDbHidden();
    }

    public function reassignTo($user) 
    {
        $this->setDbOwnerId( $user->getDbId() );
        $this->save();
    }

    /**
     *
     * Strips out the private fields we do not want to send back in API responses
     * @param $file a CcFiles object
     */
    //TODO: rename this function?
    public static function sanitizeResponse($file)
    {
        $response = $file->toArray(BasePeer::TYPE_FIELDNAME);
    
        foreach (self::$privateFields as $key) {
            unset($response[$key]);
        }
    
        return $response;
    }
    
    public function getFileSize()
    {
        return filesize($this->getAbsoluteFilePath());
    }
    
    public function getFilename()
    {
        $info = pathinfo($this->getAbsoluteFilePath());
        return $info['filename'];
    }
    
    public function getAbsoluteFilePath()
    {
        $music_dir = Application_Model_MusicDir::getDirByPK($this->getDbDirectory());
        if (!$music_dir) {
            throw new Exception("Invalid music_dir for file in database.");
        }
        $directory = $music_dir->getDirectory();
        $filepath  = $this->_file->getDbFilepath();

        return Application_Common_OsPath::join($directory, $filepath);
    }
    
    public function isValidFile()
    {
        return is_file($this->getAbsoluteFilePath());
    }
    
    /**
     * 
     * Deletes the file from the stor directory
     */
    public function deletePhysicalFile()
    {
        $filepath = $this->getAbsoluteFilePath();
        if (file_exists($filepath)) {
            unlink($filepath);
        } else {
            throw new Exception("Could not locate file ".$filepath);
        }
    }
    
} // CcFiles
