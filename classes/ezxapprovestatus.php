<?php
//
// Definition of eZXApproveStatus class
//
// Created on: <12-Dec-2005 21:14:37 hovik>
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

/*! \file ezxapprovestatus.php
*/

/*!
  \class eZXApproveStatus ezxapprovestatus.php
  \brief The class eZXApproveStatus does

*/

#define( 'eZXApproveStatus_StatusSelectApprover', 0 );
#define( 'eZXApproveStatus_StatusInApproval', 1 );
#define( 'eZXApproveStatus_StatusApproved', 2 );
#define( 'eZXApproveStatus_StatusDiscarded', 3 );
#define( 'eZXApproveStatus_StatusFinnished', 4 );

class eZXApproveStatus extends eZPersistentObject
{
    const StatusSelectApprover = 0;
    const StatusInApproval     = 1;
    const StatusApproved       = 2;
    const StatusDiscarded      = 3;
    const StatusFinnished      = 4;
    
    /*!
     Constructor
    */
    function __construct( $row )
    {
        parent::__construct( $row );
    }

    static function definition()
    {
        return array( 'fields' => array( 'id' => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         'contentobject_id' => array( 'name' => 'ContentObjectID',
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         'approve_status' => array( 'name' => 'ApproveStatus',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                         'contentobject_status' => array( 'name' => 'ContentObjectStatus',
                                                                          'datatype' => 'integer',
                                                                          'default' => 0,
                                                                          'required' => true ),
                                         'active_version' => array( 'name' => 'ActiveVersion',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                         'locked_version' => array( 'name' => 'LockedVersion',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                         'locked' => array( 'name' => 'LockedTS',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ),
                                         'locked_user_id' => array( 'name' => 'LockedUserID',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                         'started' => array( 'name' => 'Started',
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         'ended' => array( 'name' => 'Ended',
                                                           'datatype' => 'integer',
                                                           'default' => 0,
                                                           'required' => true ),
                                         'collaborationitem_id' => array( 'name' => 'CollaborationID',
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         'workflowprocess_id' => array( 'name' => 'WorkflowProcessID',
                                                                        'datatype' => 'integer',
                                                                        'default' => 0,
                                                                        'required' => true ),
                                         'required_num_approvers' => array( 'name' => 'RequiredNumApprovers',
                                                                            'datatype' => 'integer',
                                                                            'default' => 0,
                                                                            'required' => true ),
                                         'event_position' => array( 'name' => 'EventPos',
                                                               'datatype' => 'integer',
                                                               'default' => 0,
                                                               'required' => true ) ),
                      'keys' => array( 'id' ),
                      'increment_key' => 'id',
                      'function_attributes' => array( 'approve_user_list' => 'approveUserList',
                                                      'object_version' => 'objectVersion',
                                                      'approve_status_name' => 'approveStatusName',
                                                      'approve2_event' => 'approve2Event',
                                                      'contentobject' => 'contentObject',
                                                      'collaboration_item' => 'collaborationItem',
                                                      'user_approve_status' => 'userApproveStatus',
                                                      'num_approved' => 'approvedUserCount',
                                                      'num_approvers' => 'approversUserCount',
                                                      'num_approve_required' => 'numApproveRequired',
                                                      'workflow_process' => 'workflowProcess' ),
                      'sort' => array( 'id' => 'asc' ),
                      'class_name' => 'eZXApproveStatus',
                      'name' => 'ezx_approve_status' );
    }

    /*!
     Cancel current approve workflow
    */
    function cancel()
    {    

        $collaborationItem = $this->attribute( 'collaboration_item' );
        if ( $collaborationItem )
        {
            $collaborationItem->setAttribute( 'data_int3', eZApprove2CollaborationHandler::STATUS_DENIED );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->setIsActive( false );
            $collaborationItem->sync();
        }

        $this->setAttribute( 'approve_status', eZXApproveStatus::StatusDiscarded );
        $this->sync();
    }

    /*!
     \static

     Fetch by collaboration item
    */
    static function fetchByCollaborationItem( $collaborationItemID, $asObject = true )
    {
        return eZPersistentObject::fetchObject( eZXApproveStatus::definition(),
                                                null,
                                                array( 'collaborationitem_id' => $collaborationItemID ),
                                                $asObject );
    }

    /*!
     \static

     Fetch ApproveStatus list bu user ID
    */
    static function fetchListByUserID( $userID, $offset = 0, $limit = 10, $status = eZXApproveStatus::StatusInApproval )
    {
        $db = eZDB::instance();
        $sql = 'SELECT DISTINCT ezx_approve_status.*
                FROM ezx_approve_status, ezx_approve_status_user_link
                WHERE ezx_approve_status_user_link.user_id = \'' . $db->escapeString( $userID ) . '\' AND
                      ezx_approve_status.id = ezx_approve_status_user_link.approve_id AND
                      ezx_approve_status.approve_status = \'' . $db->escapeString( $status ) . '\'';
                      
        $result = $db->arrayQuery( $sql, array( 'limit' => $limit,
                                                'offset' => $offset ) );

        if ( !$result ||
             count( $result ) == 0 )
        {
            return false;
        }

        return eZPersistentObject::handleRows( $result, 'eZXApproveStatus', true );
    }

    /*!
     \static

     Fetch count by userID

     \param userID
    */
    static function fetchCountByUserID( $userID )
    {
        $db = eZDB::instance();
        $sql = 'SELECT count( DISTINCT ezx_approve_status.id ) as count
                FROM ezx_approve_status, ezx_approve_status_user_link
                WHERE ezx_approve_status_user_link.user_id = \'' . $db->escapeString( $userID ) . '\' AND
                      ezx_approve_status.id = ezx_approve_status_user_link.approve_id';
        $result = $db->arrayQuery( $sql );

        if ( !$result ||
             count( $result == 0 ) )
        {
            return 0;
        }

        return $result[0]['count'];
    }

    /*!
     \reimp
    */
    function attribute( $attr, $noFunction = false )
    {
        $retVal = false;
        switch( $attr )
        {
            case 'approve_status_name':
            {
                $nameMap = $this->statusNameMap();
                $retVal = $nameMap[$this->attribute( 'approve_status' )];
            } break;

            case 'num_approvers':
            {
                $retVal = count( $this->approveUserList() );
            } break;

            case 'collaboration_item':
            {
                #include_once( 'kernel/classes/ezcollaborationitem.php' );
                $retVal = eZCollaborationItem::fetch( $this->attribute( 'collaborationitem_id' ) );
            } break;

            case 'workflow_process':
            {
                #include_once( 'kernel/classes/ezworkflowprocess.php' );
                $retVal = eZWorkflowProcess::fetch( $this->attribute( 'workflowprocess_id' ) );
            } break;

            case 'user_approve_status':
            {
                $retVal = eZXApproveStatusUserLink::fetchByUserID( eZUser::currentUserID(),
                                                                   $this->attribute( 'id' ),
                                                                   eZXApproveStatusUserLink::RoleApprover );
            } break;

            case 'num_approve_required':
            {
                $eventData = $this->attribute( 'approve2_event' );
                if ( $eventData )
                {
                    $retVal = $eventData->attribute( 'require_all_approve' ) ? $this->attribute( 'num_approvers' ) : 1;
                }
            } break;

            case 'contentobject':
            {
                $retVal = eZContentObject::fetch( $this->attribute( 'contentobject_id' ) );
            } break;

            default:
            {
                $retVal = eZPersistentObject::attribute( $attr );
            } break;
        }
        return $retVal;
    }

    function approve2Event()
    {
        #include_once( 'kernel/classes/ezworkflowprocess.php' );
        $workflowProcess = eZWorkflowProcess::fetch( $this->attribute( 'workflowprocess_id' ),
                                                     false );

        $retVal = false;
        if ( !$workflowProcess )
        {
            return $retVal;
        }
        #include_once( eZExtension::baseDirectory() . '/ezapprove2/eventtypes/event/ezxapprove2/ezxapprove2type.php' );
        $retVal = eZApprove2Event::fetch( $workflowProcess['event_id'] );
        return $retVal;
    }

    function objectVersion()
    {
        $retVal = eZContentObjectVersion::fetchVersion( $this->attribute( 'active_version' ),
                                                        $this->attribute( 'contentobject_id' ) );
        return $retVal;
    }

    /*!
     Check if a user is approver
    */
    function isApprover( $userID )
    {
        $approveUserLink = eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                                    $this->attribute( 'id' ),
                                                                    eZXApproveStatusUserLink::RoleApprover );
        if ( $approveUserLink )
        {
            return true;
        }

        return false;
    }

    /*!
     Check if the user ID is creator
    */
    function isCreator( $userID )
    {
        $approveUserLink = eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                                    $this->attribute( 'id' ),
                                                                    eZXApproveStatusUserLink::RoleCreator );
        if ( $approveUserLink )
        {
            return true;
        }

        return false;
    }

    /*!
     Set approve creator ( object creator )

     \param UserID
    */
    function setCreator( $userID )
    {
        $approveUserLink = eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                                    $this->attribute( 'id' ),
                                                                    eZXApproveStatusUserLink::RoleCreator );
        if ( !$approveUserLink )
        {
            $approveUserLink = eZXApproveStatusUserLink::create( $userID,
                                                                 $this->attribute( 'id' ),
                                                                 eZXApproveStatusUserLink::RoleCreator );
            $approveUserLink->store();
        }
    }

    function addApproveUser( $userID, $hash = false )
    {
        if ( $this->isCreator( $userID ) ||
             !$userID )
        {
            return false;
        }

        $hashCond = false;
        if ( $hash !== false )
        {
            $hashCond = array( array( $hash, '' ) );
        }

        $approveUserLink = eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                                    $this->attribute( 'id' ),
                                                                    eZXApproveStatusUserLink::RoleApprover,
                                                                    $hashCond );
        if ( !$approveUserLink )
        {
            $approveUserLink = eZXApproveStatusUserLink::create( $userID,
                                                                 $this->attribute( 'id' ),
                                                                 eZXApproveStatusUserLink::RoleApprover,
                                                                 $hash );
            $approveUserLink->store();
        }
    }

    function approveUserList( $hash = false, $asObject = true )
    {
        $cond = array( 'approve_id' => $this->attribute( 'id' ),
                       'approve_role' => eZXApproveStatusUserLink::RoleApprover );

        if ( $hash !== false )
        {
            $cond['hash'] = $hash;
        }
        $retVal = eZPersistentObject::fetchObjectList( eZXApproveStatusUserLink::definition(),
                                                       null,
                                                       $cond,
                                                       null,
                                                       null,
                                                       $asObject);
        return $retVal;
    }

    /*!
     Get number of approvers who have approved the object

     \return number of users who have approved.
    */
    function discardedUserCount()
    {
        $discardCount = 0;
        foreach( $this->approveUserList() as $userLink )
        {
            if ( $userLink->attribute( 'approve_status' ) == eZXApproveStatusUserLink::StatusDiscarded )
            {
                ++$discardCount;
            }
        }

        return $discardCount;
    }

    /*!
     Get number of approvers who have approved the object

     \return number of users who have approved.
    */
    function approvedUserCount()
    {
        $approveCount = 0;
        foreach( $this->approveUserList() as $userLink )
        {
            if ( $userLink->attribute( 'approve_status' ) == eZXApproveStatusUserLink::StatusApproved )
            {
                ++$approveCount;
            }
        }

        return $approveCount;
    }

    static function fetch( $id, $asObject = true )
    {
        $retVal = eZPersistentObject::fetchObject( eZXApproveStatus::definition(),
                                                   null,
                                                   array( 'id' => $id ),
                                                   $asObject );
        return $retVal;
    }

    function removeUser( $linkID, $hash = false )
    {
        $condArray = array( 'id' => $linkID );
        if ( $hash !== false )
        {
            $condArray['hash'] = $hash;
        }
        eZPersistentObject::removeObject( eZXApproveStatusUserLink::definition(),
                                          $condArray );
    }

    static function fetchByContentObjectID( $contentObjectID, $contentVersion, $asObject = true )
    {
        $retVal = eZPersistentObject::fetchObject( eZXApproveStatus::definition(),
                                                   null,
                                                   array( 'contentobject_id' => $contentObjectID,
                                                          'active_version' => $contentVersion ),
                                                   $asObject );
        return $retVal;
    }

    /*!
     Fetch by workflow preocess ID

     \param processitem ID
    */
    static function fetchByWorkflowProcessID( $processID, $position, $asObject = true )
    {
        return eZPersistentObject::fetchObject( eZXApproveStatus::definition(),
                                                null,
                                                array( 'workflowprocess_id' => $processID,
                                                       'event_position' => $position ),
                                                $asObject );
    }

    static function create( $contentObjectID,
                            $contentObjectVersion,
                            $workflowProcessID,
                            $eventPosition )
    {
        $retVal = new eZXApproveStatus( array( 'contentobject_id' => $contentObjectID,
                                               'active_version' => $contentObjectVersion,
                                               'workflowprocess_id' => $workflowProcessID,
                                               'started' => time(),
                                               'event_position' => $eventPosition,
                                               'approve_status' => eZXApproveStatus::StatusSelectApprover ) );
        return $retVal;
    }

    /*!
     Create collaboration messages.

     \param hash, specify specific user hash. false by default
     \param Creator ID, optional. Defaul is current user ID.

     \return collaboration item ID.
    */
    function createCollaboration( $hash = false, $creatorID = false )
    {
        #include_once( eZExtension::baseDirectory() . '/ezapprove2/eventtypes/event/ezxapprove2/ezxapprove2type.php' );
        $approveUserIDList = array();
        $user = eZUser::currentUser();
        $db = eZDB::instance();
        $db->begin();

        if ( $hash === false )
        {
            $approveUserList = $this->attribute( 'approve_user_list' );
        }
        else
        {
            $approveUserList = $this->approveUserList( $hash );
        }

        foreach( $approveUserList as $approveUserStatus )
        {
            if ( !$approveUserStatus->attribute( 'message_link_created' ) )
            {
                $approveUserIDList[] = $approveUserStatus->attribute( 'user_id' );
                $approveUserStatus->setAttribute( 'message_link_created', eZXApproveStatusUserLink::MessageCreated );
                $approveUserStatus->setAttribute( 'hash', '' );
                $approveUserStatus->sync();
            }
        }

        $collaborationItemID = $this->attribute( 'collaborationitem_id' ) ? $this->attribute( 'collaborationitem_id' ) : false;

        if ( $creatorID === false )
        {
            $creatorID = $user->id();
        }

        #include_once( eZExtension::baseDirectory() . '/ezapprove2/collaboration/ezapprove2/ezapprove2collaborationhandler.php' );
        $collaborationItem = eZApprove2CollaborationHandler::createApproval( $this->attribute( 'contentobject_id' ),
                                                                             $this->attribute( 'active_version' ),
                                                                             $creatorID,
                                                                             $approveUserIDList,
                                                                             $collaborationItemID );

        if ( $collaborationItem )
        {
            // Set collaboration item it to approve status.
            $this->setAttribute( 'collaborationitem_id', $collaborationItem->attribute( 'id' ) );
            $this->store();
            $collaborationItemID = $collaborationItem->attribute( 'id' );

            // Set collabortion sent status.
            $process = $this->attribute( 'workflow_process' );
            $process->setAttribute( 'event_state', eZApprove2Type::COLLABORATION_CREATED );
            $process->store();
        }
        $db->commit();

        return $collaborationItemID;
    }

    /*
     Create and return collaborationItem.
    */
    function createApproveCollaboration( $process, $event, $userID, $contentobjectID, $contentobjectVersion, $editors )
    {
        if ( $editors === null )
            return false;
        $authorID = $userID;
        #include_once( eZExtension::baseDirectory() . '/ezapprove2/collaboration/ezapprove2/ezapprove2collaborationhandler.php' );
        return  eZApprove2CollaborationHandler::createApproval( $contentobjectID,
                                                                $contentobjectVersion,
                                                                $authorID,
                                                                $editors );
    }

    /*!
     \static

     Get status name map
    */
    static function statusNameMap()
    {
        return array( eZXApproveStatus::StatusSelectApprover => ezi18n( 'ezapprove2', 'Select approver' ),
                      eZXApproveStatus::StatusInApproval => ezi18n( 'ezapprove2', 'In approval' ),
                      eZXApproveStatus::StatusApproved => ezi18n( 'ezapprove2', 'Approved' ),
                      eZXApproveStatus::StatusDiscarded => ezi18n( 'ezapprove2', 'Discarded' ),
                      eZXApproveStatus::StatusFinnished => ezi18n( 'ezapprove2', 'Finnished' ) );
    }

}

?>
