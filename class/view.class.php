<?php
class view
{
    protected $filepath;
    protected $filecontent;
    protected $content;
    protected $data = [];

    public function __construct($filename)
    {
        $filename .= '.model';
        if (is_file($filename))
        {
          $this->filepath = $filename;
          $this->filecontent = $this->get_content($this->filepath);
        }else{
          throw new Exception($filename . ' is not a valid file');
        }
    }
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    public function render()
    {
        ob_start();
        $this->content = $this->parse($this->filecontent);
        eval('?>' . $this->content);
        return ob_get_clean();
    }
    private function get_content($filename)
    {
        return file_get_contents($filename);
    }
    protected function parse($content)
    {
        $content = preg_replace('#{{ *([0-9a-z_\.\-]+) *}}#i', '<?php $this->_show_var(\'$1\'); ?>', $content);
        return $content;
    }
    protected function _show_var($name)
    {
        echo $this->getVar($name, $this->data);
    }
    protected function getVar($var, $parent)
    {
        $parts = explode('.', $var);
        if (count($parts) === 1)
        {
            return $this->getSubVar($var, $parent);
        }
        else
        {
            // Au moins 1 enfant
            $name = array_shift($parts);
            $new_parent = $this->getSubVar($name, $parent);
            $var = join('.', $parts);
            // Appel récursif
            return $this->getVar($var, $new_parent);
        }
    }
    protected function getSubVar($var, $parent)
    {
        if (is_array($parent))
        {
            if (isset($parent[$var]))
            {
                return $parent[$var];
            }
            return '';
        }
        if (is_object($parent))
        {
            // Si le parent est un objet
            if (is_callable([$parent, $var]))
            {
                // L'enfant est une méthode
                return $parent->$var();
            }
            if (isset($parent->$var))
            {
                // L'enfant est un attribut
                return $parent->$var;
            }
            return '';
        }
        return '';
    }
}
