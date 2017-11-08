<?php
class view
{
    public function __construct($view)
    {
        $this->_view = $view;
        $this->_renderView = $this->_originalView = $this->openView();
    }
    private function openView()
    {
        try {
            $filename = 'view/'.$this->_view.'.model';
            $handle   = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        return $contents;
    }
    public function find($word, $original=false)
    {
        if ($original===false) {
            return strstr($this->_renderView, '{{'.$word.'}}');
        } else {
            return strstr($this->_originalView, '{{'.$word.'}}');
        }
    }
    public function replace($word, $substitute)
    {
        $this->_renderView = str_replace('{{'.$word.'}}', $substitute, $this->_renderView);
    }
    public function render()
    {
        echo $this->_renderView;
    }
}
