<?php
//
// Definition of eZApproveExtendedFilter class
//
// Created on: <31-Jan-2006 14:41:50 hovik>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*! \file ezapproveextendedfilter.php
*/

/*!
  \class eZApproveExtendedFilter ezapproveextendedfilter.php
  \brief The class eZApproveExtendedFilter does

*/

class eZApproveExtendedFilter
{
    /*!
     Constructor
    */
    function __construct()
    {
    }

    /*!
     Create SQL parts
    */
    static function createSqlParts( $params )
    {
        $db = eZDB::instance();

        $sqlTables = '';
        $sqlJoins = '';
        if ( isset( $params['status'] ) )
        {
            $sqlTables = ', ezx_approve_status ';

            $sqlJoins = ' ( ezx_approve_status.contentobject_id = ezcontentobject.id AND
                            ezx_approve_status.active_version = ezcontentobject_version.version AND
                            ezx_approve_status.approve_status ';

            if ( is_array( $params['status'] ) )
            {
                $sqlJoins .= 'IN ( \'' . implode( '\', \'', $params['status'] ) . '\' )';
            }
            else
            {
                $sqlJoins .= '= \'' . $db->escapeString( $params['status'] ) . '\'';
            }
            $sqlJoins .= '  ) AND';
        }

        return array( 'tables' => $sqlTables,
                      'joins'  => $sqlJoins );
    }
}

?>
