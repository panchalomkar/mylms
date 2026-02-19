<?php

class Level {

    public function ad_save($post, $files) {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }

        $i = 0;
        $failed = false;
        $destination = 'images/';

        foreach ($post['level'] as $data) {

            $object = new stdClass();



            //file upload

            if ($files['icon']['error'][$i] == 0 && $files['icon']['size'][$i] > 0) {

                $FileType1 = strtolower(pathinfo(basename($files["icon"]["name"][$i]), PATHINFO_EXTENSION));
                if ($FileType1 == "jpg" || $FileType1 == "png" || $FileType1 == "jpeg" || $FileType1 == "gif") {
                    if ($post['id'][$i] > 0) {
                        $id = $post['id'][$i];
                        $record = $DB->get_record('custom_level', array('id' => $id));
                        if ($record->icon != '') {
                            @unlink($CFG->dirroot . '/local/mydashboard/images/' . $record->icon);
                        }
                    }
                    $filename = round(microtime(true) * 1000) . '.' . $FileType1;
                    $object->icon = $filename;
//                    $filename = time() . basename($_FILES["file"]["name"]);
                    move_uploaded_file($files["icon"]["tmp_name"][$i], $CFG->dirroot . '/local/mydashboard/' . $destination . $filename);
                }
            }

            $object->level = $post['level'][$i];
            $object->point = $post['point'][$i];
            $object->grade = $post['grade'][$i];
            $object->companyid = $selectedcompany;
            $object->timecreated = time();
            if ($object->level != '' && $object->point != '' && $object->grade != '') {
                if ($post['id'][$i] > 0) {

                    $object->id = $post['id'][$i];
                    $DB->update_record('custom_level', $object);
                } else {
                    $DB->insert_record('custom_level', $object);
                }
            }
            $i++;
        }
    }

}
