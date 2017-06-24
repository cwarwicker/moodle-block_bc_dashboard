<?php
require_once '../../config.php';
require_once 'lib.php';
require_once $CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/BuiltReport.php';
require_once $CFG->dirroot . '/blocks/bc_dashboard/components/reporting/builder/classes/Element.php';
//
$Report = new \BCDB\Report\BuiltReport();

require_once $CFG->dirroot . '/blocks/gradetracker/classes/bc_dashboard/avggcse.php';
require_once $CFG->dirroot . '/blocks/gradetracker/classes/bc_dashboard/grade.php';
require_once $CFG->dirroot . '/blocks/gradetracker/classes/bc_dashboard/listofquals.php';
require_once $CFG->dirroot . '/blocks/gradetracker/classes/bc_dashboard/numberofqoe.php';



require_once $CFG->dirroot . '/blocks/elbp/plugins/Attendance/bc_dashboard/attendance.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/numberwithrecords.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/numberwithoutrecords.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/lastupdate.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/numberofrecords.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/singlefield.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Custom/bc_dashboard/multifield.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Targets/bc_dashboard/numberoftargets.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Tutorials/bc_dashboard/numberoftutorials.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Tutorials/bc_dashboard/lasttutorial.php';
require_once $CFG->dirroot . '/blocks/elbp/plugins/Comments/bc_dashboard/numberofcomments.php';


/**
 * 
 * test/
 *      test sub cat/
 *          sub sub cat/
 *              sub sub course @
 *              sub sub course 2 @
 *          Another Course @
 *      sibling sub cat/
 *      Conn's Test Course @
 * 
 */

$Report->addOption("course_type", "child");

//
//
////
////
$Report->setStartingPoint(125);

//$att = new ELBP\bc_dashboard\Comments\numberofcomments();
//$att->setParams( array('all', 'total') );
//$Report->addElement($att);

$att = new ELBP\bc_dashboard\Attendance\attendance();
$att->setParams( array('Attendance', 'Total') );
$Report->addElement($att);
//
//$o = new ELBP\bc_dashboard\Custom\multifield();
//$o->setParams( array( 10, array('JDBRu13n5p', '6bYsa42hQV'), 'ucpj3n1FkR', 'Review 1', 'total' ) );
//$Report->addElement( $o );
//
//$att = new ELBP\bc_dashboard\Attendance\attendance();
//$att->setParams( array('Punctuality', 'Last 7 Days') );
//$Report->addElement($att);


////
$Report->execute();
////
echo "<br><br><hr><br><br>";
var_dump($Report->getData());
//
////$el = \BCDB\Report\Element::load(79);
////var_dump($el);