# General informations
The Social Flow proposes a view on most frequent actions in courses to student and teachers on the Moodle dashboard.
![Dashboard integrating social flow block](img/SocialflowBlockV1Dashboard.png)
It has been designed to stimulate student engagement while indicating the community main actions to help each student to set its own objectives.
The Social Flow view may be customized by each student with several preferences
- reference period : depending on its relation to deadlines, students may configure reference period to 1 day to 2 weeks;![Social flow block course selection](img/SocialflowBlockV1option1.png)
- courses selection : student may decide the Social Flow to present actions occuring in a selection of its courses;![Social flow block reference period selection](img/SocialflowBlockV1option2.png)
- action type : enable the student to display 'consultation' actions (like playing a video), 'contribution' actions (like submitting a quiz attempt) or both; ![Social flow block course selection](img/SocialflowBlockV1option3.png)
- number of lines : defines the number of lines to display in the social flow view.![Social flow block number of libne selection](img/SocialflowBlockV1option4.png)
Activities with closing date are associated to informations about the closing date.
![Social flow closing date informations](img/SocialflowV2.png)

# One view based on two plugins
The Social Flow view is based on two plugins:
- logstore_socialflow: Logs the events to the database and makes all data treatements with a performant approach; you need to install this dependency first
- block_socialflow:  Block to display the Social Flow data on the dashboard.

# Installation

## Requirements:
- Moodle: Version 4.4 or higher (currently testing with 4.4, 4.5 and 5.0)
- Database: MySQL/MariaDB or Postgres (other database types are implemented but have not been tested)

## Download:
First, download and install this plugin (logstore_socialflow). Afterwards, install the block plugin.

## Cutomize the logged events
The social flow stores only a selection of events linked to student actions performed in the courses.
The logged actions are stored in the logstore_socialflow_evts table, that you may augment with your additionnal plugins events.
Events may have 2 types : 
- 'consult' (like \mod_assign\event\course_module_viewed) for actions associates to a student resource or activity consultation,
- 'contrib' (like \mod_assign\event\assessable_submitted) for actions associated to a student activity contribution.
  
The social flow takes into account the activities closing date and late date and information.  Field where closing date and late date are stored are therefore necessary to define a new event:
- moduletable: stores the name of the table where the closing or late date field are stored (often the plugin name but not always)
- hasclosingdate : takes value '0" if no closing date may be associated to this module and takes value '1' the module supports closing date
- closingdatefield : label of the table field storing the closing date (action may no more be proceeded after this date)
- haslatedate : takes value '0" if no late date may be associated to this module and takes value '1' the module supports late date
- closingdatefield : label of the table field storing the late date (action may still be proceeded after this date, but are noted as late)

## Activating the logstore
After installation you need to enable the logstore plugin:
- Go to Moodle Site administration page
- In the Plugins tab, scroll down to Logging
- Click on Manage log stores
- There should be a row for the installed logstore plugin with the name Socialflow Log
- Click on the eye icon (👁) to enable the log store.
The log store is now activated and will log events.

After installation and activation of the logstore, you have to make some actions in courses as a student and then run the 2 cron task associated to social flow so that social flow tables are filled : 
- first run \logstore_socialflow\task\nbpa_task task (computes the number of students in each logstore_socialflow_log table logged course and stores this informations in the logstore_socialflow_nbpa table )
- then run the \logstore_socialflow\task\hits_task task (computes the number of hits for each action in the logstore_socialflow_log table and stores this in the logstore_socialflow_hits table; then get informations about the closing dates of logged activities and stores it in the logstore_socialflow_closing table)
Note that \logstore_socialflow\task\cleanup_task performs data cleanup in the log table.

## Import data from logstore_standard

After installation, all data are empty as no data has been logged so far. But your Moodle site might log data through Moodle's own logging system, the logstore_standard_log. The logstore plugin offers a simple way to import that data by using the import.php script. It can be called from the shell like this:

$ cd MOODLE_PATH/admin/tool/log/store/socialflow
$ php cli/import.php

This will immediately start the import process. 

# Configuration

You only have to configure the log plugin. The logstore plugin has options related to logging data and writing data to the database. The block plugin has no option but need to be added on the Default Dashboard via menu
Administration > Site administration > Appearance > Default Dashboard page

The log plugin settings page can be found in:
Administration > Site Administration > Plugins > Log plugins > Socialflow

The logstore has the following options:
- log_scope: One of all, include, exclude. Defines the scope of the logging process. By default, everything is logged.
        Option all: Logs all events
        Option include: Log events only in courses specified via course_ids
        Option exclude: Log events excluding the courses specified via course_ids
- course_ids: To be used with the log_scope option include or exclude to only track specific courses. Example: 10,153,102.
- tracking_roles: Define which roles should be tracked (whitelist) unless specified via nontracking_roles. This is useful if you only want to track specific roles (like students or guests). By default, all roles are tracked. Example: student,guest. See Role Tracking for more information.
- nontracking_roles: Define which roles should not be tracked. This is useful if you don't want to track specific roles (like managers or teachers). By default, no roles are ignored. Example: teacher,editingteacher,manager. See Role Tracking for more information.
    buffersize: Same as buffersize of other loggers. In case a single page fires more than one event, this is the number of events that will be buffered before writing them to database. Defaults to 50.

# Data privacy

Social Flow plugins log no personal data. 

When an event is triggered inside of Moodle, the following data is logged by the logstore plugin:
Table: logstore_socialflow_log
- id : BIGINT 	 
- eventid : INT (Type of action, e.g. "Resource viewed")
- courseid :	BIGINT (Corresponding course)
- contextid : BIGINT (Corresponding context, e.g. ID of the resource that was viewed)
- timecreated : BIGINT (Date and time, exact to the second)

In addition, there are helper tables, that do not store data-privacy related data and only exist to speed up queries or to minimize storage requirements.

This plugin was developed with data privacy in mind.
It proceeds a new data treatement that is clearly described in the help tab of the block plugin.
![Social flow help](img/SocialflowBlockV1help.png)
This new treatement should be described in the data privacy statement.

# Reference

The Socialflow plugins were developed by [Isabelle Motte](https://www.uclouvain.be/fr/people/isabelle.motte) from [Universtité catholique de Louvain](https://www.uclouvain.be) in the context of a technical development for a career evolution. This development is presented in detail in the associated report :

[Moodle Learning Analytics: how to extract log data efficiently ? Feedback on the development of social flow plugins](LINK)

This work is based on the [Learning Analytics Plugins](https://moodle.org/plugins/local_learning_analytics) developed by Thomas Dondorf at RWTH Aachen University in the context of his PhD dissertation:
>Dondorf, T. (2022). Learning Analytics for Moodle: Facilitating the Adoption of Data Privacy Friendly Learning Analytics in Higher Education (Dissertation). Rheinisch-Westfälische Technische Hochschule Aachen, Aachen. https://doi.org/10.18154/RWTH-2022-04002
