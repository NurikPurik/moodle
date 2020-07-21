<?php
defined('_JEXEC') or die;
header('Content-Type: text/html; charset=utf-8');   				
if($this->file == 'soap'){
	    
    $query = $this->_db->getQuery(true);
    $query->select('r.ref');
    $query->select('s.code AS s_code');
    $query->select('d.code AS d_code');
    $query->select('d.ref as d_ref');
    $query->select('s.ref as s_ref');
    $query->select('r.stime');
    $query->select('r.course');
    $query->select('r.control_exam_date');
    $query->select('d.description AS dis');
    $query->select('s.description AS spec');
    $query->select('r.lang');
    $query->from('jos_euniversity_cat_teachers_rup AS r');
    $query->from('jos_euniversity_ref_discipline d');
    $query->from('jos_euniversity_cat_edu_specs s');
    $query->where('r.year = 1920');
    $query->where('r.semestr IN (2,4,6,8)');
    $query->where('r.type_schedule = "Экзамен"');
    $query->where('r.control_exam_type = "СП"');
    $query->where('r.apply = 1');
    $query->where('r.active = 1');
    $query->where('r.discipline = d.ref');
    $query->where('r.spec = s.ref');
    //$query->where('d.ref = "d4a99147-93b9-11e8-80e6-002590ea6fbf"');
    $query->order('r.control_exam_date');
  
   //$query->where('d.ref = "7ff444b1-ce01-11e9-80d6-025400b39f57"');

    $this->_db->setQuery($query);
    $arrRup = $this->_db->loadAssocList();
    /*echo count($arrRup);
    echo '<pre>';
    print_r($arrRup);
    echo '</pre>';*/

foreach ($arrRup as $key => $value) {
	foreach ($arrRup as $key1 => $value1) {
		if($value['d_ref'] == $value1['d_ref'] and $value['lang'] == $value1['lang'])
		{
			$courses[] = $value1;				
		}
	}
}
	/*echo count($courses);
  	echo '<pre>';
    print_r($courses);
    echo '</pre>';*/



	$option['driver'] = 'mysql';
	$option['host'] = '';
	$option['user'] = '';
	$option['password'] = '';
	$option['database'] = '';
	$option['prefix'] = 'mdl_';

	$db = & JDatabase::getInstance($option);
	if ( JError::isError($db) ) {
	        jexit('Database Error: ' . $db->toString() );
	}
	if ($db->getErrorNum() > 0) {
	        JError::raiseError(500, 'JDatabase::getInstance: Could not connect to database <br />');
	}

 $serverurl = "";
 $soapClient = new SoapClient($serverurl);
 		///СОЗДАЕМ КУРС
 		$mod = array();
		foreach ($courses as $key => $course) {
			if($course['lang'] == 'ba8d54c1-a076-11df-82cf-001fc6e2768c')
			{
				$otdel = 'К/О';
			}else
			{
				$otdel = 'Р/О';
			}
			$cour['0']['fullname'] = $course['dis'].' - '. $course['course'];
		    $cour['0']['shortname'] = 'Экзамен'.' - '.$otdel.' - '.$course['d_code'];
		    $cour['0']['summary'] = $course['d_ref'];
		    $cour['0']['categoryid'] = '141';
		    $disref=$course['d_ref'];
		    	try
				    {
				       $resp = $soapClient->__soapCall('core_course_create_courses', array($cour));
				    }
				    catch (Exception $e)
				    {
				       // echo("<pre>");
				        //print_r($e);
				       // echo("</pre>");
				    }
				if (isset($resp)) {
				     echo("<pre>");
				     print_r($resp);
				     echo("</pre>");
				}
				$idgroup = 0;
			foreach ($arrRup as $key1 => $value) {
				if($value['lang'] == 'ba8d54c1-a076-11df-82cf-001fc6e2768c')
				{
					$otdel = 'К/О';
				}else
				{
					$otdel = 'Р/О';
				}
				$shortname = 'Экзамен'.' - '.$otdel.' - '.$value['d_code'];
				
			
				if($resp['0']['shortname'] == $shortname)
				{
					$idgroup ++;
					$courseid = $resp['0']['id'];

					$date = strtotime($value['control_exam_date']);
					$sdate = time();
					$fdate = $date+(($value['stime'] * 3600)+7200);
						
					$name = $value['spec'].' - '.$value['control_exam_date'].' - '.$value['stime'].' - '.$otdel;
					$module = '16';
					
					$timeopen= $sdate;
					$timeclose= $fdate;
					$timelimit='2700';
					$overduehandling='autosubmit';

					$result = mysql_query("INSERT INTO `mdl_quiz` (`course`, `name`,`timeopen`,`timeclose`,`timelimit`,`overduehandling`) VALUES ({$db->quote($courseid)},{$db->quote($name)},{$db->quote($timeopen)},{$db->quote($timeclose)},{$db->quote($timelimit)},{$db->quote($overduehandling)})");
					$quiz = mysql_insert_id();

					$section = "SELECT id FROM `mdl_course_sections` WHERE `course` = '".$courseid."'";
					$db->setQuery($section);
					$sectionobj = $db->loadObjectList();
					$sectionarr = json_decode(json_encode($sectionobj), True);
					$section = $sectionarr['0']['id']; 

					$result = mysql_query("INSERT INTO `mdl_course_modules` (`course`, `module`,`instance`,`section`) 
					VALUES ({$db->quote($courseid)},{$db->quote($module)},{$db->quote($quiz)},{$db->quote($section)})");
					$modules = mysql_insert_id();
					array_push($mod,$modules);
					// конец студ группа
				
				$query->select('rs.doc_ref');
			    $query->select('sp.code');
			    $query->from('jos_euniversity_cat_teachers_rup_students rs');
			    $query->from('jos_euniversity_users_students_profile sp');
			    $query->where('rs.doc_ref = "'.$value['ref'].'"');
			    $query->where('rs.student = sp.ref');
			   
			  	$this->_db->setQuery($query);
   				$arrStudRup = $this->_db->loadAssocList();

   				$result = "SELECT id FROM `mdl_context` WHERE contextlevel=50 AND instanceid= '".$courseid."'";
				$db->setQuery($result);
				$resultObj = $db->loadObjectList();
				$idcontextarr = json_decode(json_encode($resultObj), True);

				 $idcontext = $idcontextarr['0']['id'];
				

				$result2 = "SELECT id FROM `mdl_enrol` WHERE courseid = '".$courseid."' AND enrol='manual'";
				$db->setQuery($result2);
				$resultarr = $db->loadObjectList();
				$idenrolarr = json_decode(json_encode($resultarr), True);

				$idenrol = $idenrolarr['0']['id'];
				// создание курса
							
				$name = $value['spec'].' - '.$value['control_exam_date'].' - '.$value['stime'];
				$result = mysql_query("INSERT INTO `mdl_groups` (`courseid`, `name`) 
					VALUES ({$db->quote($courseid)},{$db->quote($name)})");
				$groupid = mysql_insert_id();

   				foreach ($arrStudRup as $key1 => $val) {

   					$stud = $val['code'];
   										
					$result = "SELECT id FROM `mdl_user` WHERE `username` = {$db->quote($stud)}";
					$db->setQuery($result);
					$resultObj = $db->loadObjectList();
					$studMoolde = json_decode(json_encode($resultObj), True);

					$stud1 = $studMoolde['0']['id'];
					$time = time();

					$result = mysql_query("INSERT INTO `mdl_user_enrolments` (status, enrolid, userid, timestart, timeend, timecreated, timemodified) 
						VALUES ('0', {$db->quote($idenrol)}, {$db->quote($stud1)}, {$db->quote($sdate)}, {$db->quote($fdate)}, {$db->quote($time)}, {$db->quote($time)})");
					$mdl_user_enrolments = mysql_insert_id();
					
					$result = mysql_query("INSERT INTO `mdl_role_assignments` (`roleid`, `contextid`, `userid`, `timemodified`) 
						VALUES ('5',{$db->quote($idcontext)},{$db->quote($stud1)}, {$db->quote($time)})");
					$mdl_role_assignments = mysql_insert_id();

					$result = mysql_query("INSERT INTO `mdl_groups_members` (`groupid`, `userid`) 
						VALUES ({$db->quote($groupid)},{$db->quote($stud1)})");
					 $mdl_groups_members = mysql_insert_id();

					// конец студ группа
				}
			}

			$comma_separated = implode(",",$mod);
			//print_r($comma_separated);
			$query = $db->setQuery(true);
					$query="UPDATE mdl_course_sections set sequence = {$db->quote($comma_separated)} WHERE course = {$db->quote($courseid)}";			
					$db->setQuery($query);
					$db->query();

				$query = $this->_db->getQuery(true);		

   				}
   				//break;	
		}
		// КОНЕЦ
       	   	
}
?>


