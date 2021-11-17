<?php

use CRM_Membershipstatus_ExtensionUtil as E;

class CRM_Membershipstatus_Form_ClearPO extends CRM_Core_Form {
  public function buildQuickForm() {

    $this->addYesNo('are_you_sure', 'Hiermee ga je alle PO-nummers bij de lidmaatschappen wissen. Ben je zeker?', FALSE, TRUE);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    if ($values['are_you_sure'] == 1) {
      CRM_Core_DAO::executeQuery('update civicrm_value_lidmaatschap__35 set po_nummer_134 = NULL');
      CRM_Core_Session::setStatus('', 'Alle PO-nummers zijn gewist.','success');
    }

    parent::postProcess();
  }

  public function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
