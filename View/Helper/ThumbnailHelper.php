<?php
/**
 * Thumbnail helper
 *
 * Fast way to embed UploadPlugin's thumb in your views
 *
 * @package     upload
 * @subpackage  upload.views.helpers
 */
class ThumbnailHelper extends AppHelper {

    var $helpers = array('Html');

/**
 * Helper default options
 */
    var $_defaultOptions = array(
        'warnings' => true
    );


/**
 * Helper constructor
 *
 * @param    array   ThumbnailHelper options.
 * @todo     This helper need options?
 */
    function __construct(View $View, $options = array()) {
        parent::__construct($View, $options);
        $this->settings = array_merge($this->_defaultOptions, $options);
    }

/**
 *   Return url of (With HtmlHelper::image) $data thumbnail
 *
 *   @param  array  $data  Model entry
 *   @param  string $field  Field name where get the thumb name (in format: Model.name)
 *   @param  mixed  $thumbnailSizeName  Thumbnail size alias (thumb, small, etc..)
 *   @example
 *          $data = array('User'=> array('id' => 1, 'name' => 'Mirko', [...]));
 *          echo $this->Thumbnail->url('User.avatar', 'small', $data)
*   @return string
 */
    function src($field, $thumbnailSizeName, $data) {
        list($modelName, $modelField) = explode('.', $field);
        $thumbName  = Set::extract($field, $data);
        $src        = $this->_getSource($field, $thumbnailSizeName, $data);
        if (is_null($src) || is_null($thumbName)) {
            $errmsg = __d('upload', "{$modelName} primary key, {$modelField} or {$modelName}.{$modelField}_dir not exists in $data.");
            $this->__error($errmsg);
            return;
        }
        return $this->output($src);
    }

/**
 *   Print image tag (With HtmlHelper::image) with $data thumbnail
 *
 *   @param  string $field  Field name where get the thumb name (in format: Model.name)
 *   @param  mixed  $thumbnailSizeName  Thumbnail size alias (thumb, small, etc..)
 *   @param  array  $data  Model entry
 *   @param  mixed   Link's HTML attributes (@see HtmlHelper::link method)
 *   @example
 *          $data = array('User'=> array('id' => 1, 'name' => 'Mirko', [...]));
 *          echo $this->Thumbnail->image('User.avatar', 'small', $data, array('title' => 'User avatar'))
 *   @return string
 */
    function image($field, $thumbnailSizeName, $data, $htmlAttributes=array()) {
        list($modelName, $modelField) = explode('.', $field);
        $thumbName = Set::extract($field, $data);
        $src = $this->_getSource($field, $thumbnailSizeName, $data);

        if ($src === null || $src === null) {
            $errmsg = __d('upload', '%s\'s primary key, %s or %s.%s_dir not exists in $data.', $modelName, $modelField, $modelName, $modelField);
            $this->__error($errmsg);
            return;
        }
        return $this->output( $this->Html->image($src, $htmlAttributes) );
    }

/**
 *  Get Upload's plugin path from Model
 *
 *  Make sure that your model schema has a $field_dir where UploadPlugin store methodPath directory
 * (flat, primaryKey, random).
 *
 *  @param  string  Field name where get the thumb name (in format: Model.name)
 *  @param  string  ThumbnailSize name
 *  @param  array   Model row
 *  @return mixed   String path or null on fail
 */
    protected function _getSource($model, $thumbnailSizeName, $data) {
        list($model, $field) = explode('.', $model);
        if (!($Model = ClassRegistry::init($model, 'Model'))) {
            $this->__error(__d('upload', 'Model %s not exists', $model));
            return null;
        }
        if (!$Model->hasField($field)) {
            $this->__error(__d('upload', '%s not have field called %s', $model, $field));
            return null;
        }
        if (!$Model->Behaviors->attached('Upload')) {
            $this->__error(__d('upload', '%s not have Upload behavior', $model));
            return null;
        }

        $Upload         = $Model->Behaviors->Upload;
        $uploadSettings = $Upload->settings[$model][$field];
        $uploadDir      = $uploadSettings['path'];
        $uploadDirPathMethodField = $uploadSettings['fields']['dir'];

        if (Set::check($data, sprintf('%s.%s', $model, $uploadDirPathMethodField))) {
            // Upload pathMethod is in $data
            $tmp = Set::extract(sprintf('/%s/%s', $model, $uploadDirPathMethodField), $data);
            $uploadDirMethod = $tmp[0];
        } elseif (Set::check($data, sprintf('%s.%s', $model, $Model->primaryKey)) && $uploadSettings['pathMethod'] == '_getPathPrimaryKey') {
            // Triying to get Upload's methodPath from Model id (if is set) and the methodPath is set
            // to "primaryKey".
            $uploadDirMethod = $data[$model][$Model->primaryKey];
        } else {
            $this->__error(__d(
            	'upload',
            	"I could not find the thumb of %s. Be sure to enter the field
            	 in your table that is referenced
            	 UploadBehavior->settings['%s']['%s']['fields']['dir'] .",
                 $model, $model, $field
            ));
            return null;
        }
        $_thumbnailSrc	    = Set::extract(sprintf('%s.%s', $model, $field), $data);
        $_thumbnailName     = str_replace(array('{size}', '{filename}'), array($thumbnailSizeName, $field), $uploadSettings['thumbnailName']);
        $_thumbnailTokens   = explode('.', $_thumbnailSrc);
        $_thumbnailExt      = end($_thumbnailTokens);
        $thumbnailBasePath  = str_replace($uploadSettings['rootDir'], '', $uploadSettings['thumbnailPath'] );
        $_src = $thumbnailBasePath.DS.$uploadDirMethod.DS.$_thumbnailName.'.'.$_thumbnailExt;
    	return str_replace('webroot', '', $_src);
    }


    private function __error($errmsg) {
        if ($this->settings['warnings']) {
            trigger_error($errmsg);
        }
    }

}