<?php

/*
 * Overrides sfWidgetFormDateRange in getStylesheets function
 */
class sfWidgetFormDmDateRange extends sfWidgetFormDateRange
{
    public function getStylesheets()
    {
        return array_merge($this->getOption('from_date')->getStylesheets(), $this->getOption('to_date')->getStylesheets());
    }
}
