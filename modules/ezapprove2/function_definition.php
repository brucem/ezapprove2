<?php
//
// Created on: <05-Jan-2006 13:04:38 hovik>
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

/*! \file function_definition.php
*/

$extension = 'ezapprove2';
$base = eZExtension::baseDirectory();
$baseDir = "$base/$extension/modules/ezapprove2/";

$FunctionList = array();

$FunctionList['is_approver'] = array( 'name' => 'is_approver',
                                      'operation_types' => array( 'read' ),
                                      'call_method' => array( 'include_file' => $baseDir . 'ezapprovefunctioncollection.php',
                                                              'class' => 'eZApproveFunctionCollection',
                                                              'method' => 'isApprover' ),
                                      'parameter_type' => 'standard',
                                      'parameters' => array( array( 'name' => 'approve_status_id',
                                                                    'type' => 'integer',
                                                                    'required' => true ),
                                                             array( 'name' => 'user_id',
                                                                    'type' => 'integer',
                                                                    'required' => false,
                                                                    'default' => eZUser::currentUserID() ) ) );

$FunctionList['have_set_status'] = array( 'name' => 'have_set_status',
                                          'operation_types' => array( 'read' ),
                                          'call_method' => array( 'include_file' => $baseDir . 'ezapprovefunctioncollection.php',
                                                                  'class' => 'eZApproveFunctionCollection',
                                                                  'method' => 'haveSetStatus' ),
                                          'parameter_type' => 'standard',
                                          'parameters' => array( array( 'name' => 'collabitem_id',
                                                                        'type' => 'integer',
                                                                        'required' => false,
                                                                        'default' => false ),
                                                                 array( 'name' => 'approve_status_id',
                                                                        'type' => 'integer',
                                                                        'required' => false,
                                                                        'default' => false ),
                                                                 array( 'name' => 'user_id',
                                                                        'type' => 'integer',
                                                                        'required' => false,
                                                                        'default' => eZUser::currentUserID() ) ) );

$FunctionList['approve_status'] = array( 'name' => 'approve_status',
                                         'operation_types' => array( 'read' ),
                                         'call_method' => array( 'include_file' => $baseDir . 'ezapprovefunctioncollection.php',
                                                                 'class' => 'eZApproveFunctionCollection',
                                                                 'method' => 'approveStatus' ),
                                         'parameter_type' => 'standard',
                                         'parameters' => array( array( 'name' => 'collaboration_id',
                                                                       'type' => 'integer',
                                                                       'required' => false,
                                                                       'default' => false ),
                                                                array( 'name' => 'contentobject_id',
                                                                       'type' => 'integer',
                                                                       'required' => false,
                                                                       'default' => false ),
                                                                array( 'name' => 'contentobject_version',
                                                                       'type' => 'integer',
                                                                       'required' => false,
                                                                       'default' => false ) ) );


$FunctionList['approve_status_map'] = array( 'name' => 'approve_status_map',
                                             'operation_types' => array( 'read' ),
                                             'call_method' => array( 'include_file' => $baseDir . 'ezapprovefunctioncollection.php',
                                                                     'class' => 'eZApproveFunctionCollection',
                                                                     'method' => 'approveStatusMap' ),
                                             'parameter_type' => 'standard',
                                             'parameters' => array( ) );

?>
