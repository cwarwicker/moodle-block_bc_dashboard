<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Dashboard Reporting
 *
 * The Reporting Dashboard plugin is a block which runs alongside the ELBP and Grade Tracker blocks, to provide a better experience and extra features, 
 * such as combined reporting across both plugins. It also allows you to create your own custom SQL reports which can be run on any aspect of Moodle.
 * 
 * @package     block_bc_dashboard
 * @copyright   2017-onwards Conn Warwicker
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 * @link        https://github.com/cwarwicker/moodle-block_elbp
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Originally developed at Bedford College, now maintained by Conn Warwicker
 * 
 */

$string['bc_dashboard:addinstance'] = 'Add the bc_dashboard block to a course';
$string['bc_dashboard:assign_report_categories'] = 'Assign your reports to public categories for others to see';
$string['bc_dashboard:config'] = 'Configure the bc_dashboard settings';
$string['bc_dashboard:crud_built_report'] = 'Create, Read, Update and Delete your own builder reports';
$string['bc_dashboard:crud_sql_report'] = 'Create, Read, Update and Delete your own SQL reports';
$string['bc_dashboard:delete_any_built_report'] = 'Delete any builder reports';
$string['bc_dashboard:delete_any_sql_report'] = 'Delete any SQL reports';
$string['bc_dashboard:edit_any_built_report'] = 'Edit any builder reports';
$string['bc_dashboard:edit_any_sql_report'] = 'Edit any SQL reports';
$string['bc_dashboard:edit_report_schedule'] = 'Edit the schedule of a report'; // todo
$string['bc_dashboard:edit_any_report_schedule'] = 'Edit the scheule or any report';
$string['bc_dashboard:export_reports'] = 'Export the report structure as an XML file, to be imported onto another Moodle instance';
$string['bc_dashboard:import_reports'] = 'Import repot structures from the exported XML files';
$string['bc_dashboard:run_reports'] = 'Run any of the reports you have access to view';
$string['bc_dashboard:view_reports'] = 'View any reports created by yourself, or that are publically accessible';
$string['bc_dashboard:view_bc_dashboard'] = 'View the bc_dashboard system';


$string['action'] = 'Action';
$string['add'] = 'Add';
$string['addstudents'] = 'Add Students';
$string['addstudents:desc'] = 'Add students to your %s list';
$string['additionalsupport'] = 'Additional Support';
$string['allstudents'] = 'All Students';
$string['areyousuredeletereport'] = 'Are you sure you want to delete this report?';
$string['asap'] = 'ASAP';
$string['assign'] = 'Assign';
$string['average'] = 'Average';
$string['buildreport'] = 'Build Report';
$string['buildreport:desc'] = 'Use the Report Builder to select which elements and filters you want to use, then generate your report.';
$string['chart'] = 'Chart';
$string['child'] = 'Child Courses';
$string['choosecourse'] = 'Start typing to find a course...';
$string['choosecoursecat'] = 'Choose a course category...';
$string['chooseelement'] = 'Choose an element...';
$string['choosefilter'] = 'Choose a filter...';
$string['chooseuser'] = 'Start typing to find a user...';
$string['comparison'] = 'Comparison';
$string['configuration'] = 'Configuration';
$string['configsaved'] = 'Configuration Saved';
$string['course'] = 'Course';
$string['courses'] = 'Courses';
$string['coursetype'] = 'Course Type';
$string['create'] = 'Create';
$string['created'] = 'Created by';
$string['createnewreport'] = 'Create new report';
$string['daily'] = 'Daily';
$string['delete'] = 'Delete';
$string['deletereport'] = 'Delete Report';
$string['default'] = 'Default';
$string['defaultreportname'] = 'New Report';
$string['description'] = 'Description';
$string['dashboard'] = 'Dashboard';
$string['datasource'] = 'Data Source';
$string['details'] = 'Details';
$string['displayname'] = 'Display Name';
$string['edit'] = 'Edit';
$string['element'] = 'Element';
$string['elements'] = 'Elements';
$string['equals'] = 'Equals';
$string['enabledisable'] = 'Enable/Disable';
$string['entersql'] = 'Enter SQL Query';
$string['error'] = 'Error';
$string['error:builderreport:elementoption'] = 'Element (%s), Option number (%s) is missing a value';
$string['error:createdir'] = 'Cannot create data directory';
$string['error:createfile'] = 'Cannot create file';
$string['error:delete'] = 'Could not complete deletion';
$string['error:importreport'] = 'Cannot upload file';
$string['error:importreport:mime'] = 'Invalid mime type, expected: application/xml or text/plain, found: {$a}';
$string['error:importreport:load'] = 'Cannot load XML file';
$string['error:invalidaccess'] = 'Invalid Access Permissions';
$string['error:invalidreport'] = 'Invalid Report';
$string['error:invaliduser'] = 'Invalid Username';
$string['error:report:name'] = 'Report name must be filled out';
$string['error:report:query'] = 'SQL Query must be filled out';
$string['error:report:run'] = 'Error running report. Please check your report settings.';
$string['error:report:sqlparams'] = 'Found %d parameters in SQL query, but %d were submitted. Make sure you "Refresh Parameters" before saving the query';
$string['error:report:startingpoint'] = 'A category starting point must be set';
$string['error:unknownsaveerror'] = 'Unable to save record for unknown reason';
$string['export'] = 'Export';
$string['failedsendmessageto'] = 'Failed to send message to';
$string['fatalerror'] = 'Fatal Error';
$string['field'] = 'Field';
$string['filloutparam'] = 'Please enter a value for the parameter - ';
$string['filters'] = 'Filters';
$string['firstname'] = 'Firstname';
$string['format'] = 'Format';
$string['import'] = 'Import';
$string['info'] = 'Info';
$string['invalidaccess'] = 'Invalid Access Permissions';
$string['lastname'] = 'Lastname';
$string['lastrun'] = 'Last run by';
$string['lastruntime'] = 'Last run';
$string['limit'] = 'Limit';
$string['load'] = 'Load';
$string['location'] = 'Location';
$string['logs'] = 'Logs';
$string['logout'] = 'Logout';
$string['massactions'] = 'Mass Actions';
$string['messagesentto'] = 'Message sent to';
$string['messagestudents'] = 'Message Student(s)';
$string['mentees'] = 'Mentees';
$string['monthly'] = 'Monthly';
$string['myprivatereports'] = 'Private Reports';
$string['myreports'] = 'My Reports';
$string['mysettings'] = 'My Settings';
$string['mystudents'] = 'My Students';
$string['nextruntime'] = 'Next run';
$string['nodata'] = 'No data found';
$string['noelements'] = 'No elements found';
$string['nopermissionscrudreports'] = 'You do not have permissions to create new reports';
$string['noreports'] = 'No reports found';
$string['nosubject'] = 'No Subject';
$string['notequals'] = 'Not Equals';
$string['numberofcourses'] = 'No. Courses';
$string['numberofstudents'] = 'No. Students';
$string['name'] = 'Name';
$string['options'] = 'Options';
$string['pagenotfound'] = 'Page not found';
$string['parameter'] = 'Parameter';
$string['parameters'] = 'Parameters';
$string['parentstandard'] = 'Parent/Standard Courses';
$string['percent'] = 'Percent';
$string['plsselectstudents'] = 'Please select a list of students to view, from the menu on the left hand side of the page';
$string['plugin'] = 'Plugin';
$string['pluginname'] = 'Dashboard';
$string['preview'] = 'Preview';
$string['private'] = 'Private';
$string['profile'] = 'Profile';
$string['public'] = 'Public';
$string['publicreports'] = 'Public Reports';
$string['recentactivity'] = 'Recent Activity';
$string['removedstudent'] = 'Removed student';
$string['removestudent:error'] = 'Error removing student';
$string['removestudents'] = 'Remove Student(s)';
$string['removestudents:sure'] = 'Are you sure you want to remove these students?';
$string['rename'] = 'Rename';
$string['repetition'] = 'Repetition';
$string['report'] = 'Report';
$string['reportcats'] = 'Report Categories';
$string['reportcoursecats'] = 'Reporting Course Categories';
$string['reportdeleted'] = 'Report Deleted';
$string['reportelements'] = 'Report Elements';
$string['reporting'] = 'Reporting';
$string['reports'] = 'Reports';
$string['reportsaved'] = 'Report Saved';
$string['reporttypes:standard'] = 'Standard';
$string['reporttypes:chart/bar'] = 'Chart - Bar Chart';
$string['reporttypes:chart/line'] = 'Chart - Line Chart';
$string['reporttypes:chart/area'] = 'Chart - Area Chart';
$string['reportoption:plugin'] = 'Plugin';
$string['reportoption:fields'] = 'Fields';
$string['reportoption:groupby'] = 'Group By Field';
$string['reportoption:groupbyvalue'] = 'Group By Value';
$string['reportoption:count'] = 'Count Method';
$string['results'] = 'Results';
$string['returndashboard'] = 'Return to Dashboard';
$string['run'] = 'Run';
$string['runfromedit'] = 'If you Run this report now, any unsaved changes will be lost. Are you sure you want to Run the report?';
$string['save'] = 'Save';
$string['scan'] = 'Scan';
$string['scanforelements'] = 'Scan for elements';
$string['schedule'] = 'Schedule';
$string['scheduling'] = 'Scheduling';
$string['scheduledtasksaved'] = 'Scheduled Task Saved';
$string['selectaction'] = 'Select Action...';
$string['selectedelements'] = 'Selected Elements';
$string['selectedfilters'] = 'Selected Filters';
$string['selectedstudents'] = 'Selected Students';
$string['sendmessage'] = 'Send Message';
$string['sendto'] = 'Send to';
$string['separateemailscomma'] = 'Separate users with a comma, e.g. jsmith,brichards,dsingh';
$string['scheduledby'] = 'Scheduled by';
$string['scheduledtask:message'] = "Hello,
    
    Your report has been successfully generated!
    You should find the file attached to this email, if not, please use the following link to download it: 
    <a href='%url%'>%url%</a>
    
    Have a nice day!";
$string['scheduledtask:subject'] = 'Automated Report Generation';
$string['settings'] = 'Settings';
$string['specificdate'] = 'Specific Date';
$string['sqlreport'] = 'SQL Report';
$string['standard'] = 'Standard';
$string['startingpoint'] = 'Starting Point';
$string['subject'] = 'Subject';
$string['submit'] = 'Submit';
$string['task'] = 'Task';
$string['task:run_scheduled_reports'] = 'Run Scheduled Reports';
$string['togglenav'] = 'Toggle navigation';
$string['total'] = 'Total';
$string['type'] = 'Type';
$string['xaxis'] = 'X-Axis';
$string['update'] = 'Update';
$string['updateparams'] = 'Refresh Parameters';
$string['uncategorised'] = 'Uncategorised';
$string['userfilters'] = 'User Filters';
$string['username'] = 'Username';
$string['value'] = 'Value';
$string['view'] = 'View';
$string['viewstudents:'] = 'View Students';
$string['viewstudents:mentees'] = 'View My Mentees';
$string['viewstudents:additionalsupport'] = 'View My Additional Support Students';
$string['viewstudents:course'] = 'View Course';
$string['viewstudents:all'] = 'View All My Students';
$string['viewstudents:admin'] = 'View Any Student';
$string['visibility'] = 'Visibility';
$string['weekly'] = 'Weekly';
$string['writesqlreport'] = 'Write SQL Report';
$string['writesqlreport:desc'] = 'Write an SQL statement to query your database directly and produce a report with your selected data.';
$string['yaxis'] = 'Y-Axis';