<?php
//
// Definition of eZApprove2Event class
//
// Created on: <14-Dec-2005 22:06:57 hovik>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZApprove2
// SOFTWARE RELEASE: 0.1
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezapprove2event.php
*/

/*!
  \class eZApprove2Event ezapprove2event.php
  \brief The class eZApprove2Event does

*/

#define( 'eZApprove2Event_RequireApproveOne', 0 );
#define( 'eZApprove2Event_RequireApproveAll', 1 );
#define( 'eZApprove2Event_RequireApproveUser', 2 );

#define( 'eZApprove2Event_ApproveTypePredefined', 0 );
#define( 'eZApprove2Event_ApproveTypeUser', 1 );

#define( 'eZApprove2Event_RequiredNumApproversAny', -1 );

#define( 'eZApprove2Event_AddApproverNo', 0 );
#define( 'eZApprove2Event_AddApproverYes', 1 );

class eZApprove2Event extends eZPersistentObject
{
    const RequireApproveOne = 0;
    const RequireApproveAll = 1;
    const RequireApproveUser = 2;
    
    const ApproveTypePredefined = 0;
    const ApproveTypeUser = 1;
    
    const RequiredNumApproversAny = -1;
    
    const AddApproverNo = 0;
    const AddApproverYes = 1;
                    
    /*!
     Constructor
    */
    function __construct( $row )
    {
        parent::__construct( $row );
    }

    static function definition()
    {
        return array( 'fields' => array( 'workflowevent_id' => array( 'name' => 'WorkflowEventID',
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         'workflowevent_version' => array( 'name' => 'WorkflowEventVersion',
                                                                           'datatype' => 'integer',
                                                                           'default' => 0,
                                                                           'required' => true ),
                                         'selected_sections' => array( 'name' => 'SelectedSections',
                                                                       'datatype' => 'string',
                                                                       'default' => '-1',
                                                                       'required' => true ),
                                         'approve_users' => array( 'name' => 'ApproveUsers',
                                                                   'datatype' => 'string',
                                                                   'default' => '',
                                                                   'required' => true ),
                                         'approve_groups' => array( 'name' => 'ApproveGroups',
                                                                    'datatype' => 'string',
                                                                    'default' => '',
                                                                    'required' => true ),
                                         'approve_type' => array( 'name' => 'ApproveType',
                                                                  'datatype' => 'integer',
                                                                  'default' => 0,
                                                                  'required' => true ),
                                         'selected_usergroups' => array( 'name' => 'SelectedUserGroups',
                                                                         'datatype' => 'string',
                                                                         'default' => '',
                                                                         'required' => true ),
                                         'num_approve_users' => array( 'name' => 'NumApproveUsers',
                                                                       'datatype' => 'integer',
                                                                       'default' => 0,
                                                                       'required' => true ),
                                         'allow_add_approver' => array( 'name' => 'AllowAddApprover',
                                                                        'datatype' => 'integer',
                                                                        'default' => 0,
                                                                        'required' => true ),
                                         'require_all_approve' => array( 'name' => 'RequireAllApprove',
                                                                         'datatype' => 'integer',
                                                                         'default' => 0,
                                                                         'required' => true ) ),
                      'keys' => array( 'workflowevent_id', 'workflowevent_version' ),
                      'function_attributes' => array( 'require_approve_name_map' => 'requireApproveNameMap',
                                                      'add_approver_name_map' => 'addApproverNameMap',
                                                      'approve_type_name_map' => 'approveTypeNameMap',
                                                      'approve_user_list' => 'approveUserList',
                                                      'approve_group_list' => 'approveGroupList',
                                                      'selected_section_list' => 'selectedSectionList',
                                                      'selected_usergroup_list' => 'selectedUserGroupList' ),
                      'sort' => array( 'workflowevent_id' => 'asc' ),
                      'class_name' => 'eZApprove2Event',
                      'name' => 'ezx_approve2_event' );
    }

    function approveUserList()
    {
        if ( $this->attribute( 'approve_users' ) == '' )
        {
            $retVal = array();
        }
        else
        {
            $retVal = explode( ',', $this->attribute( 'approve_users' ) );
        }
        return $retVal;
    }

    function selectedSectionList()
    {
        $retVal = explode( ',', $this->attribute( 'selected_sections' ) );
        return $retVal;
    }

    function approveGroupList()
    {
        if ( $this->attribute( 'approve_groups' ) == '' )
        {
            $retVal = array();
        }
        else
        {
            $retVal = explode( ',', $this->attribute( 'approve_groups' ) );
        }
        return $retVal;
    }

    /*!
     Remove user groups from selected liste
    */
    function removeApproveUserList( $removeIDArray )
    {
        $this->setAttribute( 'approve_users', implode( ',', array_diff( $this->attribute( 'approve_user_list' ),
                                                                        $removeIDArray ) ) );
    }

    /*!
     Remove user groups from selected liste
    */
    function removeApproveGroupList( $removeIDArray )
    {
        $this->setAttribute( 'approve_groups', implode( ',', array_diff( $this->attribute( 'approve_group_list' ),
                                                                         $removeIDArray ) ) );
    }

    /*!
     Remove user groups from selected liste
    */
    function removeSelectedUserList( $removeIDArray )
    {
        $this->setAttribute( 'selected_usergroups', implode( ',', array_diff( $this->attribute( 'selected_usergroup_list' ),
                                                                              $removeIDArray ) ) );
    }

    function selectedUserGroupList()
    {
        if ( $this->attribute( 'selected_usergroups' ) == '' )
        {
            $retVal = array();
        }
        else
        {
            $retVal = explode( ',', $this->attribute( 'selected_usergroups' ) );
        }
        return $retVal;
    }

    /*!
     Get name/value map for approve users
    */
    static function requireApproveNameMap()
    {
        $retVal = array( eZApprove2Event::RequireApproveOne => ezi18n( 'ezapprove2', 'One' ),
                         eZApprove2Event::RequireApproveAll => ezi18n( 'ezapprove2', 'All' ),
                         eZApprove2Event::RequireApproveUser => ezi18n( 'ezapprove2', 'User defined' ) );
        return $retVal;
    }

    /*!
     Add approver to workflow.
    */
    static function addApproverNameMap()
    {
        $retVal = array( eZApprove2Event::AddApproverNo => ezi18n( 'ezapprove2', 'No' ),
                         eZApprove2Event::AddApproverYes => ezi18n( 'ezapprove2', 'Yes' ) );
        return $retVal;
    }

    /*!
     Get approve type name map
    */
    static function approveTypeNameMap()
    {
        $retVal = array( eZApprove2Event::ApproveTypeUser => ezi18n( 'ezapprove2', 'User defined' ),
                         eZApprove2Event::ApproveTypePredefined => ezi18n( 'ezapprove2', 'Predefined' ) );
        return $retVal;
    }

    /*!
     Fetch eZApprove2Event object

     \param workflow event id
     \param workflow event version
     \param for reload ( default false )
     \param return as object
    */
    static function fetch( $workflowEventID, $workflowEventVersion = false, $forceLoad = false, $asObject = true )
    {
        if ( $workflowEventVersion === false )
        {
            $workflowEventVersion = 0;
        }

        if ( !$forceLoad &&
             isset( $GLOBALS['eZApprove2Event_' . $workflowEventID . '_' . $workflowEventVersion] ) )
        {
            return $GLOBALS['eZApprove2Event_' . $workflowEventID . '_' . $workflowEventVersion];
        }

        $event = eZPersistentObject::fetchObject( eZApprove2Event::definition(),
                                                  null,
                                                  array( 'workflowevent_id' => $workflowEventID,
                                                         'workflowevent_version' => $workflowEventVersion ),
                                                  $asObject );

        if ( !$event )
        {
            $event = eZApprove2Event::fetchDraft( $workflowEventID, $asObject );
        }

        if ( !$event )
        {
            $event = new eZApprove2Event( array( 'workflowevent_id' => $workflowEventID,
                                                 'workflowevent_version' => $workflowEventVersion ) );
        }

        $GLOBALS['eZApprove2Event_' . $workflowEventID . '_' . $workflowEventVersion] = $event;

        return $GLOBALS['eZApprove2Event_' . $workflowEventID . '_' . $workflowEventVersion];
    }

    static function fetchDraft( $workflowEventID, $asObject = true )
    {
        $event = eZPersistentObject::fetchObject( eZApprove2Event::definition(),
                                                  null,
                                                  array( 'workflowevent_id' => $workflowEventID,
                                                         'workflowevent_version' => 1 ),
                                                  $asObject );
        return $event; #HACK
    }

    function publish()
    {
        $this->setAttribute( 'workflowevent_version', 0 );
        $this->store();
    }

    static function removeDraft()
    {
        $draft = eZApprove2Event::fetchDraft( $this->attribute( 'workflowevent_id' ) );
        if ( $draft )
        {
            $draft->remove();
        }
    }
}

?>
