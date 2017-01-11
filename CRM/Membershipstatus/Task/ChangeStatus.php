<?php

class CRM_Membershipstatus_Task_ChangeStatus extends CRM_Member_Form_Task {
  public $_single = FALSE;
  protected $_rows;
  protected $_pdfFormatID;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/contribute/search', $urlParams);
    $breadCrumb = array(
      array(
        'url' => $url,
        'title' => ts('Search Results'),
      )
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    // show the number of selected memberships
    $this->assign('totalSelectedMembers', count($this->_memberIds));
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle('Verander lidmaatschapstatus');

    // add help
    $this->assign('detailedInfo', 'Kies de nieuwe status voor de geselecteerde lidmaatschappen.');

    // add status id's
    $this->add('select', 'status_id', ts('Lidmaatschapstatus'), CRM_Member_PseudoConstant::membershipStatus());

    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => 'Wijzig',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'back',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public function postProcess() {
    // get the selected status id
    $statusID = CRM_Utils_Request::retrieve('status_id', 'Integer', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');

    // update the status
    foreach ($this->_memberIds as $memberID) {
      $params = array(
        'id' => $memberID,
        'status_id' => $statusID,
      );
      civicrm_api3('Membership', 'create', $params);
    }
  }
}

