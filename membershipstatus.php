<?php

require_once 'membershipstatus.civix.php';

function membershipstatus_civicrm_searchTasks( $objectName, &$tasks ) {
  if ($objectName == 'membership') {
    $taskFound = FALSE;

    // see if the task is already in the list
    foreach ($tasks as $task) {
      if ($task['class'] == 'CRM_Membershipstatus_Task_ChangeStatus') {
        $taskFound = TRUE;
        break;
      }
    }

    // if not found, add the task
    if (!$taskFound) {
      $tasks[] = array(
        'class' => 'CRM_Membershipstatus_Task_ChangeStatus',
        'title' => ts('Wijzig lidmaatschapstatus en maak bijdragen'),
        'result' => FALSE,
      );
    }
  }

}
  /**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function membershipstatus_civicrm_config(&$config) {
  _membershipstatus_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function membershipstatus_civicrm_install() {
  _membershipstatus_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function membershipstatus_civicrm_enable() {
  _membershipstatus_civix_civicrm_enable();
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function membershipstatus_civicrm_navigationMenu(&$menu) {
  _membershipstatus_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'be.businessandcode.membershipstatus')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _membershipstatus_civix_navigationMenu($menu);
} // */
