CREATE TABLE ezx_approve2_event (
  workflowevent_id int(11) NOT NULL default '0',
  workflowevent_version int(11) NOT NULL default '0',
  selected_sections varchar(255) NOT NULL default '',
  approve_users varchar(255) NOT NULL default '',  
  approve_groups varchar(255) default '',
  approve_type int(11) NOT NULL default '0',
  selected_usergroups varchar(255) NOT NULL default '',
  allow_add_approver int(11) NOT NULL default '0',
  num_approve_users int(11) NOT NULL default '0',
  require_all_approve int(11) NOT NULL default '0',
  PRIMARY KEY( workflowevent_id, workflowevent_version ) );

CREATE TABLE ezx_approve_status (
  id int(11) NOT NULL auto_increment,
  step int(11) NOT NULL default '0',
  contentobject_id int(11) NOT NULL default '0',
  contentobject_status int(11) NOT NULL default '0',
  approve_status int(11) NOT NULL default '0',
  active_version int(11) NOT NULL default '0',
  locked_version int(11) NOT NULL default '0',
  locked int(11) NOT NULL default '0',
  collaborationitem_id int(11) NOT NULL default '0',
  locked_user_id int(11) NOT NULL default '0',
  started int(11) NOT NULL default '0',
  ended int(11) NOT NULL default '0',
  workflowprocess_id int(11) NOT NULL default '0',
  event_position int(11) NOT NULL default '0',
  required_num_approvers int(11) NOT NULL default '0',
  PRIMARY KEY( id, step ) );

CREATE TABLE ezx_approve_status_user_link (
  id int(11) NOT NULL auto_increment,
  approve_id int(11) NOT NULL default '0',
  approve_status int(11) NOT NULL default '0',
  approve_role int(11) NOT NULL default '0',
  hash char(32) DEFAULT '',
  message_link_created int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  action int(11) NOT NULL default '0',
  PRIMARY KEY( id ) );
