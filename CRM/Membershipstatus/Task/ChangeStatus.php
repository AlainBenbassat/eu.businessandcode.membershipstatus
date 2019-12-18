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
    $this->assign('detailedInfo', 'Kies de nieuwe status voor de geselecteerde lidmaatschappen en de gewenste einddatum.<br><br>Het lidmaatschap wordt dan bijgewerkt EN er wordt een bijdrage gemaakt op datum van 1 januari in het gekozen jaar.');

    // add form fields
    $formItems = [];
    $defaults = [];

    // membership status
    $memberShipStatus = CRM_Member_PseudoConstant::membershipStatus();
    $this->add('select', 'membership_status_id', ts('Lidmaatschapstatus'), $memberShipStatus, TRUE, FALSE);
    $formItems[] = 'membership_status_id';
    $defaults['membership_status_id'] = 2; // current

    // end date
    $this->add('datepicker', 'end_date', 'Einddatum lidmaatschap:', '', TRUE, ['time' => FALSE]);
    $formItems[] = 'end_date';
    $defaults['end_date'] = $this->getDefaultEndDate('full');

    // source
    $this->add('text', 'source', 'Bron bijdrage', ['style' => 'width:25em']);
    $formItems[] = 'source';
    $defaults['source'] = 'LIDM' . $this->getDefaultEndDate('Y');

    // assign form elements to template
    $this->assign('elementNames', $formItems);

    // set defaults
    $this->setDefaults($defaults);

    // add the buttons
    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => 'Wijzig',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'back',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  public function postProcess() {
    $submittedVales = $this->_submitValues;

    // get the selected status id
    $statusID =  $submittedVales['membership_status_id'];
    $endDate = $submittedVales['end_date'];
    $source = $submittedVales['source'];

    // create the queue
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => 'bemas_change_membership_status',
      'reset' => TRUE, // flush queue upon creation
    ]);

    // store the id's in the queue
    // update the status
    foreach ($this->_memberIds as $memberID) {
      $task = new CRM_Queue_Task(['CRM_Membershipstatus_Task_ChangeStatus', 'processMembership'], [$memberID, $statusID, $endDate, $source]);
      $queue->createItem($task);
    }

    if ($queue->numberOfItems() > 0) {
      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'BEMAS Change Membership Status',
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEndUrl' => CRM_Utils_System::url('civicrm/membership/search', 'reset=1'),
      ]);
      $runner->runAllViaWeb();
    }
  }

  public static function processMembership(CRM_Queue_TaskContext $ctx, $memberID, $statusID, $endDate, $source, $description) {
    // get the membership
    $memberShip = civicrm_api3('Membership', 'getsingle', ['id' => $memberID]);

    // check if status or end date needs to be updated
    if ($memberShip['end_date'] != $endDate || $memberShip['status_id'] != $statusID) {
      // update the membership
      $params = [
        'id' => $memberID,
        'end_date' => $endDate,
        'status_id' => $statusID,
      ];
      civicrm_api3('Membership', 'create', $params);
    }

    // see if we have a corresponding contribution for the requested year
    $sql = "
      select
        *
      from
        civicrm_membership_payment mp
      inner join
        civicrm_contribution c on c.id = mp.contribution_id and year(c.receive_date) = %2
      where
        mp.membership_id = %1
    ";
    $sqlParams = [
      1 => [$memberID, 'Integer'],
      2 => [substr($endDate, 0, 4), 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      // OK, do nothing
    }
    else {
      // create contribution
      $price = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $memberShip['membership_type_id'], 'minimum_fee');
      $params = [
        'contact_id' => $memberShip['contact_id'],
        'financial_type_id' => 2,
        'receive_date' => substr($endDate, 0, 4) . '-01-01 12:00',
        'total_amount' => $price,
        'net_amount' => $price,
        'contribution_source' => $source,
        'contribution_status_id' => 2,
        'payment_instrument' => 'EFT',
        'sequential' => 1,
        'custom_116' => $memberShip['custom_134'], // PO number
      ];
      $contrib = civicrm_api3('Contribution', 'create', $params);

      // link this contribution with the membership
      $params = [
        'membership_id' => $memberID,
        'contribution_id' => $contrib['id'],
      ];
      civicrm_api3('MembershipPayment', 'create', $params);
    }

    return TRUE;
  }

  /**
   * Returns the last day of the year.
   *
   * In Jan, Feb, March we return the last day of the current year
   * In all other months we return the last day of next year
   *
   * @return string
   */
  private function getDefaultEndDate($what) {
    // get current month
    $currentMonth = date('n');
    if ($currentMonth <= 3) {
      // set to current year
      $endYear = date('Y');
    }
    else {
      // set to next year
      $endYear = (date('Y') + 1);
    }

    // return only the year or the full end date
    if ($what == 'Y') {
      return $endYear;
    }
    else {
      return $endYear  . '-12-31';
    }

    return $endDate;
  }
}

