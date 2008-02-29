<?php
//
// Definition of eZApprove2CollaborationHandler class
//
// Created on: <13-Dec-2005 11:24:05 hovik>
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

/*! \file ezapprove2collaborationhandler.php
*/

/*!
  \class eZApprove2CollaborationHandler ezapprove2collaborationhandler.php
  \brief The class eZApprove2CollaborationHandler does

*/

/*!
  \class eZApprove2CollaborationHandler ezapprove2collaborationhandler.php
  \brief Handles approval communication using the collaboration system

  The handler uses the fields data_int1, data_int2 and data_int3 to store
  information on the contentobject and the approval status.

  - data_int1 - The content object ID
  - data_int2 - The content object version
  - data_int3 - The status of the approval, see defines.

*/

#include_once( 'kernel/classes/ezcollaborationitemhandler.php' );
#include_once( 'kernel/classes/ezcollaborationitem.php' );
#include_once( 'kernel/classes/ezcollaborationitemmessagelink.php' );
#include_once( 'kernel/classes/ezcollaborationitemparticipantlink.php' );
#include_once( 'kernel/classes/ezcollaborationitemgrouplink.php' );
#include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
#include_once( 'kernel/classes/ezcollaborationprofile.php' );
#include_once( 'kernel/classes/ezcollaborationsimplemessage.php' );
#include_once( 'kernel/classes/ezcontentobjectversion.php' );
#include_once( 'kernel/common/i18n.php' );

#define( "EZ_COLLABORATION_MESSAGE_TYPE_APPROVE2", 1 );
/// Default status, no approval decision has been made
#define( "EZ_COLLABORATION_APPROVE2_STATUS_WAITING", 0 );
/// The contentobject was approved and will be published.
#define( "EZ_COLLABORATION_APPROVE2_STATUS_ACCEPTED", 1 );
/// The contentobject was denied and will be archived.
#define( "EZ_COLLABORATION_APPROVE2_STATUS_DENIED", 2 );
/// The contentobject was deferred and will be a draft again for reediting.
#define( "EZ_COLLABORATION_APPROVE2_STATUS_DEFERRED", 3 );

class eZApprove2CollaborationHandler extends eZCollaborationItemHandler
{
    /// Approval message type
    const MESSAGE_TYPE_APPROVE2 = 1;

    /// Default status, no approval decision has been made
    const STATUS_WAITING = 0;

    /// The contentobject was approved and will be published.
    const STATUS_ACCEPTED = 1;

    /// The contentobject was denied and will be archived.
    const STATUS_DENIED = 2;

    /// The contentobject was deferred and will be a draft again for reediting.
    const STATUS_DEFERRED = 3;
    
    /*!
     Initializes the handler
    */
    function __construct()
    {
        $this->eZCollaborationItemHandler( 'ezapprove2',
                                           ezi18n( 'kernel/classes', 'Approval2' ),
                                           array( 'use-messages' => true,
                                                  'notification-types' => true,
                                                  'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE ) );
    }

    /*!
     \reimp
    */
    function title( &$collaborationItem )
    {
        return ezi18n( 'kernel/classes', 'Approval v.2' );
    }

    /*!
     \reimp
    */
    function content( $collaborationItem )
    {
        $content = array( "content_object_id" => $collaborationItem->attribute( "data_int1" ),
                          "content_object_version" => $collaborationItem->attribute( "data_int2" ),
                          "approval_status" => $collaborationItem->attribute( "data_int3" ) );
        return $content;
    }

    function notificationParticipantTemplate( $participantRole )
    {
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
        {
            return 'approve.tpl';
        }
        else if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_AUTHOR )
        {
            return 'author.tpl';
        }
        else
            return false;
    }

    /*!
     \return the content object version object for the collaboration item \a $collaborationItem
    */
    function contentObjectVersion( $collaborationItem )
    {
        $contentObjectID = $collaborationItem->contentAttribute( 'content_object_id' );
        $contentObjectVersion = $collaborationItem->contentAttribute( 'content_object_version' );
        return eZContentObjectVersion::fetchVersion( $contentObjectVersion, $contentObjectID );
    }

    /*!
     \reimp
     Updates the last_read for the participant link.
    */
    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    /*!
     \reimp
     \return the number of messages for the approve item.
    */
    function messageCount( &$collaborationItem )
    {
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );
    }

    /*!
     \reimp
     \return the number of unread messages for the approve item.
    */
    function unreadMessageCount( &$collaborationItem )
    {
        $lastRead = 0;
        $status = $collaborationItem->attribute( 'user_status' );
        if ( $status )
            $lastRead = $status->attribute( 'last_read' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ),
                                                                      'conditions' => array( 'modified' => array( '>', $lastRead ) ) ) );
    }

    /*!
     \static
     \return the status of the approval collaboration item \a $approvalID.
    */
    static function checkApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            return $collaborationItem->attribute( 'data_int3' );
        }
        return false;
    }

    /*!
     \static
     \return makes sure the approval item is activated for all participants \a $approvalID.
    */
    static function activateApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            $collaborationItem->setAttribute( 'data_int3', eZCollaborationApprove2::STATUS_WAITING );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_ACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->store();
            $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => $approvalID ) );
            foreach( $participantList as $participantLink )
            {
                $collaborationItem->setIsActive( true, $participantLink->attribute( 'participant_id' ) );
            }
            return true;
        }
        return false;
    }

    /*!
     Creates a new approval collaboration item which will approve the content object \a $contentObjectID
     with version \a $contentObjectVersion.
     The item will be added to the author \a $authorID and the approver \a $approverID.
     \return the collaboration item.
    */
    static function createApproval( $contentObjectID, $contentObjectVersion, $authorID, $approverIDList, $collaborationID = false )
    {
        $createNotification = false;
        $collaborationItem = false;
        $participantList = array();
        
        if ( $collaborationID === false )
        {
            $collaborationItem = eZCollaborationItem::create( 'ezapprove2', $authorID );
            $collaborationItem->setAttribute( 'data_int1', $contentObjectID );
            $collaborationItem->setAttribute( 'data_int2', $contentObjectVersion );
            $collaborationItem->setAttribute( 'data_int3', false );
            $collaborationItem->store();
            $createNotification = true;
            $collaborationID = $collaborationItem->attribute( 'id' );

            $participantList[] = array( 'id'   => $authorID,
                                        'role' => eZCollaborationItemParticipantLink::ROLE_AUTHOR );
        }

        foreach( $approverIDList as $approverID )
        {
            $participantList[] = array( 'id'   => $approverID,
                                        'role' => eZCollaborationItemParticipantLink::ROLE_APPROVER );
        }

        // #HACK# ???
        foreach ( $participantList as $participantItem )
        {
            $participantID = $participantItem['id'];
            $participantRole = $participantItem['role'];
            $link = eZCollaborationItemParticipantLink::create( $collaborationID, $participantID,
                                                                $participantRole, eZCollaborationItemParticipantLink::TYPE_USER );
            $link->store();

            $profile = eZCollaborationProfile::instance( $participantID );
            $groupID = $profile->attribute( 'main_group' );
            eZCollaborationItemGroupLink::addItem( $groupID, $collaborationID, $participantID );
        }

        // Create the notification
        if ( $createNotification )
        {
            $collaborationItem->createNotificationEvent();
        }
        return $collaborationItem;
    }

    /*!
     \reimp
     Adds a new comment, approves the item or denies the item.
    */
    function handleCustomAction( &$module, &$collaborationItem )
    {
        $redirectView = 'item';
        $redirectParameters = array( 'full', $collaborationItem->attribute( 'id' ) );
        $addComment = false;

        if ( $this->isCustomAction( 'Comment' ) )
        {
            $addComment = true;
        }
        else if ( $this->isCustomAction( 'Accept' ) ||
                  $this->isCustomAction( 'Deny' ) )
        {
            #include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

            $approveStatus = eZXApproveStatus::fetchByCollaborationItem( $collaborationItem->attribute( 'id' ) );
            if ( !$approveStatus )
            {
                eZDebug::writeError( 'Could not fetch approve status for collaboration id : ' . $collaborationItem->attribute( 'id' ) );
                return false;
            }
            $approveStatusUserLink = eZXApproveStatusUserLink::fetchByUserID( eZUser::currentUserID(),
                                                                              $approveStatus->attribute( 'id' ),
                                                                              eZXApproveStatusUserLink::RoleApprover );
            if ( !$approveStatusUserLink )
            {
                eZDebug::writeDebug( 'User is not approver for approve status : ' . $approveStatus->attribute( 'id' ) . ', user ID : ' . eZUser::currentUserID() );
                return false;
            }

            $contentObjectVersion = $this->contentObjectVersion( $collaborationItem );
            if ( $this->isCustomAction( 'Accept' ) )
            {
                $approveStatusUserLink->setAttribute( 'approve_status', eZXApproveStatusUserLink::StatusApproved );
            }
            else if ( $this->isCustomAction( 'Deny' ) )
            {
                $collaborationItem->setIsActive( false );
                $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
                $approveStatusUserLink->setAttribute( 'approve_status', eZXApproveStatusUserLink::StatusDiscarded );
            }
            $approveStatusUserLink->sync();

            $redirectView = 'view';
            $redirectParameters = array( 'summary' );
            $addComment = true;
        }
        else if ( $this->isCustomAction( 'Edit' ) )
        {
            #include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

            $approveStatus = eZXApproveStatus::fetchByCollaborationItem( $collaborationItem->attribute( 'id' ) );
            $contentObject = $approveStatus->attribute( 'contentobject' );

            // Check if user can edit object
            if ( !$contentObject ||
                 !$contentObject->attribute( 'can_edit' ) )
            {
                eZDebug::writeError( 'insufficient access to edit object : ' . $contentObject->attribute( 'id' ) );
                return false;
            }

            // 1. Lock workflow, and abort all previous pending elements.
            $db = eZDB::instance();
            if ( !$approveStatus )
            {
                eZDebug::writeError( 'Could not fetch approve status for collaboration id : ' . $collaborationItem->attribute( 'id' ) );
                return false;
            }
            $db->begin();
            $approveStatus->cancel();

            // 2. Create new version based in the pending one.
            $newVersion = $contentObject->createNewVersion( $approveStatus->attribute( 'active_version' ) );

            // 3. Set pending version to rejected.
            $oldVersion = $approveStatus->attribute( 'object_version' );
            $oldVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_REJECTED );
            $oldVersion->sync();

            // Abort collaboration item. Added by KK
            $collaborationItem->setIsActive( false );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );

            $db->commit();

            // 4. Redirect user to new object.
            return $module->redirect( 'content', 'edit', array( $contentObject->attribute( 'id' ),
                                                                $newVersion->attribute( 'version' ) ) );
        }
        else if ( $this->isCustomAction( 'AddApprover' ) )
        {
            #include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

            $approveStatus = eZXApproveStatus::fetchByCollaborationItem( $collaborationItem->attribute( 'id' ) );
            if ( !$approveStatus )
            {
                eZDebug::writeError( 'Could not find eZXApproveStatus, ' . $collaborationItem->attribute( 'id' ) );
                return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
            }

            return $module->redirect( 'ezapprove2', 'add_approver', array( $approveStatus->attribute( 'id' ) ) );
        }

        if ( $addComment )
        {
            $messageText = $this->customInput( 'ApproveComment' );
            if ( trim( $messageText ) != '' )
            {
                $message = eZCollaborationSimpleMessage::create( 'ezapprove2_comment', $messageText );
                $message->store();
                eZCollaborationItemMessageLink::addMessage( $collaborationItem, $message, eZApprove2CollaborationHandler::MESSAGE_TYPE_APPROVE2 );
            }
        }

        $collaborationItem->setAttribute( 'modified', time() );
        $collaborationItem->sync();
        return $module->redirectToView( $redirectView, $redirectParameters );
    }
}
?>
