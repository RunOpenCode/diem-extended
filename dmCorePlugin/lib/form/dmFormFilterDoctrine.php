<?php

abstract class dmFormFilterDoctrine extends sfFormFilterDoctrine
{

  protected function setupNestedSet() {

    //die(print_r(get_class($this->getTable()),1));
    if ($this->getTable() instanceof myDoctrineTable) {

      // unset NestedSet columns not needed as added in the getAutoFieldsToUnset() method

      $this->updateNestedSetWidget($this->getTable(), 'nested_set_parent_id', 'Child of');

      // check all relations for NestedSet
      foreach ($this->getTable()->getRelationHolder()->getAll() as $relation) {
        if ($relation->getTable() instanceof dmDoctrineTable && $relation->getTable()->isNestedSet()) {
          // check for many to many
          $fieldname = $relation->getType()
                  ? dmString::underscore($relation->getAlias()) . '_list'
                  : $relation->getLocalColumnName()
                  ;
          $this->updateNestedSetWidget($relation->getTable(), $fieldname);
        }
      }
    }

  }

  protected function updateNestedSetWidget(dmDoctrineTable $table, $fieldname = null, $label = null)
  {
    if ($table->isNestedSet()) {
      if (null === $fieldname) {
        $fieldname = 'nested_set_parent_id';
      }
      // create if not exists
      if (!($this->widgetSchema[$fieldname] instanceof sfWidgetFormDoctrineChoice)) {
        $this->widgetSchema[$fieldname] = new sfWidgetFormDoctrineChoice(array('model' => $table->getComponentName()));
      }
      if (!($this->validatorSchema[$fieldname] instanceof sfValidatorDoctrineChoice)) {
        $this->validatorSchema[$fieldname] = new sfValidatorDoctrineChoice(array('model' => $table->getComponentName()));
      }

      if (null !== $label) {
        $this->widgetSchema[$fieldname]->setLabel('$label');
      }

      // set sorting
      $orderBy = 'lft';
      if ($table->getTemplate('NestedSet')->getOption('hasManyRoots', false)) {
        $orderBy = $table->getTemplate('NestedSet')->getOption('rootColumnName', 'root_id') . ', ' . $orderBy;
      }

      $this->widgetSchema[$fieldname]->setOptions(array_merge(
              $this->widgetSchema[$fieldname]->getOptions(),
              array(
                  'method' => 'getNestedSetIndentedName',
                  'order_by' => array($orderBy, ''),
        )));

    }
  }

  public function setup() {


    $this->setupNestedSet();

    parent::setup();

  }


  protected function mergeI18nForm($culture = null)
  {
    $this->mergeForm($this->createI18nForm());
  }

  public function isI18n()
  {
    return $this->getTable()->hasI18n();
  }

  /**
   * Create current i18n form
   */
  protected function createI18nForm($culture = null)
  {
    if (!$this->isI18n())
    {
      throw new dmException(sprintf('The model "%s" is not internationalized.', $this->getModelName()));
    }

    $i18nFormClass = $this->getI18nFormClass();

    $options = array();
    if($widgets = $this->getOption('widgets')) $options['widgets'] = $widgets;
    
    $i18nForm = new $i18nFormClass(array(), $options);

    unset($i18nForm['id'], $i18nForm['lang']);

    return $i18nForm;
  }

  protected function getI18nFormClass()
  {
    return $this->getTable()->getI18nTable()->getComponentName().'FormFilter';
  }

  protected function getRootAlias(Doctrine_Query $query, $fieldName)
  {
    return $this->getTable()->isI18nColumn($fieldName)
    ? $query->getRootAlias().'Translation'
    : $query->getRootAlias();
  }

  protected function addForeignKeyQuery(Doctrine_Query $query, $field, $value)
  {
    $fieldName = $this->getFieldName($field);

    if (is_array($value))
    {
      $query->andWhereIn(sprintf('%s.%s', $this->getRootAlias($query, $fieldName), $fieldName), $value);
    }
    else
    {
      $query->addWhere(sprintf('%s.%s = ?', $this->getRootAlias($query, $fieldName), $fieldName), $value);
    }
  }

  protected function addEnumQuery(Doctrine_Query $query, $field, $value)
  {
    $fieldName = $this->getFieldName($field);
	 $counter = 0;
	 $enumConditions='';
	 $rootAlias = $this->getRootAlias($query, $fieldName);
	 foreach ($value as $val) {
		 $counter++;
		 $glue = $counter < count($value) ? ' OR ' : '';
		 $enumConditions .= sprintf('%s.%s = ?'.$glue , $rootAlias, $fieldName);
	 }
    $query->addWhere($enumConditions, $value);
  }

  protected function addTextQuery(Doctrine_Query $query, $field, $values)
  {
    $fieldName = $this->getFieldName($field);

    if (is_array($values) && isset($values['is_empty']) && $values['is_empty'])
    {
      $query->addWhere(sprintf('(%s.%s IS NULL OR %1$s.%2$s = ?)', $this->getRootAlias($query, $fieldName), $fieldName), array(''));
    }
    else if (is_array($values) && isset($values['text']) && '' != $values['text'])
    {
      $query->addWhere(sprintf('%s.%s LIKE ?', $this->getRootAlias($query, $fieldName), $fieldName), '%'.$values['text'].'%');
    }
  }

  protected function addNumberQuery(Doctrine_Query $query, $field, $values)
  {
    if(!is_array($values))
    {
      $values = array('text' => $values);
    }

    $fieldName = $this->getFieldName($field);

    if (isset($values['is_empty']) && $values['is_empty'])
    {
      $query->addWhere(sprintf('(%s.%s IS NULL OR %1$s.%2$s = ?)', $this->getRootAlias($query, $fieldName), $fieldName), array(''));
    }
    else if (isset($values['text']) && '' !== $values['text'])
    {
      $query->addWhere(sprintf('%s.%s = ?', $this->getRootAlias($query, $fieldName), $fieldName), $values['text']);
    }
  }

  protected function addBooleanQuery(Doctrine_Query $query, $field, $value)
  {
    $fieldName = $this->getFieldName($field);
    $query->addWhere(sprintf('%s.%s = ?', $this->getRootAlias($query, $fieldName), $fieldName), $value);
  }

  protected function addDateQuery(Doctrine_Query $query, $field, $values)
  {
    if(is_array($values))
    {
      $fieldName = $this->getFieldName($field);
      if (isset($values['is_empty']) && $values['is_empty']) {
        $query->addWhere(sprintf('%s.%s IS NULL', $query->getRootAlias($query, $fieldName), $fieldName));
      } else {
        if (null !== $values['from'] && null !== $values['to']) {
          $query->andWhere(sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName), $values['from']);
          $query->andWhere(sprintf('%s.%s <= ?', $this->getRootAlias($query, $fieldName), $fieldName), $values['to']);
        } else if (null !== $values['from']) {
          $query->andWhere(sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName), $values['from']);
        } else if (null !== $values['to']) {
          $query->andWhere(sprintf('%s.%s <= ?', $this->getRootAlias($query, $fieldName), $fieldName), $values['to']);
        }
      }
    }
    else
    {
      $fieldName = $this->getFieldName($field);

      switch($values)
      {
        case null:
        case '':
          break;
        case 'today':
          $query->andWhere(
            sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName),
            date('Y-m-d H:i:s', strtotime('-1 day'))
          );
          break;
        case 'week':
          $query->andWhere(
            sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName),
            date('Y-m-d H:i:s', strtotime('-1 week'))
          );
          break;
        case 'month':
          $query->andWhere(
            sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName),
            date('Y-m-d H:i:s', strtotime('-1 month'))
          );
          break;
        case 'year':
          $query->andWhere(
            sprintf('%s.%s >= ?', $this->getRootAlias($query, $fieldName), $fieldName),
            date('Y-m-d H:i:s', strtotime('-1 year'))
          );
          break;
      }

    }
  }
}