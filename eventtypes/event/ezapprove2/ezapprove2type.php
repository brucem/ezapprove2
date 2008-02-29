<?php
//
// Definition of eZApprove2Type class
//
// Created on: <12-Dec-2005 15:15:59 hovik>
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

/*! \file ezapprove2type.php
*/

/*!
  \class eZApprove2Type ezapprove2type.php
  \brief The class eZApprove2Type does

*/

#include_once( 'kernel/classes/ezworkflowtype.php' );

#include_once( "kernel/classes/ezworkflowtype.php" );
#include_once( 'kernel/classes/collaborationhandlers/ezapprove/ezapprovecollaborationhandler.php' );

#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezapprove2event.php' );
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

#define( "EZ_WORKFLOW_TYPE_APPROVE2_ID", "ezapprove2" );

#define( "EZ_APPROVE2_COLLABORATION_NOT_CREATED", 0 );
#define( "EZ_APPROVE2_COLLABORATION_CREATED", 1 );

class eZApprove2Type extends eZWorkflowEventType
{
    const ID = "ezapprove2";
    const COLLABORATION_NOT_CREATED = 0;
    const COLLABORATION_CREATED = 1;
    
    function __construct()
    {
        $this->eZWorkflowEventType( eZApprove2Type::ID, ezi18n( 'kernel/workflow/event', "Approve2" ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'before' ) ) ) );
    }

    function attributeDecoder( $event, $attr )
    {
        switch ( $attr )
        {
            case 'data':
            {
                $returnValue = eZApprove2Event::fetch( $event->attribute( 'id' ), $event->attribute( 'version' ) );
                return $returnValue;
            } break;
        }
        $retValue = null;
        return $retValue;
    }

    function typeFunctionalAttributes( )
    {
        return array( 'data' );
    }

    function attributes()
    {
        return array_merge( array( 'sections',
                                   'users',
                                   'usergroups' ),
                            eZWorkflowEventType::attributes() );

    }

    function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    function attribute( $attr )
    {
        switch( $attr )
        {
            case 'sections':
            {
                #include_once( 'kernel/classes/ezsection.php' );
                $sections = eZSection::fetchList( false );
                foreach ( array_keys( $sections ) as $key )
                {
                    $section = $sections[$key];
                    $section['Name'] = $section['name'];
                    $section['value'] = $section['id'];
                }
                return $sections;
            }break;
        }
        $eventValue = eZWorkflowEventType::attribute( $attr );
        return $eventValue;
    }

    function execute( $process, $event )
    {
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $process, 'eZApprove2Type::execute' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, 'eZApprove2Type::execute' );
        
        $parameters = $process->attribute( 'parameter_list' );
        $versionID = $parameters['version'];
        $object = eZContentObject::fetch( $parameters['object_id'] );
        
        if ( !$object )
        {
            eZDebugSetting::writeError( 'kernel-workflow-approve', $parameters['object_id'], 'eZApprove2Type::execute' );
            return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }

        /*
          If we run event first time ( when we click publish in admin ) we do not have user_id set in workflow process,
          so we take current user and store it in workflow process, so next time when we run event from cronjob we fetch
          user_id from there.
        */
        if ( $process->attribute( 'user_id' ) == 0 )
        {
            $user = eZUser::currentUser();
            $process->setAttribute( 'user_id', $user->id() );
        }
        else
        {
            $user = eZUser::instance( $process->attribute( 'user_id' ) );
        }

        $eventData = eZApprove2Event::fetch( $event->attribute( 'id' ), $event->attribute( 'version' ) );

        $userGroups = array_merge( (array)$user->attribute( 'groups' ),
                                   (array)$user->attribute( 'contentobject_id' ) );
        $workflowSections = explode( ',', $eventData->attribute( 'selected_sections' ) );
        $workflowGroups = explode( ',', $eventData->attribute( 'selected_usergroups' ) );
        $editors = explode( ',', $eventData->attribute( 'approve_users' ) );
        $approveGroups = explode( ',', $eventData->attribute( 'approve_groups' ) );

        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $user, 'eZApprove2Type::execute::user' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $userGroups, 'eZApprove2Type::execute::userGroups' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $editors, 'eZApprove2Type::execute::editor' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $workflowSections, 'eZApprove2Type::execute::workflowSections' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $workflowGroups, 'eZApprove2Type::execute::workflowGroups' );
        eZDebugSetting::writeDebug( 'kernel-workflow-approve', $object->attribute( 'section_id'), 'eZApprove2Type::execute::section_id' );

        $section = $object->attribute( 'section_id');
        $correctSection = false;

        if ( !in_array( $section, $workflowSections ) && !in_array( -1, $workflowSections ) )
        {
            $assignedNodes = $object->attribute( 'assigned_nodes' );
            if ( $assignedNodes )
            {
                foreach( $assignedNodes as $assignedNode )
                {
                    $parent = $assignedNode->attribute( 'parent' );
                    $parentObject = $parent->object();
                    $section = $parentObject->attribute( 'section_id');

                    if ( in_array( $section, $workflowSections ) )
                    {
                        $correctSection = true;
                        break;
                    }
                }
            }
        }
        else
        {
            $correctSection = true;
        }
        
        switch( $eventData->attribute( 'approve_type' ) )
        {
            case eZApprove2Event::ApproveTypeUser:
            {
                $inExcludeGroups = false;
                $userIsEditor = false;
            } break;

            default:
            case eZApprove2Event::ApproveTypePredefined:
            {
                $inExcludeGroups = count( array_intersect( $userGroups, $workflowGroups ) ) != 0;
                $userIsEditor = ( in_array( $user->id(), $editors ) ||
                                  count( array_intersect( $userGroups, $approveGroups ) ) != 0 );
            } break;
        }

        if ( !$inExcludeGroups &&
             !$userIsEditor &&
             $correctSection )
        {
            switch( $eventData->attribute( 'approve_type' ) )
            {
                case eZApprove2Event::ApproveTypeUser:
                {
                    $contentObjectVersionID = $parameters['version'];
                    $contentObjectID = $parameters['object_id'];
                    $approveStatus = eZXApproveStatus::fetchByContentObjectID( $contentObjectID,
                                                                               $contentObjectVersionID );

                    if ( !$approveStatus ||
                         $approveStatus->attribute( 'approve_status' ) == eZXApproveStatus::StatusSelectApprover )
                    {
                        if ( !$approveStatus )
                        {
                            $approveStatus = eZXApproveStatus::create( $contentObjectID,
                                                                       $contentObjectVersionID,
                                                                       $process->attribute( 'id' ),
                                                                       $process->attribute( 'event_position' ) );
                            $approveStatus->store();
                            $approveStatus->setCreator( $user->attribute( 'contentobject_id' ) );
                        }

                        $approveStatus->setAttribute( 'active_version', $contentObjectVersionID );
                        $approveStatus->sync();

                        $process->Template = array();
                        $process->Template['templateName'] = 'design:workflow/eventtype/ezapprove2/select_approver.tpl';
                        $process->Template['templateVars'] = array( 'event' => $event,
                                                                    'approval_status' => $approveStatus,
                                                                    'object' => $object );

                        // Set object version to draft untill approvers are selected successfully in case user exists in the wrong way.
                        #include_once( 'kernel/classes/ezcontentobjectversion.php' );
                        $contentObjectVersion = eZContentObjectVersion::fetchVersion( $contentObjectVersionID, $contentObjectID );
                        $contentObjectVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                        $contentObjectVersion->sync();

                        return eZWorkflowType::STATUS_FETCH_TEMPLATE_REPEAT;
                    }
                    else
                    {
                        switch( $approveStatus->attribute( 'approve_status' ) )
                        {
                            case eZXApproveStatus::StatusSelectApprover:
                            {
                                // Do nothing, continue processing in next cronjob run.
                                return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
                            } break;

                            case eZXApproveStatus::StatusInApproval:
                            {
                                // Check if enough users have approves the workflow, or any has discarded it.
                                $discardCount = $approveStatus->discardedUserCount();
                                $collaborationItem = $approveStatus->attribute( 'collaboration_item' );
                                #include_once( eZExtension::baseDirectory() . '/ezapprove2/collaboration/ezapprove2/ezapprove2collaborationhandler.php' );

                                if ( $discardCount > 0 )
                                {
                                    $approveStatus->cancel();

                                    $approveINI = eZINI::instance( 'ezapprove2.ini' );
                                    if ( $approveINI->variable( 'ApproveSettings', 'NodeCreationOnDraft' ) == 'true' )
                                    {
                                        $db = eZDB::instance();
                                        $db->query( 'UPDATE ezcontentobject_version
                                                     SET status = ' . eZContentObjectVersion::STATUS_REJECTED . '
                                                     WHERE contentobject_id = ' . $approveStatus->attribute( 'contentobject_id' ) . '
                                                     AND version = ' . $approveStatus->attribute( 'active_version' ) );
                                        $db->query( 'DELETE FROM ezcontentobject_tree where contentobject_id = ' . $approveStatus->attribute( 'contentobject_id' ) .
                                                    ' AND contentobject_version = ' . $approveStatus->attribute( 'active_version' ) );
                                        $db->query( 'DELETE FROM ezurlalias
                                                     WHERE destination_url=\'content/versionview/' . $approveStatus->attribute( 'contentobject_id' ) . '/' . $approveStatus->attribute( 'active_version' ) . '\'' );
                                    }
                                    return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
                                }

                                $numRequired = $approveStatus->attribute( 'num_approve_required' );
                                $numApproved = $approveStatus->attribute( 'num_approved' );
                                if ( $numApproved >= $numRequired )
                                {
                                    $collaborationItem->setAttribute( 'data_int3', eZApprove2CollaborationHandler::STATUS_ACCEPTED );
                                    $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
                                    $timestamp = time();
                                    $collaborationItem->setAttribute( 'modified', $timestamp );
                                    $collaborationItem->setIsActive( false );
                                    $collaborationItem->sync();

                                    $approveStatus->setAttribute( 'approve_status', eZXApproveStatus::StatusApproved );
                                    $approveStatus->store();

                                    $approveINI = eZINI::instance( 'ezapprove2.ini' );
                                    if ( $approveINI->variable( 'ApproveSettings', 'ObjectLockOnEdit' ) == 'true' )
                                    {
                                        // Unlock related objects
                                        $object = $approveStatus->attribute( 'contentobject' );
                                        // #HACK#
                                        if ( $object->attribute( 'contentclass_id' ) == 17 ) // 17 == newsletter_issue
                                        {
                                            foreach( $object->relatedContentObjectList( $approveStatus->attribute( 'active_version' ), false, false ) as $relatedObject )
                                            {
                                                $relatedObject->setAttribute( 'flags', $relatedObject->attribute( 'flags' ) ^ EZ_CONTENT_OBJECT_FLAG_LOCK_EDIT ^ EZ_CONTENT_OBJECT_FLAG_LOCK_REMOVE );
                                                $relatedObject->sync();
                                            }
                                        }
                                    }

                                    return eZWorkflowType::STATUS_ACCEPTED;
                                }
                                else
                                {
                                    // Still need more approvers.
                                    return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
                                }
                            } break;

                            case eZXApproveStatus::StatusDiscarded:
                            {
                                $approveINI = eZINI::instance( 'ezapprove2.ini' );
                                if ( $approveINI->variable( 'ApproveSettings', 'NodeCreationOnDraft' ) == 'true' )
                                {
                                    $db = eZDB::instance();
                                    $db->arrayQuery( 'DELETE FROM ezcontentobject_tree where contentobject_id = ' . $approveStatus->attribute( 'contentobject_id' ) .
                                                     ' AND contentobject_version = ' . $approveStatus->attribute( 'active_version' ) );
                                }

                                return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
                            } break;

                            case eZXApproveStatus::StatusApproved:
                            case eZXApproveStatus::StatusFinnished:
                            {
                                // Nothing special to do.
                            } break;
                        }
                    }
                } break;

                case eZApprove2Event::ApproveTypePredefined:
                {
                    $approveStatus = eZXApproveStatus::fetchByWorkflowProcessID( $process->attribute( 'id' ),
                                                                                 $process->attribute( 'event_position' ) );
                    if ( !$approveStatus )
                    {
                        $contentObjectVersionID = $parameters['version'];
                        $contentObjectID = $parameters['object_id'];

                        $db = eZDB::instance();
                        $db->begin();

                        // CREATE APPROVE STATUS
                        $approveStatus = eZXApproveStatus::create( $contentObjectID,
                                                                   $contentObjectVersionID,
                                                                   $process->attribute( 'id' ),
                                                                   $process->attribute( 'event_position' ) );
                        $approveStatus->store();

                        $approveStatus->setCreator( $user->attribute( 'contentobject_id' ) );

                        // ADD APPROVERS
                        foreach( $approveGroups as $userGroupID )
                        {
                            $userGroupObject = eZContentObject::fetch( $userGroupID );
                            if ( $userGroupObject )
                            {
                                $userGroupNode = $userGroupObject->attribute( 'main_node' );

                                if ( $userGroupNode )
                                {
                                    foreach( $userGroupNode->subTree( array( 'Depth' => 1,
                                                                             'DepthOperator' => 'eq',
                                                                             'Limitation' => array() ) ) as $userNode )
                                    {
                                        $approveStatus->addApproveUser( $userNode->attribute( 'contentobject_id' ) );
                                    }
                                }
                            }
                        }
                        foreach( $editors as $userID )
                        {
                            $approveStatus->addApproveUser( $userID );
                        }

                        $approveStatus->setAttribute( 'approve_status', eZXApproveStatus::StatusInApproval );
                        $approveStatus->store();

                        $approveStatus->createCollaboration( false, $user->attribute( 'contentobject_id' ) );

                        $approveStatus->store();

                        $db->commit();
                    }

                    $discardCount = $approveStatus->discardedUserCount();
                    $collaborationItem = $approveStatus->attribute( 'collaboration_item' );
                    #include_once( eZExtension::baseDirectory() . '/ezapprove2/collaboration/ezapprove2/ezapprove2collaborationhandler.php' );

                    if ( $discardCount > 0 )
                    {
                        $approveStatus->cancel();

                        return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
                    }

                    $numApproved = $approveStatus->attribute( 'num_approved' );
                    if ( $numApproved >= 1 )
                    {
                        $collaborationItem->setAttribute( 'data_int3', eZApprove2CollaborationHandler::STATUS_ACCEPTED );
                        $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
                        $timestamp = time();
                        $collaborationItem->setAttribute( 'modified', $timestamp );
                        $collaborationItem->setIsActive( false );
                        $collaborationItem->sync();

                        $approveStatus->setAttribute( 'approve_status', eZXApproveStatus::StatusApproved );
                        $approveStatus->store();
                        return eZWorkflowType::STATUS_ACCEPTED;
                    }
                    else
                    {
                        // Still need more approvers.
                        return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
                    }
                } break;
            }
        }
        else
        {
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $workflowSections , "we are not going to create approval " . $object->attribute( 'section_id') );
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $userGroups, "we are not going to create approval" );
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $workflowGroups,  "we are not going to create approval" );
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $user->id(), "we are not going to create approval "  );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
    }

    function initializeEvent( $event )
    {
    }

    function fetchHTTPInput( $http, $base, $event )
    {
        $eventData = eZApprove2Event::fetch( $event->attribute( 'id' ), $event->attribute( 'version' ) );

        $sectionsVar = $base . "_event_ezapprove_section_" . $event->attribute( "id" );
        if ( $http->hasPostVariable( $sectionsVar ) )
        {
            $sectionsArray = $http->postVariable( $sectionsVar );
            if ( in_array( '-1', $sectionsArray ) )
            {
                $sectionsArray = array( -1 );
            }
            $sectionsString = implode( ',', $sectionsArray );
            $eventData->setAttribute( "selected_sections", $sectionsString );
        }

        if ( $http->hasPostVariable( 'ApproveType_' . $event->attribute( 'id' ) ) )
        {
            $eventData->setAttribute( 'approve_type', $http->postVariable( 'ApproveType_' . $event->attribute( 'id' ) ) );
        }

        if ( $http->hasPostVariable( 'RequiredNumberApproves_' . $event->attribute( 'id' ) ) )
        {
            $eventData->setAttribute( 'num_approve_users', $http->postVariable( 'RequiredNumberApproves_' . $event->attribute( 'id' ) ) );
        }

        if ( $http->hasPostVariable( 'ApproveOneAll_' . $event->attribute( 'id' ) ) )
        {
            $eventData->setAttribute( 'require_all_approve', $http->postVariable( 'ApproveOneAll_' . $event->attribute( 'id' ) ) );
        }

        if ( $http->hasPostVariable( 'ApproveAllowAddApprover_' . $event->attribute( 'id' ) ) )
        {
            $eventData->setAttribute( 'allow_add_approver', $http->postVariable( 'ApproveAllowAddApprover_' . $event->attribute( 'id' ) ) );
        }

        if ( $http->hasSessionVariable( 'BrowseParameters' ) )
        {
            $browseParameters = $http->sessionVariable( 'BrowseParameters' );
            if ( isset( $browseParameters['custom_action_data'] ) )
            {
                $customData = $browseParameters['custom_action_data'];
                if ( isset( $customData['event_id'] ) &&
                     $customData['event_id'] == $event->attribute( 'id' ) )
                {
                    switch( $customData['browse_action'] )
                    {
                        case 'AddApproveUsers':
                        {
                            if ( $http->hasPostVariable( 'SelectedObjectIDArray' ) and !$http->hasPostVariable( 'BrowseCancelButton' ) )
                            {
                                $userIDArray = $http->postVariable( 'SelectedObjectIDArray' );
                                foreach( $userIDArray as $key => $userID )
                                {
                                    if ( !eZUser::isUserObject( eZContentObject::fetch( $userID ) ) )
                                    {
                                        unset( $userIDArray[$key] );
                                    }
                                }
                                $eventData->setAttribute( 'approve_users', implode( ',',
                                                                                    array_unique( array_merge( (array)$eventData->attribute( 'approve_user_list' ),
                                                                                                               (array)$userIDArray ) ) ) );
                            }
                        } break;

                        case 'AddApproveGroups':
                        {
                            if ( $http->hasPostVariable( 'SelectedObjectIDArray' ) )
                            {
                                $userIDArray = $http->postVariable( 'SelectedObjectIDArray' );
                                $eventData->setAttribute( 'approve_groups', implode( ',',
                                                                                     array_unique( array_merge( (array)$eventData->attribute( 'approve_group_list' ),
                                                                                                                (array)$userIDArray ) ) ) );
                            }
                        } break;

                        case 'AddExcludeUser':
                        {
                            if ( $http->hasPostVariable( 'SelectedObjectIDArray' ) and !$http->hasPostVariable( 'BrowseCancelButton' ) )
                            {
                                $userIDArray = $http->postVariable( 'SelectedObjectIDArray' );
                                $eventData->setAttribute( 'selected_usergroups', implode( ',',
                                                                                          array_unique( array_merge( (array)$eventData->attribute( 'selected_usergroup_list' ),
                                                                                                                     (array)$userIDArray ) ) ) );
                            }
                        } break;

                    }

                    $http->removeSessionVariable( 'BrowseParameters' );
                }
            }
        }
    }

    /*!
     \reimp
    */
    function storeEventData( $event, $version )
    {
        $eventData = eZApprove2Event::fetch( $event->attribute( 'id' ), 1 );

        switch( $version )
        {
            case 0: // publish
            {
                $eventData->publish();
                $eventData->removeDraft();
            } break;

            case 1: // draft
            {
                $eventData->store();
            } break;
        }

        eZWorkflowEventType::storeEventData( $event, $version );
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

    /*
     \reimp
    */
    function customWorkflowEventHTTPAction( $http, $action, $workflowEvent )
    {
        $eventID = $workflowEvent->attribute( "id" );
        $module = $GLOBALS['eZRequestedModule'];

        $eventData = $this->attributeDecoder( $workflowEvent, 'data' );

        switch ( $action )
        {
            case "AddApproveUsers" :
            {
                $approveINI = eZINI::instance( 'ezapprove2.ini' );
                #include_once( 'kernel/classes/ezcontentbrowse.php' );
                eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleUsers',
                                                'from_page' => '/workflow/edit/' . $workflowEvent->attribute( 'workflow_id' ),
                                                'custom_action_data' => array( 'event_id' => $eventID,
                                                                               'browse_action' => $action ),
                                                'class_array' => $approveINI->variable( 'ApproveSettings', 'UserClassIdentifierList' ) ),
                                         $module );
            } break;

            case "RemoveApproveUsers" :
            {
                if ( $http->hasPostVariable( 'DeleteApproveUserIDArray_' . $eventID ) )
                {
                    $eventData->removeApproveUserList( $http->postVariable( 'DeleteApproveUserIDArray_' . $eventID ) );
                    $eventData->store();
                }
            } break;

            case "AddApproveGroups" :
            {
                $approveINI = eZINI::instance( 'ezapprove2.ini' );
                #include_once( 'kernel/classes/ezcontentbrowse.php' );
                eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleUsers',
                                                'from_page' => '/workflow/edit/' . $workflowEvent->attribute( 'workflow_id' ),
                                                'custom_action_data' => array( 'event_id' => $eventID,
                                                                               'browse_action' => $action ),
                                                'class_array' => $approveINI->variable( 'ApproveSettings', 'UserGroupClassIdentifierList' ) ),
                                         $module );
            } break;

            case "RemoveApproveGroups" :
            {
                if ( $http->hasPostVariable( 'DeleteApproveGroupIDArray_' . $eventID ) )
                {
                    $eventData->removeApproveGroupList( $http->postVariable( 'DeleteApproveGroupIDArray_' . $eventID ) );
                    $eventData->store();
                }
            } break;

            case "AddExcludeUser" :
            {
                $approveINI = eZINI::instance( 'ezapprove2.ini' );
                #include_once( 'kernel/classes/ezcontentbrowse.php' );
                eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleUsers',
                                                'from_page' => '/workflow/edit/' . $workflowEvent->attribute( 'workflow_id' ),
                                                'custom_action_data' => array( 'event_id' => $eventID,
                                                                               'browse_action' => $action ),
                                                'class_array' => $approveINI->variable( 'ApproveSettings', 'UserGroupClassIdentifierList' ) ),
                                         $module );
            } break;

            case "RemoveExcludeUser" :
            {
                if ( $http->hasPostVariable( 'DeleteExcludeUserIDArray_' . $eventID ) )
                {
                    $eventData->removeSelectedUserList( $http->postVariable( 'DeleteExcludeUserIDArray_' . $eventID ) );
                    $eventData->store();
                }
            } break;

        }
    }

    /*
     \reimp
    */
    function cleanupAfterRemoving( $attr = array() )
    {
        foreach ( array_keys( $attr ) as $attrKey )
        {
            switch ( $attrKey )
            {
                case 'DeleteContentObject':
                {
                    $contentObjectID = $attr[ $attrKey ];
                    $db = eZDb::instance();
                    // Cleanup "User who approves content"
                    $db->query( 'UPDATE ezworkflow_event
                                  SET    data_int1 = \'0\'
                                  WHERE  data_int1 = \'' . $contentObjectID . '\''  );
                    // Cleanup "Excluded user groups"
                    $excludedGroupsID = $db->arrayQuery( 'SELECT data_text2, id
                                                           FROM   ezworkflow_event
                                                           WHERE  data_text2 like \'%' . $contentObjectID . '%\'' );
                    if ( count( $excludedGroupsID ) > 0 )
                    {
                        foreach ( $excludedGroupsID as $groupID )
                        {
                            // $IDArray will contain IDs of "Excluded user groups"
                            $IDArray = split( ',', $groupID[ 'data_text2' ] );
                            // $newIDArray will contain  array without $contentObjectID
                            $newIDArray = array_filter( $IDArray, create_function( '$v', 'return ( $v != ' . $contentObjectID .' );' ) );
                            $newValues = implode( ',', $newIDArray );
                            $db->query( 'UPDATE ezworkflow_event
                                          SET    data_text2 = \''. $newValues .'\'
                                          WHERE  id = ' . $groupID[ 'id' ] );
                        }
                    }
                } break;
            }
        }
    }

    function checkApproveCollaboration( $process, $event )
    {
        $db = eZDb::instance();
        $taskResult = $db->arrayQuery( 'select workflow_process_id, collaboration_id from ezapprove_items where workflow_process_id = ' . $process->attribute( 'id' )  );
        $collaborationID = $taskResult[0]['collaboration_id'];
        $collaborationItem = eZCollaborationItem::fetch( $collaborationID );
        $contentObjectVersion = eZApproveCollaborationHandler::contentObjectVersion( $collaborationItem );
        $approvalStatus = eZApproveCollaborationHandler::checkApproval( $collaborationID );
        if ( $approvalStatus == eZApproveCollaborationHandler::STATUS_WAITING )
        {
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, 'approval still waiting' );
            return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
        }
        else if ( $approvalStatus == eZApproveCollaborationHandler::STATUS_ACCEPTED )
        {
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, 'approval was accepted' );
            $status = eZWorkflowType::STATUS_ACCEPTED;
        }
        else if ( $approvalStatus == eZApproveCollaborationHandler::STATUS_DENIED or
                  $approvalStatus == eZApproveCollaborationHandler::STATUS_DEFERRED )
        {
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, 'approval was denied' );
            $contentObjectVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
            $status = eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }
        else
        {
            eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, "approval unknown status '$approvalStatus'" );
            $contentObjectVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_REJECTED );
            $status = eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }
        $contentObjectVersion->sync();
        if ( $approvalStatus != eZApproveCollaborationHandler::STATUS_DEFERRED )
            $db->query( 'DELETE FROM ezapprove_items WHERE workflow_process_id = ' . $process->attribute( 'id' )  );
        return $status;
    }
}

eZWorkflowEventType::registerEventType( eZApprove2Type::ID, "ezapprove2type" );

?>
