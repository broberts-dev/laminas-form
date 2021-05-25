<?php

namespace LaminasTest\Form\Integration\TestAsset;

use Laminas\Form\Form as BaseForm;

class Form extends BaseForm
{
    /** @param null|FormElementManager */
    public $elementManagerAtInit;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->elementManagerAtInit = $this->getFormFactory()->getFormElementManager();
    }
}
