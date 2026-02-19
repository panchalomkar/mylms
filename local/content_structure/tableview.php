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
 * Library functions.
 *
 * @package    report_custom_report
 * @author     Uvais
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */
//require_once 'locallib.php';

class tableview extends table_sql
{

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('image', 'name', 'timecreated', 'edit');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('icon', LANGFILE),
            get_string('programname', LANGFILE),
            get_string('timecreated', LANGFILE),
            get_string('action', LANGFILE)
        );
        $this->define_headers($headers);
        $this->set_attribute('class', 'generaltable generalbox datatable');
        $this->set_attribute('id', 'myTable');
    }

    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
    function col_timecreated($values)
    {
        global $CFG;
        return date('d-m-Y H:i', $values->timecreated);
    }

    function col_enter($values)
    {
        global $CFG;
        return '<a href="' . $CFG->wwwroot . BASEURL . '/container.php?pid=' . $values->id . '">'
            . '<img src="' . $CFG->wwwroot . BASEURL . '/images/enter.png" width="40"></a>';
    }

    function col_name($values)
    {
        global $CFG;
        return '<p style="font-size: 15px;">' . $values->name . '</p>';
    }

    function col_image($values)
    {
        global $CFG;

        // Default icon
        $defaulticon = '<img src="' . $CFG->wwwroot . LOCALFILEPATH . '/media/icons/default.png" width="40">';

        if (empty($values->image)) {
            return $defaulticon;
        }

        $filename = $values->image;
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Base path to icons
        $iconpath = $CFG->wwwroot . LOCALFILEPATH . '/media/icons/';

        // Map file extensions to icon filenames
        $iconmap = [
            'pdf' => 'pdf.png',
            'doc' => 'word.png',
            'docx' => 'word.png',
            'ppt' => 'ppt.png',
            'pptx' => 'ppt.png',
            'mp4' => 'video.png',
            'mov' => 'video.png',
            'avi' => 'video.png',
            'jpg' => 'image.png',
            'jpeg' => 'image.png',
            'png' => 'image.png',
            'gif' => 'image.png',
        ];

        $iconfile = isset($iconmap[$fileext]) ? $iconmap[$fileext] : 'default.png';

        return '<img src="' . $iconpath . $iconfile . '" width="40" alt="' . strtoupper($fileext) . '">';
    }

    function col_edit($values)
    {
        global $CFG, $USER, $OUTPUT;
        // edit button
        $sitecontext = context_system::instance();
        // prevent editing of admins by non-admins
        if (is_siteadmin($USER)) {
            $buttons[] = '<a href="' . $CFG->wwwroot . BASEURL . '/container.php?pid=' . $values->id . '">'
                . '<img src="' . $CFG->wwwroot . BASEURL . '/images/enter.png" width="20" title="View"></a>';
            $buttons[] = '<a href="#" class="btn-edit" data-id="' . $values->id . '" data-toggle="modal" data-target="#programModal">' .
                $OUTPUT->pix_icon('t/edit', get_string('edit')) . '</a>';


            $buttons[] = '<a href="#"><img src="' . $CFG->wwwroot . '/local/content_structure/images/delete.png" width="20" title="Delete" class="delete-content" id="' . $values->id . '" type="container"></a>';
        }

        return implode(' ', $buttons);
    }

    // function col_edit($values)
    // {
    //     global $CFG, $USER, $OUTPUT;
    //     // edit button
    //     $sitecontext = context_system::instance();
    //     // prevent editing of admins by non-admins
    //     if (is_siteadmin($USER)) {
    //         $buttons[] = '<a href="' . $CFG->wwwroot . BASEURL . '/container.php?pid=' . $values->id . '">'
    //             . '<img src="' . $CFG->wwwroot . BASEURL . '/images/enter.png" width="20" title="View"></a>';
    //         $url = new moodle_url(BASEURL . '/index.php', array('id' => $values->id));
    //         $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', get_string('edit')));

    //         $buttons[] = '<a href="#"><img src="' . $CFG->wwwroot . '/local/content_structure/images/delete.png" width="20" title="Delete" class="delete-content" id="' . $values->id . '" type="container"></a>';
    //     }

    //     return implode(' ', $buttons);
    // }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value)
    {
        // For security reasons we don't want to show the password hash.
        return null;
    }

    function display_report_list()
    {
        global $DB, $USER;

        $fields = "cr.id, cr.name, cr.image, cr.timecreated, '' As enter, '' AS edit";
        $from = "{local_content_structure} cr
                ";
        $where = 'parent=0';
        $this->set_sql($fields, $from, $where);
    }

}

/* * ****COURSE VIEW TABLE ******* */

class courseview extends table_sql
{

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        // Define columns and headers
        $columns = array('image', 'name', 'viewfile', 'timecreated', 'edit');
        $this->define_columns($columns);

        $headers = array(
            get_string('imageicon', LANGFILE),
            get_string('coursename', LANGFILE),
            get_string('viewfile', LANGFILE),
            get_string('timecreated', LANGFILE),
            get_string('edit', LANGFILE)
        );
        $this->define_headers($headers);

        // Enable sorting and default order
        $this->sortable(true, 'timecreated', SORT_DESC);

        // Disable sorting for columns not in the database
        $this->no_sorting('viewfile');
        $this->no_sorting('image');
        $this->no_sorting('edit');

        $this->set_attribute('class', 'generaltable generalbox datatable');
    }

    function col_viewfile($values)
    {
        global $CFG;

        $url = '';
        $type = '';
        $label = 'File'; // default
        $icon = 'fa-file-o'; // default
        $name = $values->name;
        // Determine URL source
        if (!empty($values->link)) {
            $url = $values->link;
        } elseif (!empty($values->image)) {
            $url = $CFG->wwwroot . '/local/content_structure/images/media/' . $values->image;
        }

        if (empty($url)) {
            return '<span class="text-muted">No File</span>';
        }

        // Extract file extension
        $pathinfo = pathinfo($url);
        $extension = isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : '';

        // File type definitions
        $filetypes = [
            'pdf' => ['type' => 'pdf', 'label' => 'PDF File', 'icon' => 'fa-file-pdf-o'],
            'ppt' => ['type' => 'ppt', 'label' => 'PowerPoint', 'icon' => 'fa-file-powerpoint-o'],
            'pptx' => ['type' => 'ppt', 'label' => 'PowerPoint', 'icon' => 'fa-file-powerpoint-o'],
            'mp4' => ['type' => 'video', 'label' => 'Video', 'icon' => 'fa-file-video-o'],
            'avi' => ['type' => 'video', 'label' => 'Video', 'icon' => 'fa-file-video-o'],
            'mov' => ['type' => 'video', 'label' => 'Video', 'icon' => 'fa-file-video-o'],
            'jpg' => ['type' => 'image', 'label' => 'Image', 'icon' => 'fa-file-image-o'],
            'jpeg' => ['type' => 'image', 'label' => 'Image', 'icon' => 'fa-file-image-o'],
            'png' => ['type' => 'image', 'label' => 'Image', 'icon' => 'fa-file-image-o'],
            'gif' => ['type' => 'image', 'label' => 'Image', 'icon' => 'fa-file-image-o'],
            'doc' => ['type' => 'doc', 'label' => 'Word Document', 'icon' => 'fa-file-word-o'],
            'docx' => ['type' => 'doc', 'label' => 'Word Document', 'icon' => 'fa-file-word-o'],
            'xls' => ['type' => 'excel', 'label' => 'Excel Sheet', 'icon' => 'fa-file-excel-o'],
            'xlsx' => ['type' => 'excel', 'label' => 'Excel Sheet', 'icon' => 'fa-file-excel-o'],
            // Add more types as needed
        ];

        if (!empty($values->link)) {
            // Detect YouTube
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                $type = 'youtube';
                $label = 'YouTube Video';
                $icon = 'fa-youtube-play';
            } else {
                $type = 'link';
                $label = 'Web Link';
                $icon = 'fa-link';
            }
        } elseif (isset($filetypes[$extension])) {
            $type = $filetypes[$extension]['type'];
            $label = $filetypes[$extension]['label'];
            $icon = $filetypes[$extension]['icon'];
        }


        return '<a href="#" 
                    class="btn  new-button viewfile" 
                    data-url="' . s($url) . '" 
                    data-type="' . $type . '" 
                    archtype="' . $name . '"
                    data-toggle="modal" 
                    data-target="#moduleModal"style="background:#003152; color:#fff;">
                    <i class="fa ' . $icon . ' tooltipelement_left"></i>
                    <span>' . get_string('viewfile', 'local_content_structure') . '</span>
                </a>';
    }




    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
    function col_timecreated($values)
    {
        global $CFG;
        return date('d-m-Y H:i', $values->timecreated);
    }

    function col_name($values)
    {
        global $CFG;
        return '<p style="font-size: 15px;">' . $values->name . '</p>';
    }

    function col_image($values)
    {
        global $CFG;

        $iconpath = $CFG->wwwroot . LOCALFILEPATH . '/media/icons/';
        $defaulticon = '<img src="' . $iconpath . 'default.png" width="40">';

        if (!empty($values->link)) {
            return '<img src="' . $iconpath . 'link.png" width="40" alt="Link">';
        }

        if (!empty($values->image)) {
            $ext = strtolower(pathinfo($values->image, PATHINFO_EXTENSION));
            $iconmap = [
                'pdf' => 'pdf.png',
                'doc' => 'word.png',
                'docx' => 'word.png',
                'ppt' => 'ppt.png',
                'pptx' => 'ppt.png',
                'mp4' => 'video.png',
                'mov' => 'video.png',
                'avi' => 'video.png',
                'jpg' => 'image.png',
                'jpeg' => 'image.png',
                'png' => 'image.png',
                'gif' => 'image.png',
            ];
            $iconfile = $iconmap[$ext] ?? 'default.png';
            return '<img src="' . $iconpath . $iconfile . '" width="40" alt="' . strtoupper($ext) . '">';
        }

        return $defaulticon;
    }




    function col_edit($values)
    {
        global $CFG, $USER, $OUTPUT;
        // edit button
        $sitecontext = context_system::instance();
        // prevent editing of admins by non-admins
        if (is_siteadmin($USER) || has_capability('local/content_structure:view', $sitecontext)) {
            $url = new moodle_url(BASEURL . '/container.php', array('pid' => $values->parent, 'id' => $values->id));
            $buttons[] = '<a href="' . $url->out() . '" title="Edit">
                    <i class="edw-icon fa fa-edit  fa-fw" style="font-size: 16px; color:#003152;"></i>
                  </a>';
            $url = new moodle_url('container.php', array('pid' => $values->parent, 'delete' => $values->id, 'sesskey' => sesskey()));
            $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        }

        return implode(' ', $buttons);
    }


    function other_cols($colname, $value)
    {
        // For security reasons we don't want to show the password hash.
        return null;
    }

    function display_report_list($pid)
    {
        global $DB, $USER;

        $fields = "cr.id, cr.parent, cr.name, cr.image, cr.link, cr.timecreated,
           '' AS addmodule, '' AS addlesson, '' AS addlearning, '' AS edit";
        $from = "{local_content_structure} cr
                ";
        $where = "parent=$pid AND archtype = 'course'";
        $this->set_sql($fields, $from, $where);
    }

}
