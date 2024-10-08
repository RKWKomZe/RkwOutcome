-- noinspection SqlNoDataSourceInspectionForFile

#
# Table structure for table 'tx_rkwoutcome_domain_model_surveyrequest'
#
CREATE TABLE tx_rkwoutcome_domain_model_surveyrequest (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	frontend_user int(11) unsigned DEFAULT '0',
	target_group int(11) unsigned DEFAULT '0',

	process longtext NOT NULL,
	process_subject longtext NOT NULL,

	survey_configuration int(11) unsigned DEFAULT '0',

	notified_tstamp int(11) unsigned DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_rkwoutcome_domain_model_surveyconfiguration'
#
CREATE TABLE tx_rkwoutcome_domain_model_surveyconfiguration (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

  target_group varchar(255) DEFAULT '' NOT NULL,

  process_type varchar(255) DEFAULT 'RKW\RkwShop\Domain\Model\Product' NOT NULL,
	product int(11) unsigned DEFAULT '0',
	event int(11) unsigned DEFAULT '0',
	survey varchar(255) DEFAULT '' NOT NULL,
	survey_waiting_time int(11) unsigned DEFAULT '0' NOT NULL,

	mail_text text NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
  KEY language (l10n_parent,sys_language_uid)

);
