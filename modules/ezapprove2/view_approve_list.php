<?php
//
// Created on: <04-Jan-2006 13:18:42 hovik>
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

/*! \file view_approve_list.php
*/

include_once( 'kernel/common/template.php' );

#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezapprove2event.php' );

$Module = $Params['Module'];

$http = eZHTTPTool::instance();

$userParameters = $Params['UserParameters'];
$statusFilter = isset( $userParameters['statusFilter'] ) ? explode( ',', $userParameters['statusFilter'] ) : array( -1 );
$offset = isset( $userParameters['offset'] ) ? $userParameters['offset'] : 0;
$limitKey = isset( $userParameters['limit'] ) ? $userParameters['limit'] : '1';
$limitList = array ( '1' => 10,
                     '2' => 25,
                     '3' => 50 );

$limit = $limitList[(string)$limitKey];

$viewParameters = array( 'offset' => $offset,
                         'limitkey' => $limitKey );

$userID = eZUser::currentUserID();
$approveStatusList = eZXApproveStatus::fetchListByUserID( $userID, $offset, $limit );
$approveStatusCount = eZXApproveStatus::fetchCountByUserID( $userID );

$allowedApproveStatusList = array( eZXApproveStatusUserLink::StatusApproved,
                                   eZXApproveStatusUserLink::StatusDiscarded );

if ( $http->hasPostVariable( 'UpdateApproveStatusList' ) )
{
    if( is_array( $approveStatusList ) )
    {
        foreach( $approveStatusList as $approveStatus )
        {
            if ( $http->hasPostVariable( 'ApproveStatus_' . $approveStatus->attribute( 'id' ) ) )
            {
                if ( in_array( $http->postVariable( 'ApproveStatus_' . $approveStatus->attribute( 'id' ) ),
                               $allowedApproveStatusList ) )
                {
                    $userApproveStatus = $approveStatus->attribute( 'user_approve_status' );
                    $userApproveStatus->setAttribute( 'approve_status', $http->postVariable( 'ApproveStatus_' . $approveStatus->attribute( 'id' ) ) );
                    $userApproveStatus->sync();
                }
            }
        }
    }
}

$tpl = templateInit();
$tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'approve_status_list', $approveStatusList );
$tpl->setVariable( 'approve_status_count', $approveStatusCount );
$tpl->setVariable( 'status_name_map', eZXApproveStatusUserLink::statusNameMap() );
$tpl->setVariable( 'limit', $limit );

$Result = array();
$Result['content'] = $tpl->fetch( "design:workflow/eventtype/ezapprove2/view_approve_list.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'ezapprove2', 'Approve List' ) ) );


?>
