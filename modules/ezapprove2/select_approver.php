<?php
//
// Created on: <15-Dec-2005 06:45:23 hovik>
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

/*! \file select_approver.php
*/

include_once( 'kernel/common/template.php' );

#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezapprove2event.php' );

$Module = $Params['Module'];
$approveStatusID = $Params['ApproveStatusID'];
$warning = '';

$approveStatus = eZXApproveStatus::fetch( $approveStatusID );

if ( !$approveStatus )
{
    return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}

$http = eZHTTPTool::instance();

if ( $http->hasPostVariable( 'RemoveApproveUsers' ) )
{
    foreach( $http->postVariable( 'DeleteApproveUserIDArray' ) as $approveUserID )
    {
        $approveStatus->removeUser( $approveUserID );
    }
}
else if ( $http->hasPostVariable( 'AddApproveUsers' ) )
{
    $approveINI = eZINI::instance( 'ezapprove2.ini' );
    #include_once( 'kernel/classes/ezcontentbrowse.php' );
    eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleUsers',
                                    'class_array' => $approveINI->variable( 'ApproveSettings', 'UserClassIdentifierList' ),
                                    'from_page' => 'ezapprove2/select_approver/' . $approveStatus->attribute( 'id' ) ),
                             $Module );
}
else if ( $http->hasPostVariable( 'SelectedObjectIDArray' ) )
{
    foreach( $http->postVariable( 'SelectedObjectIDArray' ) as $userID )
    {
        $approveStatus->addApproveUser( $userID );
    }
}
else if ( $http->hasPostVariable( 'SubmitButton' ) )
{
    $approveEvent = $approveStatus->attribute( 'approve2_event' );
    $approveUserList = $approveStatus->attribute( 'approve_user_list' );

    if ( count( $approveUserList ) < $approveEvent->attribute( 'num_approve_users' ) ||
         count( $approveUserList ) == 0 )
    {
        $warning = ezi18n( 'ezapprove2', 'You need to select at least %num_users users to approve your content.', false, array( '%num_users' => $approveEvent->attribute( 'num_approve_users' ) ) );
    }
    else
    {
        // Set object version to draft untill approvers are selected successfully in case user exists in the wrong way.
        #include_once( 'kernel/classes/ezcontentobjectversion.php' );
        $contentObjectVersion = $approveStatus->attribute( 'object_version' );
        $contentObjectVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_PENDING );
        $contentObjectVersion->sync();

        $approveStatus->setAttribute( 'approve_status', eZXApproveStatus::StatusInApproval );
        $approveStatus->store();

        $workflowProcess = $approveStatus->attribute( 'workflow_process' );
        if ( !$workflowProcess )
        {
            $approveStatus->remove();
            return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
        }
        $workflowProcess->setAttribute( 'status', eZWorkflow::STATUS_DEFERRED_TO_CRON );
        $workflowProcess->setAttribute( 'modified', time() );

        $parameterList = $workflowProcess->attribute( 'parameter_list' );
        if ( isset( $parameterList[ 'parent_process_id' ] ) )
        {
            $parentProcess = eZWorkflowProcess::fetch( $parameterList[ 'parent_process_id' ] );
            if ( is_object( $parentProcess ) )
            {
                $parentProcess->setAttribute( 'status', eZWorkflow::STATUS_DEFERRED_TO_CRON );
                $parentProcess->setAttribute( 'modified', time() );
                $parentProcess->store();
            }
        }

        $workflowProcess->store();

        $approveINI = eZINI::instance( 'ezapprove2.ini' );
        if ( $approveINI->variable( 'ApproveSettings', 'ObjectLockOnEdit' ) == 'true' )
        {
            // Lock all related objects for editing and removal
            $object = $approveStatus->attribute( 'contentobject' );
            // #HACK#
            if ( $object->attribute( 'contentclass_id' ) == 17 ) // 17 == newsletter_issue
            {
                foreach( $object->relatedContentObjectList( $approveStatus->attribute( 'active_version' ), false, false ) as $relatedObject )
                {
                    $relatedObject->setAttribute( 'flags', $relatedObject->attribute( 'flags' ) | EZ_CONTENT_OBJECT_FLAG_LOCK_EDIT | EZ_CONTENT_OBJECT_FLAG_LOCK_REMOVE );
                    $relatedObject->sync();
                }
            }
        }

        $collaborationItemID = $approveStatus->createCollaboration();

        $approveINI = eZINI::instance( 'ezapprove2.ini' );
        if ( $approveINI->variable( 'ApproveSettings', 'NodeCreationOnDraft' ) == 'true' )
        {
            // Create temporary contentobject tree node entry
            $db = eZDB::instance();
            #include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
            $parentNodeIDArray = $object->attribute( 'parent_nodes' );
            $tmpNode = eZContentObjectTreeNode::create( $parentNodeIDArray[0],
                                                        $object->attribute( 'id' ),
                                                        $approveStatus->attribute( 'active_version' ) );
            $tmpNode->store();
            $parentNode = $tmpNode->attribute( 'parent' );
            $tmpNode->setAttribute( 'main_node_id', $tmpNode->attribute( 'node_id' ) );
            $tmpNode->setAttribute( 'path_string', $parentNode->attribute( 'path_string' ) . $tmpNode->attribute( 'node_id' ) . '/' );
            $tmpNode->setAttribute( 'depth', ( $parentNode->attribute( 'depth' ) + 1 ) );
//        $tmpNode->setAttribute( 'published', mktime() );
            $tmpNode->setAttribute( 'path_identification_string', $tmpNode->pathWithNames() );
            $tmpNode->sync();

            #include_once( 'kernel/classes/ezurlalias.php' );
            $alias = eZURLAlias::create( $tmpNode->attribute( 'path_identification_string' ),
                                         'content/versionview/' . $object->attribute( 'id' ) . '/' . $approveStatus->attribute( 'active_version' ) );
            $alias->store();

            #include_once( "kernel/classes/ezcontentcachemanager.php" );
            eZContentCacheManager::clearObjectViewCache( $parentNode->attribute( 'contentobject_id' ) );
        }

        return $Module->redirect( 'collaboration', 'item', array( 'full',
                                                                  $collaborationItemID ) );
    }
}

$tpl = templateInit();
$tpl->setVariable( 'approval_status', $approveStatus );
$tpl->setVariable( 'object', $approveStatus->attribute( 'object_version' ) );
$tpl->setVariable( 'warning', $warning );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:workflow/eventtype/ezapprove2/select_approver.tpl' );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'ezapprove2', 'Edit Subscription' ) ) );


?>
