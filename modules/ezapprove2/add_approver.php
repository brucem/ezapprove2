<?php
//
// Created on: <16-Jan-2006 15:14:51 hovik>
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

/*! \file add_approver.php
*/


include_once( 'kernel/common/template.php' );

#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezapprove2event.php' );

$Module = $Params['Module'];
$approveStatusID = $Params['ApproveStatusID'];
$approveStatus = eZXApproveStatus::fetch( $approveStatusID );

if ( !$approveStatus )
{
    eZDebug::writeError( 'Approve status not found.' );
    return $Module->handleError(  eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}

if ( !$approveStatus->isApprover( eZUser::currentUserID() ) )
{
    eZDebug::writeError( 'User is not allowed to add new approvers.' );
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}

$approveEvent = $approveStatus->attribute( 'approve2_event' );
if ( !$approveEvent )
{
    eZDebug::writeDebug( 'Could not find approve event.' );
    return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}
if ( !$approveEvent->attribute( 'allow_add_approver' ) )
{
    eZDebug::writeError( 'User is not allowed to add new approvers.' );
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}

$hash = md5( eZUser::currentUserID() . '-' . $approveStatusID );

$http = eZHTTPTool::instance();

if ( $http->hasPostVariable( 'RemoveApproveUsers' ) )
{
    foreach( $http->postVariable( 'DeleteApproveUserIDArray' ) as $approveUserID )
    {
        $approveStatus->removeUser( $approveUserID, $hash );
    }
}
else if ( $http->hasPostVariable( 'AddApproveUsers' ) )
{
        #include_once( 'kernel/classes/ezcontentbrowse.php' );
        eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleUsers',
                                        'class_array' => array ( 'user' ),
                                        'from_page' => 'ezapprove2/add_approver/' . $approveStatus->attribute( 'id' ) ),
                                 $Module );
}
else if ( $http->hasPostVariable( 'SelectedObjectIDArray' ) )
{
    foreach( $http->postVariable( 'SelectedObjectIDArray' ) as $userID )
    {
        $approveStatus->addApproveUser( $userID, $hash );
    }
}
else if ( $http->hasPostVariable( 'SubmitButton' ) )
{
    $collaborationItemID = $approveStatus->createCollaboration( $hash );
    return $Module->redirect( 'collaboration', 'item', array( 'full',
                                                              $approveStatus->attribute( 'collaborationitem_id' ) ) );
}
else if ( $http->hasPostVariable( 'CancelButton' ) )
{
    return $Module->redirect( 'collaboration', 'item', array( 'full',
                                                              $approveStatus->attribute( 'collaborationitem_id' ) ) );
}

$tpl = templateInit();
$tpl->setVariable( 'approval_status', $approveStatus );
$tpl->setVariable( 'object', $approveStatus->attribute( 'object_version' ) );
$tpl->setVariable( 'approve_user_list', $approveStatus->approveUserList( $hash ) );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:workflow/eventtype/ezapprove2/add_approver.tpl' );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'ezapprove2', 'Edit Subscription' ) ) );

?>
