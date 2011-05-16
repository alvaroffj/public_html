<?php
include_once 'util/session.php';
include_once 'util/paginacion.php';

class CPrincipal {
    protected $_secName = "Monitoreo";
    protected $_CSec;
    protected $ss;
    protected $usuarioMP;
    public $layout = "vista/layout.phtml";
    public $showLayout = true;
    public $thisLayout = true;
    public $loged = false;
    public $usuario;

    function __construct() {
        $this->ss = new session();

        if ($this->checkLogin()) {
            $this->setSec();
        } else {
            include_once 'CLog.php';
            $this->_CSec = new CLog($this);
        }
    }

    public function getLayout() {
        if($this->thisLayout) return $this->layout;
        else return $this->_CSec->getLayout();
    }

    function getCSec() {
        return $this->_CSec;
    }

    function getSession() {
        return $this->ss;
    }

    function checkLogin() {
        return ($this->ss->existe("accountID") && $this->ss->existe("userID"));
//        return true;
    }

    function error($e) {
        switch($e) {
            case '404':
                $this->showLayout = false;
                echo "error 404<br>";
                break;
        }
    }

    function isAdmin() {
        return ($this->ss->get("roleID") == 1);
    }

    function isSuperAdmin() {
        return ($this->ss->get("roleID") == 0);
    }

    function setSec() {
        $this->sec = $_GET["sec"];
        $this->showLayout = true;
        $this->thisLayout = true;
        switch($this->sec) {
            case 'log':
                include_once 'CLog.php';
                $this->_CSec = new CLog($this);
                break;
            case 'monitoreo':
                include_once 'CMonitoreo.php';
                $this->_CSec = new CMonitoreo($this);
                break;
            case 'configuracion':
                include_once 'controlador/CConfiguracion.php';
                $this->_CSec = new CConfiguracion($this);
                break;
            case 'reporte':
                include_once 'controlador/CReporte.php';
                $this->_CSec = new CReporte($this);
                break;
            default:
                $this->sec = "monitoreo";
                include_once 'CMonitoreo.php';
                $this->_CSec = new CMonitoreo($this);
                break;
        }
    }

}
?>
