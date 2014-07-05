<?php namespace Jarnstedt\Former;

use Illuminate\Support\MessageBag;
use Illuminate\Config\Repository as Config;
use Illuminate\Session\Store as Session;
use Illuminate\Html\FormBuilder;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;

/**
 * Laravel 4 form builder
 *
 * @author  Joonas Järnstedt
 * @version 0.4
 */
class Former extends FormBuilder {

    /**
     * The default values for the form
     */
    protected $defaults = array();

    /**
     * The options for Former
     *
     * @var array
     */
    protected $options = array();

    /**
     * Stores the comments
     * field_name => comment
     * @var array
     */
    protected $comments = array();

    /**
     * Form errors
     */
    protected $errors;

    /**
     * Class constructor
     *
     * @param \Illuminate\Html\HtmlBuilder $html
     * @param  
     */
    public function __construct(HtmlBuilder $html, UrlGenerator $url, Session $session, Config $config)
    {
        $this->url = $url;
        $this->html = $html;
        $this->csrfToken = $session->getToken();
        $this->config = $config;
        $this->loadConfig();
        $this->errors = $session->get('errors');
        $this->session = $session;
    }

    /**
     * Function to instantiate the class
     *
     * @param  array $defaults
     * @return class
     */
    public function make($defaults = array())
    {
        if (is_object($defaults)) {
            $this->setModel($defaults);
        }
        return $this->setDefaults($defaults);
    }

    /**
     * Load all the default config options
     */
    private function loadConfig()
    {
        // L4 does not currently have a method for loading an entire config file
        // so we have to spin through them individually for now
        $options = array('formClass', 'autocomplete', 'nameAsId', 'idPrefix', 'requiredLabel', 'requiredPrefix',
            'requiredSuffix', 'requiredClass', 'controlGroupError', 'displayInlineErrors', 'commentClass'
        );
        foreach($options as $option) {
            $this->options[$option] = $this->config->get('former::' . $option);
        }
    }

    /**
     * Set form defaults
     *
     * @param  array $defaults
     * @return class
     */
    public function setDefaults($defaults = array())
    {
        if (count($defaults) > 0) {
            $this->defaults = (object)$defaults;
        }

        return $this;
    }

    /**
     * Set option(s) for the class
     *
     * Call with option key and value, or an array of options
     *
     * @param string|array $key
     * @param string $value
     * @return class
     */
    public function setOption($key, $value = '')
    {
        if (is_array($key)) {
            $this->options = array_merge($this->options, $key);
        } else {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return string
     */
    private function getOption($key)
    {
        return (isset($this->options[$key])) ? $this->options[$key] : '';
    }

    /**
     * Set comments
     *
     * Call with the field name and the comment, or an array
     * of comments
     *
     * @param mixed $name
     * @param string $comment
     * @return class
     */
    public function setComments($name, $comment = '')
    {
        if (is_array($name) && count($name) > 0) {
            $this->comments = array_merge($this->comments, $name);
        } else {
            $this->comments[$name] = $comment;
        }

        return $this;
    }

    /**
     * Overrides the base form open method to allow for automatic insertion of csrf tokens
     * and form class
     *
     * @param array  $attributes
     * @return string
     */
    public function open(array $attributes = array())
    {
        // Add in the form class if necessary
        if (empty($attributes['class']))
        {
            $attributes['class'] =  $this->getOption('formClass');
        }
        elseif (strpos($attributes['class'], 'form-') === false)
        {
            $attributes['class'] .= ' ' . $this->getOption('formClass');
        }

        // Auto-complete attribute
        if (empty($attributes['autocomplete']))
        {
            $attributes['autocomplete'] = $this->getOption('autocomplete');
        }
        unset($attributes['autocomplete']);
        return parent::open($attributes);
    }

    /**
     * Create a HTML hidden input element.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array   $attributes
     * @return string
     */
    public function hidden($name, $value = null, $attributes = array())
    {
        $value = $this->calculateValue($name, $value);

        return parent::hidden($name, $value, $attributes);
    }

    /**
     * Create a HTML text input element.
     *
     * @param string $name
     * @param string $label
     * @param null   $value
     * @param array  $attributes
     * @return string
     */
    public function text($name, $label = '', $value = null, $attributes = array())
    {
        $value = $this->calculateValue($name, $value);
        $attributes = $this->setAttributes($name, $attributes);
        $field = parent::text($name, $value, $attributes);

        return $this->buildWrapper($field, $name, $label);
    }

    /**
     * Create a HTML textarea input element.
     *
     * @param string $name
     * @param string $label
     * @param null $value
     * @param array $attributes
     * @return string
     */
    public function textarea($name, $label = '', $value = null, $attributes = array())
    {
        $value = $this->calculateValue($name, $value);
        $attributes = $this->setAttributes($name, $attributes);
        if ( ! isset($attributes['rows']))
        {
            $attributes['rows'] = 4;
        }
        $field = parent::textarea($name, $value, $attributes);

        return $this->buildWrapper($field, $name, $label);
    }

    /**
     * Create a HTML password input element.
     *
     * @param  string  $name
     * @param  string  $label
     * @param  array   $attributes
     * @return string
     */
    public function password($name, $label = '', $attributes = array())
    {
        $attributes = $this->setAttributes($name, $attributes);
        $field = parent::password($name, $attributes);

        return $this->buildWrapper($field, $name, $label);
    }

    /**
     * Create a HTML select element.
     *
     * @param  string  $name
     * @param  string  $label
     * @param  array   $options
     * @param  string  $selected
     * @param  array   $attributes
     * @return string
     */
    public function select($name, $label = '', $options = array(), $selected = null, $attributes = array())
    {        
        $modelOptions = $this->getModelOptions($name);

        if (!is_null($modelOptions)) {
            $options = $modelOptions;
        }

        $selected = $this->calculateValue($name, $selected);
        $attributes = $this->setAttributes($name, $attributes);
        $field = parent::select($name, $options, $selected, $attributes);

        return $this->buildWrapper($field, $name, $label);
    }

    /**
     * Get select options from form model if set
     * @param  $name Select name
     * @return mixed
     */
    private function getModelOptions($name)
    {
        if (is_null($this->model)) {
            return null;
        }
        return $this->model->{$name.'Options'}();
    }

    /**
     * Create a HTML checkbox input element.
     *
     * @param  string  $name
     * @param  string  $label
     * @param  string  $value
     * @param  bool    $checked
     * @param  array   $attributes
     * @return string
     */
    public function checkbox($name, $label = '', $value = '1', $checked = false, $attributes = array())
    {
        $checked = $this->calculateValue($name, $checked);
        $attributes = $this->setAttributes($name, $attributes);
        $field = parent::checkbox($name, $value, $checked, $attributes);

        return $this->buildWrapper($field, $name, $label, true);
    }

    /**
     * Create a HTML radio input element.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  bool    $checked
     * @param  array   $attributes
     * @return string
     */
    public function radio($name, $value = '1', $checked = false, $attributes = array())
    {
        $checked = $this->calculateValue($name, $checked, $value);
        $attributes = $this->setAttributes($name, $attributes);

        return parent::radio($name, $value, $checked, $attributes);
    }

    /**
     * Create a HTML file input element.
     *
     * @param  string  $name
     * @param  string  $label
     * @param  array   $attributes
     * @return string
     */
    public function file($name, $attributes = array())
    {
        $attributes = $this->setAttributes($name, $attributes);
        $field = parent::file($name, $attributes);

        return $this->buildWrapper($field, $name, $label);
    }

    /**
     * Create a HTML label element
     * @param  string $name 
     * @param  string $value 
     * @param  string $attributes 
     * @return string
     */
    public function label($name, $value = null, $attributes = array())
    {
        return $this->buildLabel($name, $value, $attributes);
    }

    /**
     * Builds the Twitter Bootstrap control wrapper
     *
     * @param  string  $field The html for the field
     * @param  string  $name The name of the field
     * @param  string  $label The label name
     * @param  boolean $checkbox
     * @return string
     */
    private function buildWrapper($field, $name, $label = '', $checkbox = false)
    {
        if ($this->errors and $this->errors instanceof MessageBag) {
            $error = $this->errors->first($name);
        }

        $comment = '';
        if (!empty($this->comments[$name])) {
            $comment = '<div class="'.$this->getOption('commentClass').'">';
            $comment .= $this->comments[$name];
            $comment .= '</div>';
        }

        $class = 'form-group';
        if ($this->getOption('controlGroupError') && ! empty($error)) {
            $class .= ' ' . $this->getOption('controlGroupError');
        }

        $id = ($this->getOption('nameAsId')) ? ' id="form-group-'.$name.'"' : '';
        $out  = '<div class="'.$class.'"'.$id.'>';
        $out .= $this->buildLabel($name, $label);

        $out .= ($checkbox === true and !empty($field)) ? '<label class="checkbox">' : '';
        $out .= $field;

        if ($this->getOption('displayInlineErrors') && ! empty($error)) {
            $out .= $error;
        }

        if ($checkbox) {
            if (!empty($this->comments[$name])) {
                $out .= $comment;
            }
            if (!empty($field)) {
                $out .= '</label>';
            }
        } else {
            $out .= $comment;
        }

        $out .= '</div>'.PHP_EOL;

        return $out;
    }

    /**
     * Builds the label html
     *
     * @param  string  $name The name of the html field
     * @param  string  $label The label name
     * @return string
     */
    private function buildLabel($name, $label = '', $attributes = array())
    {
        $out = '';
        if (!empty($label)) {
            if (!empty($attributes['class'])) {
                $attributes['class'] .= ' control-label';
            } else {
                $attributes['class'] = 'control-label';
            }
            if ($this->getOption('requiredLabel') && substr($label, -strlen($this->getOption('requiredLabel'))) == $this->getOption('requiredLabel')) {
                $label = $this->getOption('requiredPrefix') . str_replace($this->getOption('requiredLabel'), '', $label) . $this->getOption('requiredSuffix');
                $attributes['class'] .= ' ' . $this->getOption('requiredClass');
            }
            $name = $this->getOption('idPrefix') . $name;
            $out .= parent::label($name, $label, $attributes);
        }

        return $out;
    }

    /**
     * Automatically populate the form field value
     *
     * @todo Note that there is s small error with checkboxes that are selected by default
     * and then unselected by the user. If validation fails, then the checkbox will be
     * selected again, because unselected checkboxes are not posted and there is no way
     * to get this value after the redirect.
     *
     * @param  string $name Html form field to populate
     * @param  string $default The default value for the field
     * @param  string $radioValue Set to true for radio buttons
     * @return string
     */
    private function calculateValue($name, $default = '', $radioValue = '')
    {
        $result = '';
        $oldInput = $this->session->getOldInput($name);
        // First check if there is post data
        // This assumes that you are redirecting after failed post
        // and that you have flashed the data
        // @see http://laravel.com/docs/input#old-input
        if ($oldInput !== null) {
            $result = ($radioValue)
                ? $oldInput == $radioValue
                : $this->session->getOldInput($name, $default);

        }

        // check if there is a default value set specifically for this field
        elseif (!empty($default)) {
            $result = $default;
        } elseif (!is_null($this->model)) {
            $result = $this->model->{$name};
        }

        // lastly, check if any defaults have been set for the form as a whole
        elseif (isset($this->defaults->$name))
        {
            $result = ($radioValue)
                ? $this->defaults->$name == $radioValue
                : $this->defaults->$name;
        }

        return $result;
    }

    /**
     * Create an id attribute for each field and also
     * determines if there is an comment field
     *
     * @param  string $name
     * @param  array  $attributes
     * @return array
     */
    private function setAttributes($name, $attributes = array())
    {
        // set the comment
        if (!empty($attributes['comment'])) {
            $this->comments[$name] = $attributes['comment'];
            unset($attributes['comment']);
        }

        // set the id attribute
        if ($this->getOption('nameAsId') && !isset($attributes['id'])) {
            $attributes['id'] = $this->getOption('idPrefix') . $name;
        }

        // if the disabled attribute is set to false, then we will actually unsert it
        // as some browsers will set the field to disabled
        if (isset($attributes['disabled']) && !$attributes['disabled']) {
            unset($attributes['disabled']);
        }

        // add "form-control" class to all inputs
        if (empty($attributes['class'])) {
            $attributes['class'] = ' form-control';
        } else {
            $attributes['class'] .= ' form-control';
        }

        return $attributes;
    }

    /**
     * Create a group of form actions (buttons).
     *
     * @param  mixed  $buttons  String or array of HTML buttons.
     * @return string
     */
    public function actions($buttons)
    {
        $out  = '<div class="form-actions">';
        $out .= is_array($buttons) ? implode('', $buttons) : $buttons;
        $out .= '</div>';

        return $out;
    }

    /**
     * Create a HTML submit input element.
     *
     * @param  string $value        Button text
     * @param  array  $attributes
     * @param  string $btn_class
     * @return mixed
     */
    public function submit($value = null, $attributes = array())
    {
        $btn_class = 'btn';

        $attributes['type'] = 'submit';
        if (!isset($attributes['class'])) {
            $attributes['class'] = $btn_class;
        } elseif (strpos($attributes['class'], $btn_class) === false) {
            $attributes['class'] .= ' ' . $btn_class;
        }

        return $this->button($value, $attributes);
    }

    /**
     * Create a HTML reset input element.
     *
     * @param  string  $value
     * @param  array   $attributes
     * @return string
     */
    public function reset($value, $attributes = array())
    {
        $attributes['type'] = 'reset';
        $attributes['class'] .= ' btn';
        return $this->button($value, $attributes);
    }

}
