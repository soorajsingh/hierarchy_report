<?php

require_once('../../../config.php');
require_once("$CFG->libdir/formslib.php");
global $CFG, $DB,$USER;
require_login();

if(!(has_capability('local/hierarchy:course_report_access', context_system::instance()))) { //check capability 
    redirect(new moodle_url('/my'));
}


$page=optional_param('page', 0, PARAM_INT);
$perpage=optional_param('perpage', 10, PARAM_INT);
$offset = $page*$perpage;


$coursename_filter = optional_param('coursename_filter','', PARAM_TEXT);
$coursecat_filter = optional_param('coursecat_filter', '', PARAM_TEXT);
$startdate_filter = optional_param('startdate_filter', 0, PARAM_INT);
$enddate_filter = optional_param('enddate_filter', 0, PARAM_INT);


$PAGE->requires->css(new moodle_url('/local/hierarchy/reports/style/table.css'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Course report');
$PAGE->set_heading('Course report');
$PAGE->set_pagelayout('standard');


$PAGE->set_url('/local/hierarchy/reports/user_course_completion.php',array('page'=>$page,'perpage'=>$perpage,'coursename_filter'=>$coursename_filter,'coursecat_filter'=>$coursecat_filter,'startdate_filter'=>$startdate_filter,'enddate_filter'=>$enddate_filter));// shortened url must start with backslash 


 class simplehtml_form extends moodleform{
   		 public function definition() 
		{
			global $CFG,$coursename_filter,$coursecat_filter,$startdate_filter,$enddate_filter;

			$mform = $this->_form;
			//$buttonarray=array();
		        $mform->addElement('header','filterhead', 'Filter');
          		$mform->setExpanded('filterhead', 0);	

$mform->addElement('html','<div class="amit-form">');
				$mform->addElement('html','<div class="row">');
					$mform->addElement('html','<div class="col-md-6">');				
						$mform->addElement('text','course_filter','Course Name',array('size'=>'40'));
					$mform->addElement('html','</div>');
						
					$mform->addElement('html','<div class="col-md-6">');	
						$mform->addElement('text','cat_filter','Category Name',array('size'=>'40'));
					$mform->addElement('html','</div>');

					
					$mform->addElement('html','<div class="col-md-6">');	
						$mform->addElement('date_selector','start_filter','Start Date',array('size'=>'40'));
						$year=2000;
						$month=1; 
						$day=1;
						$defaulttime = make_timestamp($year, $month, $day);
						$mform->setDefault('start_filter',  $defaulttime);
					$mform->addElement('html','</div>');
					
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('date_selector','end_filter','End Date',array('size'=>'40'));

						$year=2050;
						$month=1; 
						$day=1;
						$defaulttime = make_timestamp($year, $month, $day);
						$mform->setDefault('end_filter',  $defaulttime);
					$mform->addElement('html','</div>');
				$mform->addElement('html','</div>');
				
				
				$mform->addElement('html','<div class="col-md-row">');
					$mform->addElement('html','<div class="col-md-12 cus-ami-right-form">');
						$mform->addElement('cancel','clear','Clear');
						$mform->addElement('submit','filter','Filter');
					$mform->addElement('html','</div>');
				$mform->addElement('html','</div>');
			
		$mform->addElement('html','</div>');

		}
	}

	$mform = new simplehtml_form();
	
    if($mform->is_cancelled())
    {

    	$url= new moodle_url('/local/hierarchy/reports/user_course_completion.php',array('page'=>$page,'perpage'=>$perpage,'coursename_filter'=>$coursename_filter,'coursecat_filter'=>$coursecat_filter,'startdate_filter'=>$startdate_filter,'enddate_filter'=>$enddate_filter)); 
        
    	redirect($url);

    }  

    if ($formdata = $mform->get_data()) 
	{
           $coursename_filter = $formdata->course_filter;
           $coursecat_filter  = $formdata->cat_filter;
           $startdate_filter  = $formdata->start_filter;
           $enddate_filter    = $formdata->end_filter;

	}
    echo $OUTPUT->header();
	
/////////
$tabs = array();
$row = array();
$activated = array();
$inactive=array();
$currenttab='Course';
 
 
 
$pending1url = new moodle_url('/local/hierarchy/reports/user_table.php');
$row[] = new tabobject('User', $pending1url->out(), 'User Report');

$pending2url = new moodle_url('/local/hierarchy/reports/user_course_completion.php');
$row[] = new tabobject('Course', $pending2url->out(),  'Course Report');


$tabs[] = $row;
$activated[] = $currenttab;
print_tabs($tabs, $currenttab, $inactive, $activated);
	
////////	
	
	
	

    $systemcontext = context_system::instance();
    if(has_capability('local/hierarchy:course_report_filter',$systemcontext))
    {
    	$mform->display();
	}

    $systemcontext = context_system::instance();
    if(has_capability('local/hierarchy:course_report_download',$systemcontext))
    {
			echo html_writer::start_tag('div',array('class'=>'span6 span6-custom')).
			html_writer::start_tag('a',array('href'=>'#','class'=>'download')).html_writer::start_tag('img',array('class'=>'iconimg','src'=>'style/img/dowload.svg')).html_writer::end_tag('a').
		html_writer::end_tag('div');
    }

$condition = " where 1=1";

if($coursename_filter != '')
{
	$condition .= " AND fullname LIKE '%$coursename_filter%'";
}
if ($coursecat_filter != '')  
{
	$condition .= " AND name LIKE '%$coursecat_filter%'";
}

if($startdate_filter != 0)
{
   $condition .= " AND startdate > $startdate_filter";
}
if($enddate_filter != 0)
{
	$condition .= " AND enddate < $enddate_filter" ;
}


		$CurrentUser=$DB->get_record('user',array('id'=>$USER->id));
		$departement = $CurrentUser->dept;
		$branch = $CurrentUser->branch;
		$departement_obj=$DB->get_record('loc',array('id'=>$departement));
		$depts= NULL;
		$deptes=array();
		if($departement_obj){
			$dpath=$departement_obj->path;
			$selectrecord = ' path like ? '; //is put into the where clause
			$result = $DB->get_records_select_menu('loc', $selectrecord, array("$dpath/%"),'id','id,fullname');
			$result[$departement_obj->id]=$departement_obj->fullname;
			if(count($result))
			{
				$depts=implode(",",array_keys($result));
				$deptes=explode(",",$depts);
			}
		}
		
		

$table = new html_table();
$table->attributes['class'] = 'table table-striped table-hover table-bordered custom-table';
$table -> head = array('S.N.','Course Id Number','Course Name','Category','Status','Course Has Expired','Start Date','End Date','Course Duration','Subscribed Users','Not Started','Not Started(%)','In Progress','In Progress(%)','Completed','Completed(%)');

$i=$offset;
$course_info = $DB->get_records_sql("SELECT cu.id, cu.idnumber AS cuidnumber, cu.category, cu.fullname, cu.visible, cu.startdate, cu.enddate, cu.duration, cc.name, cc.idnumber AS ccidnumber FROM {course} AS cu INNER JOIN {course_categories} as cc ON cu.category = cc.id $condition ORDER BY cu.fullname ASC limit $perpage OFFSET $offset");

$count_course = $DB->get_records_sql("SELECT cu.id, cu.idnumber AS cuidnumber, cu.category, cu.fullname, cu.visible, cu.startdate, cu.enddate, cu.duration, cc.name, cc.idnumber AS ccidnumber FROM {course} AS cu INNER JOIN {course_categories} as cc ON cu.category = cc.id $condition");
$count_course = count($count_course);

$teacherRoleId = $DB->get_record('role', array('shortname' => 'editingteacher'));
$managerRoleId = $DB->get_record('role', array('shortname' => 'manager'));
	  
foreach ($course_info as $course) 
{
    $i++;
	$Subcribed_user    = 0;
	$course_notstart   = 0;
	$course_inprogress = 0;
	$course_completed  = 0;
	$course_notstart_percent   = 0;
	$course_inprogress_percent = 0;
	$course_completed_percent  = 0;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $enrolled = get_enrolled_users($context);

 
  if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)) || (user_has_role_assignment($USER->id, $teacherRoleId->id)))
  { 
    	if(count($enrolled))
    	{ 
    		$Subcribed_user   = count($enrolled);

    		foreach ($enrolled as $enobj) 
    		{	
               $userid = $enobj->id;

               $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!='')) 
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }

                if($status == 0)
                {

                    $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }
                   
                   /*  course completion % calculation block.

                   $total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                    	$occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                    	$occp = 0;
                    }

                    if($occp == 0)
                    {
                   	    $course_notstart++;
        		    }
        		    if($occp > 0 && $occp < 100)
        		    {
                        $course_inprogress++;  
        		    }*/
                }
                
    	    }
             
            if($course_notstart)
            {
             	$course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
             	$course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
             	$course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
    	}

        if($course->visible == 1)
        {
        	$visible = "Published";
        }
        else
        {
        	$visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
        	$t =time();
        	$enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
            
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
            	$has_exp = "Yes";
            }
        }
        else
        {
        	$enddate = "-";
        	$has_exp = "No";
        	
        }

        if($course->ccidnumber != 0)
        {
        	$category_id = $course->ccidnumber;
        }
        else
        {
        	$category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
        	$course_idnumber = $course->cuidnumber;
        }
        else
        {
        	$course_idnumber = "-";
        }

        $course_fullname  	 = $course->fullname;
        $course_category 	 = $course->name;

        //
        $duration = '-';
        if($course->duration != 0) {
            $duration = $course->duration . " min";
        }
        

        $table->data[] = array($i,$course_idnumber,$course_fullname,$course_category,$visible,$has_exp,$startdate,$enddate,$duration,$Subcribed_user,$course_notstart,$course_notstart_percent,$course_inprogress,$course_inprogress_percent,$course_completed,$course_completed_percent);
    }

    //branchpoweruser loop

    else if($CurrentUser->branchpoweruser == 1)
    {
        $enrollbranchpoweruser = array();
        if(count($enrolled) != 0)
        {
            foreach ($enrolled as $obj) 
            {
               if($obj->branch == $CurrentUser->branch)
               {
                  $enrollbranchpoweruser[] = $obj;
               }
            }          
        }   
        if(count($enrollbranchpoweruser))
        { 
            $Subcribed_user  = count($enrollbranchpoweruser);

           foreach ($enrollbranchpoweruser as $enobj) 
            {   
               $userid = $enobj->id;

              $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!=''))
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }
                
                if($status == 0)
                {
                    $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }

                  /*  course completion % calculation block.  

                  $total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                        $occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                        $occp = 0;
                    }

                    if($occp == 0)
                    {
                        $course_notstart++;
                    }
                    if($occp > 0 && $occp < 100)
                    {
                        $course_inprogress++;  
                    }*/
                }
                
            }
             
            if($course_notstart)
            {
                $course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
                $course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
                $course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
        }

       

        if($course->visible == 1)
        {
            $visible = "Published";
        }
        else
        {
            $visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
            $t =time();
            $enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
                $has_exp = "Yes";
            }
        }
        else
        {
            $enddate = "-";
            $has_exp = "No";
            
        }

        if($course->ccidnumber != 0)
        {
            $category_id = $course->ccidnumber;
        }
        else
        {
            $category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
            $course_idnumber = $course->cuidnumber;
        }
        else
        {
            $course_idnumber = "-";
        }

        $course_fullname     = $course->fullname;
        $course_category     = $course->name;

        //
        $duration = '-';
        if($course->duration != 0) {
            $duration = $course->duration . " min";
        }

        $table->data[] = array($i,$course_idnumber,$course_fullname,$course_category,$visible,$has_exp,$startdate,$enddate,$duration,$Subcribed_user,$course_notstart,$course_notstart_percent,$course_inprogress,$course_inprogress_percent,$course_completed,$course_completed_percent);

    } 

        //deptpoweruser loop

     else if($CurrentUser->deptpoweruser == 1)
    {
        $enrolleddeptuser = array();
        if(count($enrolled) != 0)
        {
            foreach ($enrolled as $obj) 
            {
               if(in_array($obj->dept, $deptes))
               {
                  $enrolleddeptuser[] = $obj;
               }
            }
        }   
        if(count($enrolleddeptuser))
        { 
            $Subcribed_user  = count($enrolleddeptuser);

          foreach ($enrolleddeptuser as $enobj) 
            {   
               $userid = $enobj->id;

               $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!=''))
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }
                
                if($status == 0)
                {
                    $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }

                   /*  course completion % calculation block.

                   $total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                        $occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                        $occp = 0;
                    }

                    if($occp == 0)
                    {
                        $course_notstart++;
                    }
                    if($occp > 0 && $occp < 100)
                    {
                        $course_inprogress++;  
                    }*/
                }
                
            }
             
            if($course_notstart)
            {
                $course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
                $course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
                $course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
        }

        if($course->visible == 1)
        {
            $visible = "Published";
        }
        else
        {
            $visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
            $t =time();
            $enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
            
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
                $has_exp = "Yes";
            }
        }
        else
        {
            $enddate = "-";
            $has_exp = "No";
          
        }

        if($course->ccidnumber != 0)
        {
            $category_id = $course->ccidnumber;
        }
        else
        {
            $category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
            $course_idnumber = $course->cuidnumber;
        }
        else
        {
            $course_idnumber = "-";
        }

        $course_fullname     = $course->fullname;
        $course_category     = $course->name;
        
        //
        $duration = '-';
        if($course->duration != 0) {
            $duration = $course->duration . " min";
        }
        
        $table->data[] = array($i,$course_idnumber,$course_fullname,$course_category,$visible,$has_exp,$startdate,$enddate,$duration,$Subcribed_user,$course_notstart,$course_notstart_percent,$course_inprogress,$course_inprogress_percent,$course_completed,$course_completed_percent);
    } 

}

$paging_url = new moodle_url('/local/hierarchy/reports/user_course_completion.php',array('page'=>$page,'perpage'=>$perpage,'coursename_filter'=>$coursename_filter,'coursecat_filter'=>$coursecat_filter,'startdate_filter'=>$startdate_filter,'enddate_filter'=>$enddate_filter));


if(!$count_course)
{
    $a = new html_table_cell("No records found");
    $a->colspan=44;
    $table->data[] = new html_table_row(array($a));
}
	
		echo html_writer::start_tag('div',array('style'=>'overflow:auto; clear:both;'));
		echo html_writer::table($table).
		html_writer::end_tag('div');

echo $OUTPUT->paging_bar($count_course, $page, $perpage, $paging_url);



echo $OUTPUT->footer();
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>

$(document).ready(function() { 
	

	$(".iconimg").click(function(event){

		window.location.href = "user_course_completion_excel.php?conditions=<?php echo urlencode($condition);?>";
	});
				
});

</script>