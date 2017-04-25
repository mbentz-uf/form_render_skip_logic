<?php
/*
 * Takes a json file and disables/enables certain forms for certain patients on
 * record_status_dashboard based on the given json file.
 *
 * TODO:
 * 1. adjust php json calls to use given json object
 * 2. test frsl_dashboard
 *	a. check to see how it handles changes in the json file
 *	b. check how it handles enableing instruments on certain visits/arms
 * 3. factor out repeated code across all fsrl hooks into a common library
 */
return function($project_id) {

    $URL = $_SERVER['REQUEST_URI'];

    //check if we are on the right page
    if(preg_match('/DataEntry\/record_status_dashboard/', $URL) == 1) {
        //get necesary information
        $patient_id = $_GET["id"];
        $project_json = json_decode('{
                           "control_field":{
                              "arm_name":"visit_1_arm_1",
                              "field_name":"patient_type"
                           },
                           "instruments_to_show":[
                              {
                             "control_field_value":"1",
                             "instrument_names":[
                                "sdh_details",
                                "radiology_sdh",
                                "surgical_data_sdh",
                                "moca_sdh",
                                "gose_sdh",
                                "telephone_interview_of_cognitive_status_sdh",
                                "surgical_data",
                                "moca",
                                "gose",
                                "telephone_interview_of_cognitive_status"
                             ]
                              },
                              {
                             "control_field_value":"2",
                             "instrument_names":[
                                "sah_details",
                                "radiology_sah",
                                "delayed_neurologic_deterioration_sah",
                                "ventriculostomysurgical_data_sah"
                             ]
                              },
                              {
                             "control_field_value":"3",
                             "instrument_names":[
                                "sdh_details",
                                "sah_details"
                             ]
                              }
                           ]
                        }'
                        , true);

        $arm_name = $project_json['control_field']['arm_name'];
        $field_name = $project_json['control_field']['field_name'];

	$patient_data_structure = '{ ';

	for($i = 0; $i < count($project_json['instruments_to_show']); $i++) {
		$control_field_value = $project_json['instruments_to_show'][$i]['control_field_value'];

		if($i != 0) {
			$patient_data_structure .= ',';
		}

		$patient_data_structure .= '"' . $control_field_value . '":';
		$control_field_value_patients = REDCap::getData($project_id,'json',null,'unique_id',$arm_name,null,false,false,false,'[' . $field_name . '] = "' . $control_field_value . '"',null,null);
		$patient_data_structure .=  $control_field_value_patients;

	}

	$patient_data_structure .= '}';


    }else {
        echo "<script> console.log('aborting frsl dashboard home page') </script>";
        return;
    }
?>

    <script>
        var json = <?php echo json_encode($project_json) ?>;
	var patient_data_structure = <?php echo $patient_data_structure ?>;
        var control_field_name = "<?php echo $field_name ?>";
        var control_field_value;

	function disableUnionOfForms(json) {
            var instruments = json.instruments_to_show;
            for (var names in instruments) {
                var forms = instruments[names].instrument_names;
                for (var form in forms) {
                    var form_to_disable = forms[form];
                    disableFormsWithProp(form_to_disable);
                }
            }
        }

        function enableDesiredForms(json, patient_data_structure) {
		var instruments_to_show = json.instruments_to_show;
		for(var i = 0; i < instruments_to_show.length; i++) {
			var control_value = instruments_to_show[i]['control_field_value'];
			var instruments_to_enable = instruments_to_show[i]['instrument_names'];
			var patients = patient_data_structure[control_value];
			for(var j = 0; j < patients.length; j++) {
				for(var k = 0; k < instruments_to_enable.length; k++) {
					enableFormsForPatientId(patients[j]['unique_id'], instruments_to_enable[k]);
				}
			}
		}
	}

        function enableFormsForPatientId(id, form) {
            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(form);

            for (var i = 0; i < rows.length; i++) {
                if (rows[i].cells[0].innerText == id) {
                    for (var j = 0; j < rows[i].cells.length; j++) {
                        if (reg.test(rows[i].cells[j].firstElementChild.href)) {
                            enableForm(rows[i].cells[j]);
                            return;
                        }
                    }
                }
            }
        }

        function form_render_skip_logic(json, patient_data_structure) {
		disableUnionOfForms(json);
		enableDesiredForms(json, patient_data_structure);
	}

        function disableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'none';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '.1';
            }
        }

        function enableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'auto';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '1';
            }
        }

        function disableFormsWithProp(property) {
            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(property);

            for (var i = 0; i < rows.length; i++) {
                for (var j = 0; j < rows[i].cells.length; j++) {
                    var link = rows[i].cells[j].firstElementChild.href;

                    if (reg.test(link)) {
                        disableForm(rows[i].cells[j]);
                    }
                }
            }
        }

        $('document').ready(function() {
                form_render_skip_logic(json, patient_data_structure);
        });
    </script>
    <?php
}
?>
