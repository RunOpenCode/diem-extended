  public function executeFilter(sfWebRequest $request)
  {
    $this->setPage(1);

    if ($request->hasParameter('_reset'))
    {
      $this->setFilters($this->configuration->getFilterDefaults());

      $this->redirect('@<?php echo $this->getUrlForAction('list') ?>');
    }

    $this->filters = $this->configuration->getFilterForm($this->getFilters());

    $this->filters->bind($request->getParameter($this->filters->getName()));
    if ($this->filters->isValid())
    {
      $this->setFilters($this->filters->getValues());

      $this->redirect('@<?php echo $this->getUrlForAction('list') ?>');
    }

    $this->pager = $this->getPager();
    $this->sort = $this->getSort();
    
    $this->setTemplate('index');
  }

  public function executeShowFilters(sfWebRequest $request)
  {
    $helper = $this->getService('helper');
    $form = $this->configuration->getFilterForm($this->getFilters());
    return $this->renderAsync(array(
        'html' => $helper->renderPartial($this->getModuleName(), 'filters', array(
                        'configuration' => $this->configuration,
                        'form' => $form
                    )),
         'js' => $form->getJavascripts(),
         'css' => $form->getStylesheets()
    ), true);
  }