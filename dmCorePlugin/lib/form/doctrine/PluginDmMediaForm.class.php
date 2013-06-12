<?php

/**
 * PluginDmMedia form.
 *
 * @package    form
 * @subpackage DmMedia
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 6174 2007-11-27 06:22:40Z fabien $
 */
abstract class PluginDmMediaForm extends BaseDmMediaForm
{
    protected $isFileRequired = true;

    public function setup()
	{
		parent::setup();
		$this->mergeI18nForm();
		$this->setUseFields();

		$this->widgetSchema['file'] = new sfWidgetFormDmInputFile();
		$this->validatorSchema['file'] = new sfValidatorFile(array(
            'required' => false
		));

		$this->changeToHidden('dm_media_folder_id');

		$this->mergePostValidator(new sfValidatorCallback(array('callback' => array($this, 'clearName'))));
		$this->mergePostValidator(new sfValidatorCallback(array('callback' => array($this, 'checkFolder'))));
        $this->mergePostValidator(new sfValidatorCallback(array('callback' => array($this, 'checkFile'))));

		if(false !== $mimeTypes = $this->getOption('mime_types', false))
		{
			$this->setMimeTypeWhiteList($mimeTypes);
		}
		elseif (false !== $mimeTypes = sfConfig::get('dm_media_mime_type_whitelist',false))
		{
			if (!dmContext::getInstance()->getUser()->can('media_ignore_whitelist'))
			{
				$this->setMimeTypeWhiteList($mimeTypes);
			}
		}
	}

	public function setMimeTypeWhiteList($mimeTypes)
	{
		$this->validatorSchema['file']->setOption('mime_types', $mimeTypes);
	}

	protected function doUpdateObject($values)
	{
		if (isset($values['file']) && $values['file'] instanceof sfValidatedFile)
		{
			$validatedFile = $values['file'];
		}
		else
		{
			$validatedFile = null;
		}

		unset($values['file']);

		if($this->object->exists() && $values['dm_media_folder_id'] != $this->object->dm_media_folder_id)
		{
			$moveToFolderId = $values['dm_media_folder_id'];
			$values['dm_media_folder_id'] = $this->object->dm_media_folder_id;
		}

		parent::doUpdateObject($values);

		if ($validatedFile)
		{
			$values = $this->handleValidatedFile($validatedFile, $values);
		}

		if(isset($moveToFolderId))
		{
			$this->object->move(dmDb::table('DmMediaFolder')->find($moveToFolderId));
		}
	}

	/*
	 * By default, when a file is uploaded
	 * 1. If media is new, create the file
	 * 2. If media already exists with another file, keep the media and replace the file
	 */
	protected function handleValidatedFile(sfValidatedFile $file, array $values)
	{
		if ($this->object->isNew())
		{
			if (!$this->object->create($file))
			{
				throw new dmException(sprintf('Can not create file for media %s', $this->object));
			}
		}
		else
		{
			if (!$this->object->replaceFile($file))
			{
				throw new dmException(sprintf('Can not replace file for media %s', $object));
			}
		}

		return $values;
	}

	public function clearName($validator, $values)
	{
		$inError = false;
		if (!empty($values['file']))
		{
			if(!$this->isValidFilenameAndExtension($values['file']->getOriginalName()))
			{
				$error = new sfValidatorError($validator, 'This is a bad name');

				// throw an error bound to the password field
				throw new sfValidatorErrorSchema($validator, array('file' => $error));
			}
		}
		return $values;
	}

	/**
	 * Used by ->clearnName() to validate filename.
	 * @param string $filename
	 */
	public function isValidFilenameAndExtension($filename)
	{
		$filenameAndExt = explode('.', dmOs::sanitizeFileName($filename));
		if(count($filenameAndExt) < 2) return false;
		return true;
	}

	public function checkFolder($validator, $values)
	{
		if (!empty($values['file']))
		{
			if(!$folder = dmDb::table('DmMediaFolder')->find($values['dm_media_folder_id']))
			{
				throw new dmException('media has no folder');
			}

			if(!is_dir($folder->fullPath))
			{
				if (!$this->getService('filesystem')->mkdir($folder->fullPath))
				{
					$error = new sfValidatorError($validator, dmProject::unRootify($folder->fullPath).' is not a directory');

					throw new sfValidatorErrorSchema($validator, array('file' => $error));
				}
			}

			if(!is_writable($folder->fullPath))
			{
				$error = new sfValidatorError($validator, dmProject::unRootify($folder->fullPath).' is not writable');

				// throw an error bound to the file field
				throw new sfValidatorErrorSchema($validator, array('file' => $error));
			}
		}

		return $values;
	}
	
    public function checkFile($validator, $values) 
    {
        if (trim($values['id']) == '' && empty($values['file']) && $this->isFileRequired()) {
            throw new sfValidatorError($validator, 'The file is required.');
        }
        return $values;
    }
    
    public function isFileRequired() {
        return (bool) $this->isFileRequired;
    }
    
	protected function setUseFields()
	{
		$this->useFields($this->getUseFields());
	}
	
	protected function getUseFields()
	{
		return array('dm_media_folder_id', 'file', 'legend', 'author', 'license');
	}
    
    public function configureRequired($required)
    {
        $this->isFileRequired = (bool) $required;
    }
}