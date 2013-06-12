<?php

/**
 * Install Diem
 */
class dmSetupTask extends dmContextTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    parent::configure();

    $this->addOptions(array(
      new sfCommandOption('clear-db', null, sfCommandOption::PARAMETER_NONE, 'Drop database ( all data will be lost )'),
      new sfCommandOption('clear-tables', null, sfCommandOption::PARAMETER_NONE, 'used in conjunction with --clear-db, it will drop tables instead of db'),
      new sfCommandOption('no-confirmation', null, sfCommandOption::PARAMETER_NONE, 'Whether to force dropping of the database'),
      new sfCommandOption('load-doctrine-data', 'd', sfCommandOption::PARAMETER_NONE, 'Run dm:data with -l option'),
      new sfCommandOption('dont-load-data', 'n', sfCommandOption::PARAMETER_NONE, 'Do not load data'),
    ));

    $this->namespace = 'dm';
    $this->name = 'setup';
    $this->briefDescription = 'Safely setup a project. Can be run several times without side effect.';

    $this->detailedDescription = <<<EOF
Will create symlinks in your web directory,
Build models, forms and filters,
Load data,
generate missing admin modules...
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->logSection('Diem Extended', 'Setup '.dmProject::getKey());
    
    $this->dispatcher->notify(new sfEvent($this, 'dm.setup.before', array('clear-db' => $options['clear-db'])));
    
    // don't use cache:clear task because it changes current app & environment
    @sfToolkit::clearDirectory(sfConfig::get('sf_cache_dir'));

    if(!file_exists(dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineRecord.php'))){
      $this->getContext()->get('filesystem')->copy(
        dmOs::join(sfConfig::get('dm_core_dir'), 'data', 'skeleton', 'lib', 'model', 'doctrine', 'myDoctrineRecord.php'), 
        dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineRecord.php')
      );
    }

    if(!file_exists(dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineQuery.php'))){
      $this->getContext()->get('filesystem')->copy(
        dmOs::join(sfConfig::get('dm_core_dir'), 'data', 'skeleton', 'lib', 'model', 'doctrine', 'myDoctrineQuery.php'),
        dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineQuery.php')
      );
    }

    if(!file_exists(dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineTable.php'))){
      $this->getContext()->get('filesystem')->copy(
        dmOs::join(sfConfig::get('dm_core_dir'), 'data', 'skeleton', 'lib', 'model', 'doctrine', 'myDoctrineTable.php'),
        dmOs::join(sfConfig::get('sf_root_dir'), 'lib', 'model', 'doctrine', 'myDoctrineTable.php')
      );
    }

    $this->runTask('doctrine:build', array(), array('model' => true));

    if (( $options['clear-db'] || $options['clear-tables']) || $this->isProjectLocked())
    {
      $this->reloadAutoload();
      if($options['clear-db'])
      {
      	$this->runTask('doctrine:drop-db', array(), array('env' => $options['env'], 'no-confirmation' => dmArray::get($options, 'no-confirmation', false)));
      }else{
    		$this->runTask('dm:drop-tables', array(), array('env' => $options['env']));
      }

      if ($options['clear-db'] && $ret = $this->runTask('doctrine:build-db', array(), array('env' => $options['env'])))
      {
        return $ret;
      }
      
      $this->runTask('doctrine:build-sql', array(), array('env' => $options['env']));
    
      $this->runTask('doctrine:insert-sql', array(), array('env' => $options['env']));
    }
    else
    {
      $this->runTask('dm:upgrade', array(), array('env' => $options['env']));
    }
    
    $this->reloadAutoload();
    
    $this->withDatabase();
    
    $this->runTask('dm:clear-cache', array(), array('env' => $options['env']));
    
    $this->getContext()->reloadModuleManager();
    
    $this->runTask('doctrine:build-forms', array(), array('generator-class' => 'dmDoctrineFormGenerator'));
    
    $this->runTask('doctrine:build-filters', array(), array('generator-class' => 'dmDoctrineFormFilterGenerator'));

    $this->runTask('dm:publish-assets');

    $this->runTask('dm:clear-cache', array(), array('env' => $options['env']));
    
    $this->reloadAutoload();
    
    $this->getContext()->reloadModuleManager();

    $this->runTask('dmAdmin:generate', array(), array('env' => $options['env']));
    
    if(!$options['dont-load-data'])
    {
    	$this->runTask('dm:data', array(), array('load-doctrine-data' => $options['load-doctrine-data'], 'env' => $options['env']));
    }

    $this->logSection('Diem Extended', 'generate front modules');
    if (!$return = $this->context->get('filesystem')->sf('dmFront:generate --env=' . dmArray::get($options, 'env', 'dev')))
    {
      $this->logBlock(array(
        'Can\'t run dmFront:generate: '.$this->context->get('filesystem')->getLastExec('output'),
        'Please run "php symfony dmFront:generate" manually to generate front templates'
      ), 'ERROR');
    }
    
    $this->runTask('dm:permissions');
    
    // fix db file permissions
    if ('Sqlite' === Doctrine_Manager::connection()->getDriverName())
    {
      $this->filesystem->chmod(sfConfig::get('sf_data_dir'), 0777, 000);
    }
    
    $this->runTask('dm:clear-cache', array(), array('env' => $options['env']));
    
    $this->dispatcher->notify(new sfEvent($this, 'dm.setup.after', array('clear-db' => $options['clear-db'])));
    
    $this->logBlock('Setup successful', 'INFO_LARGE');
    
    $this->unlockProject();
  }
  
  protected function migrate()
  {
    throw new dmException('Disabled');
    
    switch($migrateResponse = $this->runTask('dm:generate-migration'))
    {
      case dmGenerateMigrationTask::UP_TO_DATE:
        break;

      case dmGenerateMigrationTask::DIFF_GENERATED:
        $this->logBlock('New doctrine migration classes have been generated', 'INFO_LARGE');
        $this->logSection('Diem Extended', 'You should check them in /lib/migration/doctrine,');
        $this->logSection('Diem Extended', 'Then decide if you want to apply changes.');
        
        if ($this->askConfirmation('Apply migration changes ? (y/N)', 'QUESTION', false))
        {
          $this->runTask('dm:clear-cache'); // load the new migration classes
          
          $migrationSuccess = 0 === $this->runTask('doctrine:migrate');
          
          if (!$migrationSuccess)
          {
            $this->logBlock('Can not apply migration changes', 'ERROR');
          }
        }
        
        if(empty($migrationSuccess))
        {
          if (!$this->askConfirmation('Continue the setup ? (y/N)', 'QUESTION', false))
          {
            $this->logSection('Diem Extended', 'Setup aborted.');
            exit;
          }
        }
        break;

      default:
        throw new dmException('Unexpected case : '.$migrateResponse);
    }
  }
  
  protected function unlockProject()
  {
    if ($this->isProjectLocked())
    {
      $this->getFilesystem()->remove(dmOs::join(sfConfig::get('dm_data_dir'), 'lock'));
      
      $password = Doctrine_Core::getConnectionByTableName('DmPage')->getOption('password');
      
      $this->logBlock('Your project is now ready for web access. See you on admin_dev.php.', 'INFO_LARGE');
      $this->logBlock('Your username is "admin" and your password is '.(empty($password) ? '"admin"' : 'the database password'), 'INFO_LARGE');
    }
  }
  
  protected function isProjectLocked()
  {
    return file_exists(dmOs::join(sfConfig::get('dm_data_dir'), 'lock'));
  }
  
  protected function projectHasModels()
  {
    return 0 !== count(sfYaml::load(file_get_contents(dmProject::rootify('config/doctrine/schema.yml'))));
  }
}
