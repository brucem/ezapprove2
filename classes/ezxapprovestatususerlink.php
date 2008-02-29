<?php
//
// Definition of eZXApproveStatusUserLink class
//
// Created on: <12-Dec-2005 22:19:00 hovik>
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

/*! \file ezxapprovestatususerlink.php
*/

/*!
  \class eZXApproveStatusUserLink ezxapprovestatususerlink.php
  \brief The class eZXApproveStatusUserLink does

*/

#include_once( 'kernel/classes/ezpersistentobject.php' );
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

#define( 'eZXApproveStatusUserLink_RoleCreator', 0 );
#define( 'eZXApproveStatusUserLink_RoleApprover', 1 );
#define( 'eZXApproveStatusUserLink_StatusNone', 0 );
#define( 'eZXApproveStatusUserLink_StatusApproved', 1 );
#define( 'eZXApproveStatusUserLink_StatusDiscarded', 2 );
#define( 'eZXApproveStatusUserLink_StatusNewDraft', 3 );
#define( 'eZXApproveStatusUserLink_MessageMissing', 0 );
#define( 'eZXApproveStatusUserLink_MessageCreated', 1 );

class eZXApproveStatusUserLink extends eZPersistentObject
{
    const RoleCreator = 0;
    const RoleApprover = 1;
    
    const StatusNone = 0;
    const StatusApproved = 1;
    const StatusDiscarded = 2;
    const StatusNewDraft = 3;
 
    const MessageMissing = 0;
    const MessageCreated = 1;
 
    /*!
     Constructor
    */
    function __construct( $rows = array() )
    {
        parent::__construct( $rows );
    }

    /*!
     \reimp
    */
    static function definition()
    {
        return array( 'fields' => array( 'id' => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         'approve_id' => array( 'name' => 'ApproveID',
                                                                'datatype' => 'integer',
                                                                'default' => 0,
                                                                'required' => true ),
                                         'approve_status' => array( 'name' => 'ApproveStep',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                         'approve_role' => array( 'name' => 'ApproveRole',
                                                                  'datatype' => 'integer',
                                                                  'default' => 0,
                                                                  'required' => true ),
                                         'hash' => array( 'name' => 'Hash',
                                                          'datatype' => 'string',
                                                          'default' => '',
                                                          'required' => true ),
                                         'user_id' => array( 'name' => 'UserID',
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         'message_link_created' => array( 'name' => 'MessageLinkCreated',
                                                                          'datatype' => 'integer',
                                                                          'default' => 0,
                                                                          'required' => true ),
                                         'action' => array( 'name' => 'Action',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ) ),
                      'keys' => array( 'id' ),
                      'increment_key' => 'id',
                      'function_attributes' => array( 'user' => 'user'),
                      'increment_key' => 'id',
                      'sort' => array( 'id' => 'asc' ),
                      'class_name' => 'eZXApproveStatusUserLink',
                      'name' => 'ezx_approve_status_user_link' );

    }

    function user()
    {
        return eZUser::fetch( $this->attribute( 'user_id' ) );
    }

    static function fetchByUserID( $userID, $approveID, $approveStatus, $hash = false, $asObject = true )
    {
        $cond = array( 'user_id' => $userID,
                       'approve_id' => $approveID );
                       
        if ( $approveStatus !== false )
        {
            $cond['approve_role'] = $approveStatus;
        }
        if ( $hash !== false )
        {
            $cond['hash'] = $hash;
        }

        return eZPersistentObject::fetchObject( eZXApproveStatusUserLink::definition(),
                                                null,
                                                $cond,
                                                $asObject );
    }

    static function fetchByCollaborationID( $userID, $collaborationID, $approveStatus, $asObject = true )
    {
        $db = eZDB::instance();
        $sql = "SELECT ezx_approve_status_user_link.*
                FROM ezx_approve_status_user_link, ezx_approve_status
                WHERE ezx_approve_status_user_link.approve_id = ezx_approve_status.id AND
                      ezx_approve_status.collaborationitem_id = '" . $db->escapeString( $collaborationID ) . "' AND
                      ezx_approve_status_user_link.user_id = '" . $db->escapeString( $userID ) . "'";
                      
        if ( $approveStatus !== false )
        {
            $sql .= " AND ezx_approve_status_user_link.approve_role = '" . $db->escapeString( $approveStatus ) . "'";
        }

        $resultSet = $db->arrayQuery( $sql );

        if ( count( $resultSet ) == 1 )
        {
            return new eZXApproveStatusUserLink( $resultSet[0] );
        }

        return false;
    }

    static function create( $userID, $approveID, $approveRole, $hash = '' )
    {
        return new eZXApproveStatusUserLink( array( 'approve_id' => $approveID,
                                                    'approve_status' => eZXApproveStatusUserLink::StatusNone,
                                                    'approve_role' => $approveRole,
                                                    'user_id' => $userID,
                                                    'hash' => $hash ) );
    }

    /*!
     \static
     Approve status name map
    */
    static function statusNameMap()
    {
        return array( eZXApproveStatusUserLink::StatusNone      => ezi18n( 'ezapprove2', 'None' ),
                      eZXApproveStatusUserLink::StatusApproved  => ezi18n( 'ezapprove2', 'Approve' ),
                      eZXApproveStatusUserLink::StatusDiscarded => ezi18n( 'ezapprove2', 'Discard' ),
                      eZXApproveStatusUserLink::StatusNewDraft  => ezi18n( 'ezapprove2', 'New Draft' ) );
    }
}

?>
