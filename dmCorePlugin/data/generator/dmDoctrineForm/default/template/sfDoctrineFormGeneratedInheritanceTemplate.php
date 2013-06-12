[?php

/**
 * <?php echo $this->modelName ?> form base class.
 *
 * @method <?php echo $this->modelName ?> getObject() Returns the current form's model object
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id$
 * @generator  <?php echo 'Diem ', constant('DIEM_VERSION'), "\n"?>
 * @gen-file   <?php echo __FILE__?>
 */
abstract class Base<?php echo $this->modelName ?>Form extends <?php echo $this->getFormClassToExtend() . "\n" ?>
{
  protected function setupInheritance()
  {
    parent::setupInheritance();
<?php foreach((array)$this->getTable()->getOption('inheritanceMap') as $field => $value): ?>
		//column aggregation field
    unset($this['<?php echo $field ?>']);
<?php endforeach; ?>

<?php foreach ($this->getColumns(true, true, true) as $column): ?>
		//column
		if($this->needsWidget('<?php echo $column->getFieldName()?>')){
			$this->setWidget('<?php echo $column->getFieldName() ?>', new <?php echo $this->getWidgetClassForColumn($column) ?>(<?php echo $this->getWidgetOptionsForColumn($column) ?>));
			$this->setValidator('<?php echo $column->getFieldName() ?>', new <?php echo $this->getValidatorClassForColumn($column) ?>(<?php echo $this->getValidatorOptionsForColumn($column) ?>));
		}    
<?php endforeach; ?>
<?php foreach ($this->getManyToManyRelations() as $relation): ?>
		//many to many
		if($this->needsWidget('<?php echo $this->underscore($relation['alias']) ?>_list')){
			$this->setWidget('<?php echo $this->underscore($relation['alias']) ?>_list', new sfWidgetFormDmPaginatedDoctrineChoice(array('multiple' => true, 'model' => '<?php echo $relation['table']->getOption('name') ?>', 'expanded' => true)));
			$this->setValidator('<?php echo $this->underscore($relation['alias']) ?>_list', new sfValidatorDoctrineChoice(array('multiple' => true, 'model' => '<?php echo $relation['table']->getOption('name') ?>', 'required' => false)));
		}
<?php endforeach; ?>
<?php foreach($this->getOneToOneRelations() as $relation):?>
		//one to one
		if($this->needsWidget('<?php echo $this->underscore($relation['local']) ?>')){

            $this->setWidget('<?php echo $this->underscore($relation['local']) ?>', new <?php echo $this->getWidgetClassForColumn($relation instanceof Doctrine_Relation_LocalKey ? $relation : new dmDoctrineColumn($relation['local'], $relation['table'])) ?>(array('multiple' => false, 'model' => '<?php echo $relation['table']->getOption('name')?>', 'expanded' =>  <?php echo $this->table->isPaginatedColumn($relation['local']) ? 'true' : 'false'?>)));

			$this->setValidator('<?php echo $this->underscore($relation['local']) ?>', new sfValidatorDoctrineChoice(array('multiple' => false, 'model' => '<?php echo $relation['table']->getOption('name')?>', 'required' => <?php echo $this->table->getSfDoctrineColumn($relation['local'])->isNotNull() ? 'true' : 'false'?>)));
		}
<?php endforeach;?>

<?php foreach ($this->getOneToManyRelations() as $relation): ?><?php if($relation['alias'] === 'Translation') continue;?>
		//one to many
		if($this->needsWidget('<?php echo $this->underscore($relation['alias']) ?>_list')){
			$this->setWidget('<?php echo $this->underscore($relation['alias']) ?>_list', new sfWidgetFormDmPaginatedDoctrineChoice(array('multiple' => true, 'model' => '<?php echo $relation['table']->getOption('name') ?>', 'expanded' => true)));
			$this->setValidator('<?php echo $this->underscore($relation['alias']) ?>_list', new sfValidatorDoctrineChoice(array('multiple' => true, 'model' => '<?php echo $relation['table']->getOption('name') ?>', 'required' => false)));
		}
<?php endforeach; ?>

<?php foreach($this->getMediaRelations() as $mediaRelation): ?><?php if($mediaRelation['localTable'] && $mediaRelation['localTable']->isGenerator()) continue;?>

    /*
     * Embed Media form for <?php echo $mediaRelation['local']."\n"; ?>
     */
    if($this->needsWidget('<?php echo $mediaRelation['local']?>')){
      $this->embedForm('<?php echo $mediaRelation['local'].'_form' ?>', $this->createMediaFormFor<?php echo dmString::camelize($mediaRelation['local']); ?>());
      unset($this['<?php echo $mediaRelation['local']; ?>']);
    }
<?php endforeach; ?>

    $this->widgetSchema->setNameFormat('<?php echo $this->underscore($this->modelName) ?>[%s]');
  }

<?php foreach($this->getMediaRelations() as $mediaRelation): ?><?php if($mediaRelation['localTable'] && $mediaRelation['localTable']->isGenerator()) continue;?>
  /**
   * Creates a DmMediaForm instance for <?php echo $mediaRelation['local']."\n"; ?>
   *
   * @return DmMediaForm a form instance for the related media
   */
  protected function createMediaFormFor<?php echo dmString::camelize($mediaRelation['local']); ?>()
  {
    return DmMediaForRecordForm::factory($this->object, '<?php echo $mediaRelation['local'] ?>', '<?php echo $mediaRelation['alias'] ?>', $this->validatorSchema['<?php echo $mediaRelation['local']; ?>']->getOption('required'), $this);
  }
<?php endforeach; ?>

  protected function doBind(array $values)
  {
<?php foreach($this->getMediaRelations() as $mediaRelation): ?><?php if($mediaRelation['localTable'] && $mediaRelation['localTable']->isGenerator()) continue;?>
    $values = $this->filterValuesByEmbeddedMediaForm($values, '<?php echo $mediaRelation['local'] ?>');
<?php endforeach; ?>
    parent::doBind($values);
  }

  public function processValues($values)
  {
    $values = parent::processValues($values);
<?php foreach($this->getMediaRelations() as $mediaRelation): ?><?php if($mediaRelation['localTable'] && $mediaRelation['localTable']->isGenerator()) continue;?>
    $values = $this->processValuesForEmbeddedMediaForm($values, '<?php echo $mediaRelation['local'] ?>');
<?php endforeach; ?>
    return $values;
  }
  
  protected function doUpdateObject($values)
  {
    parent::doUpdateObject($values);
<?php foreach($this->getMediaRelations() as $mediaRelation): ?><?php if($mediaRelation['localTable'] && $mediaRelation['localTable']->isGenerator()) continue;?>
    $this->doUpdateObjectForEmbeddedMediaForm($values, '<?php echo $mediaRelation['local'] ?>', '<?php echo $mediaRelation['alias'] ?>');
<?php endforeach; ?>
  }

  public function getModelName()
  {
    return '<?php echo $this->modelName ?>';
  }

<?php if ($this->getManyToManyRelations()): ?>
  public function updateDefaultsFromObject()
  {
    parent::updateDefaultsFromObject();

<?php foreach ($this->getManyToManyRelations() as $relation): ?>
    if (isset($this->widgetSchema['<?php echo $this->underscore($relation['alias']) ?>_list']))
    {
      $this->setDefault('<?php echo $this->underscore($relation['alias']) ?>_list', array_merge((array)$this->getDefault('<?php echo $this->underscore($relation['alias']) ?>_list'),$this->object-><?php echo $relation['alias']; ?>->getPrimaryKeys()));
    }

<?php endforeach; ?>
  }

  protected function doSave($con = null)
  {
<?php foreach ($this->getManyToManyRelations() as $relation): ?>
    $this->save<?php echo $relation['alias'] ?>List($con);
<?php endforeach; ?>

    parent::doSave($con);
  }

<?php foreach ($this->getManyToManyRelations() as $relation): ?>
  public function save<?php echo $relation['alias'] ?>List($con = null)
  {
    if (!$this->isValid())
    {
      throw $this->getErrorSchema();
    }

    if (!isset($this->widgetSchema['<?php echo $this->underscore($relation['alias']) ?>_list']))
    {
      // somebody has unset this widget
      return;
    }

    if (null === $con)
    {
      $con = $this->getConnection();
    }

    $existing = $this->object-><?php echo $relation['alias']; ?>->getPrimaryKeys();
    $values = $this->getValue('<?php echo $this->underscore($relation['alias']) ?>_list');
    if (!is_array($values))
    {
      $values = array();
    }

    $unlink = array_diff($existing, $values);
    if (count($unlink))
    {
      $this->object->unlink('<?php echo $relation['alias'] ?>', array_values($unlink));
    }

    $link = array_diff($values, $existing);
    if (count($link))
    {
      $this->object->link('<?php echo $relation['alias'] ?>', array_values($link));
    }
  }

<?php endforeach; ?>
<?php endif; ?>
}
