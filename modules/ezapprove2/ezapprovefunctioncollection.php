<?php
//
// Definition of eZApproveFunctionCollection class
//
// Created on: <05-Jan-2006 15:04:29 hovik>
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

/*! \file ezapprovefunctioncollection.php
*/

/*!
  \class eZApproveFunctionCollection ezapprovefunctioncollection.php
  \brief The class eZApproveFunctionCollection does

*/
#include_once( eZExtension::baseDirectory() . '/ezapprove2/classes/ezxapprovestatus.php' );

class eZApproveFunctionCollection
{
    /*!
     \static

     Check if a user is an approver in the specified eZXApproveStatus
     */
    function isApprover( $approveStatusID, $userID )
    {
        $result = false;
        if ( eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                      $approveStatusID,
                                                      eZXApproveStatusUserLink::RoleApprover ) )
        {
            $result = true;
        }

        return array( 'result' => $result );
    }

    /*!
     \static

     Check if the user has set the approve status
    */
    function haveSetStatus( $collabItemID, $approveStatusID, $userID )
    {
        $result = false;

        if ( $collabItemID !== false )
        {
            $approveUserLink = eZXApproveStatusUserLink::fetchByCollaborationID( $userID, $collabItemID, false );
        }

        if ( $approveStatusID !== false )
        {
            $approveUserLink = eZXApproveStatusUserLink::fetchByUserID( $userID,
                                                                        $approveStatusID,
                                                                        false );
        }

        if ( $approveUserLink )
        {
            if ( $approveUserLink->attribute( 'approve_status' ) != eZXApproveStatusUserLink::StatusNone )
            {
                $result = true;
            }
        }

        return array( 'result' => $result );

    }

    /*!
    Fetch eZXApproveStatus object by collaboration ID

    \param collaboration ID

    \return eZXApproveStatus object
    */
    function approveStatus( $collaborationItemID = false, $contentObjectID = false, $contentObjectVersion = false )
    {
        $result = false;
        if ( $collaborationItemID !== false )
        {
            $result = eZXApproveStatus::fetchByCollaborationItem( $collaborationItemID );
        }
        else if ( $contentObjectID !== false )
        {
            $result = eZXApproveStatus::fetchByContentObjectID( $contentObjectID, $contentObjectVersion );
        }

        return array( 'result' => $result );
    }

    /*!
     Get Approve status name map
    */
    static function approveStatusMap()
    {
        return array( 'result' => eZXApproveStatus::statusNameMap() );
    }
}

?>
