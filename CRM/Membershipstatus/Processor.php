<?php

class CRM_Membershipstatus_Processor {
  public function process($memberID, $statusID, $endDate, $source) {
    $this->updateMembership($memberID, $statusID, $endDate);

    if (!$this->hasContribution($memberID, $endDate)) {
      $contribID = $this->createContribution($memberID, $endDate, $source);

      $this->linkContributionToMembership($contribID, $memberID);
    }
  }

  private function getMembership($memberID) {
    $memberShip = civicrm_api3('Membership', 'getsingle', ['id' => $memberID]);

    return $memberShip;
  }

  private function updateMembership($memberID, $statusID, $endDate) {
    $memberShip = $this->getMembership($memberID);

    if ($memberShip['end_date'] != $endDate || $memberShip['status_id'] != $statusID) {
      $this->updateMembershipEndDateAndStatus($memberID, $statusID, $endDate);
    }

    $memberShipDAO = $this->getChildMemberships($memberID);
    while ($memberShipDAO->fetch()) {
      $this->updateMembership($memberShipDAO->id, $statusID, $endDate);
    }
  }

  private function getChildMemberships($memberID) {
    $sql = "select id from civicrm_membership where owner_membership_id = $memberID";
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao;
  }

  private function updateMembershipEndDateAndStatus($memberID, $statusID, $endDate) {
    $params = [
      'id' => $memberID,
      'end_date' => $endDate,
      'status_id' => $statusID,
    ];
    civicrm_api3('Membership', 'create', $params);
  }

  private function hasContribution($memberID, $endDate) {
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
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function createContribution($memberID, $endDate, $source) {
    $memberShip = $this->getMembership($memberID);
    $price = $this->getMembershipTypePrice($memberShip['membership_type_id']);

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

    return $contrib['id'];
  }

  private function getMembershipTypePrice($membershipTypeId) {
    $price = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membershipTypeId, 'minimum_fee');
    return $price;
  }

  private function linkContributionToMembership($contribID, $memberID) {
    $params = [
      'membership_id' => $memberID,
      'contribution_id' => $contribID,
    ];
    civicrm_api3('MembershipPayment', 'create', $params);
  }
}

