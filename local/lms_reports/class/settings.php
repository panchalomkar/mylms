<?php

abstract class local_rap_settings
{

    /**
     *
     * @var string
     */
    protected $pluginName = '';

    /**
     *
     * @var array
     */
    protected $keys = array();

    /**
     *
     * @var array
     */
    protected $settings = array();

    /**
     *
     * @var string
     */
    protected $prefix;

    /**
     *
     * @var string
     */
    protected $suffix;


    /**
     *
     * @param string $prefix
     * @param string $suffix
     */
    function __construct($prefix = null, $suffix = null)
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }


    function __get($key)
    {
        if(! in_array($key, $this->keys))
        {
            return null;
        }
        
        $method = "get$key";
        if(method_exists($this, $method))
        {
            return $this->$method();
        }
        else
        {
            return isset($this->settings[$key]) ? $this->settings[$key] : null;
        }
    }


    function __set($key, $value)
    {
        if(! in_array($key, $this->keys))
        {
            return null;
        }
        
        $method = "set$key";
        if(method_exists($this, $method))
        {
            $this->$method($value);
        }
        else
        {
            $this->settings[$key] = $value;
        }
    }


    /**
     *
     * @return theme_rapfull_settings_base
     */
    function save()
    {
        foreach($this->keys as $key)
        {
            if(isset($this->settings[$key]))
            {
                $val = $this->settings[$key];
            }
            else
            {
                $val = null;
            }
            
            set_config("{$this->prefix}{$key}{$this->suffix}", $val, $this->pluginName);
        }
        
        return $this;
    }


    /**
     * Load all settings from database
     *
     * @return theme_rapfull_settings_base
     */
    function load()
    {
        foreach($this->keys as $key)
        {
            $value = get_config($this->pluginName, "{$this->prefix}{$key}{$this->suffix}");
            $this->settings[$key] = $value;
        }
        
        return $this;
    }


    /**
     *
     * @return array
     */
    function getSettings()
    {
        $k = array();
        foreach($this->keys as $key)
        {
            $k[$key] = $this->__get($key);
        }
        
        return $k;
    }


    /**
     *
     * @return theme_rapfull_settings_base
     */
    function clearCache()
    {
        purge_all_caches();
        return $this;
    }


    /**
     *
     * @param array $data
     * @return local_rap_settings
     */
    function populate(array $data)
    {
        foreach($this->keys as $key)
        {
            if(isset($data[$key]))
            {
                $this->__set($key, $data[$key]);
            }
            else
            {
                $this->__set($key, null);
            }
        }
        
        return $this;
    }


    /**
     *
     * @return local_rap_settings
     */
    function clearAll()
    {
        foreach($this->keys as $key)
        {
            $this->__set($key, null);
        }
        
        return $this;
    }


    /**
     *
     * @param int $draftitemid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @return array
     */
    protected function _saveFileFromDraftArea($draftitemid, $component, $filearea, $itemid)
    {
        global $CFG;
        
        require_once "{$CFG->libdir}/filelib.php";
        
        $pic = array();
        
        $filepath = '/';
        $context = context_system::instance();
        $contextid = $context->id;
        
        $files = file_get_drafarea_files($draftitemid);
        
        if(count($files->list))
        {
            global $DB;
            file_save_draft_area_files($draftitemid, $contextid, $component, $filearea, $itemid, array(
                'maxfiles' => 1 
            ));
            
            $pic = array(
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => $filepath,
                'filename' => $files->list[0]->filename 
            );
        }
        
        return $pic;
    }


    /**
     *
     * @param string $settingName
     * @return moodle_url | null
     */
    function getFileUrlFromSettings($settingName)
    {
        $url = null;
        
        $value = $this->__get($settingName);
        if($value)
        {
            $url = $this->getFileUrlFromJson($value);
        }
        
        return $url;
    }


    /**
     *
     * @param string $json
     * @return moodle_url | null
     */
    function getFileUrlFromJson($json)
    {
        $url = null;
        $params = @json_decode($json, true);
        if($params && is_array($params))
        {
            $url = $this->getFileUrlFromArray($params);
        }
        
        return $url;
    }


    /**
     *
     * @param array $params
     * @return moodle_url | null
     */
    function getFileUrlFromArray(array $params)
    {
        $url = null;
        $context = context_system::instance();
        
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, $params['component'], $params['filearea'], $params['itemid'], $params['filepath'], $params['filename']);
        if($file)
        {
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        }
        return $url;
    }


    /**
     *
     * @param string $settingName
     * @return int
     */
    function saveFileToDraftFromSetting($settingName)
    {
        global $CFG;
        
        require_once "{$CFG->libdir}/filelib.php";
        
        $value = $this->$settingName;
        $draftitemid = $this->saveFileToDraftFromJson($value);
        if($value)
        {
            $decoded = @json_decode($value, true);
        }
        
        return $draftitemid;
    }


    /**
     *
     * @param string $json
     * @return int
     */
    function saveFileToDraftFromJson($json)
    {
        $draftitemid = 0;
        $decoded = @json_decode($json, true);
        if($decoded)
        {
            $draftitemid = $this->saveFileToDraftFromArray($decoded);
        }
        
        return $draftitemid;
    }


    /**
     *
     * @param array $params
     * @return int
     */
    function saveFileToDraftFromArray(array $params)
    {
        global $CFG;
        require_once "{$CFG->libdir}/filelib.php";
        
        $draftitemid = 0;
        
        $context = context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, $params['component'], $params['filearea'], $params['itemid'], $params['filepath'], $params['filename']);
        if($file)
        {
            file_prepare_draft_area($draftitemid, $file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid());
        }
        
        return $draftitemid;
    }


    /**
     *
     * @param array $value
     * @param string $propertyName
     */
    protected function _saveEditorValueAsJson($value, $propertyName)
    {
        if(! empty($value) and is_array($value))
        {
            $this->settings[$propertyName] = json_encode($value);
        }
        else
        {
            $this->settings[$propertyName] = null;
        }
    }


    /**
     *
     * @param string $propertyName
     * @return array
     */
    protected function _getEditorValueAsArray($propertyName)
    {
        $json = $this->settings[$propertyName];
        $val = array();
        $decoded = @json_decode($json, true);
        
        if($decoded)
        {
            $val = $decoded;
        }
        
        return $val;
    }
}