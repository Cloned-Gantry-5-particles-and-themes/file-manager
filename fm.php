<?php
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);
define('ROOT', dirname(__FILE__));

function d($obj, $die = false) {
    echo '<pre>' . print_r($obj, true) . '</pre>';
    if ($die) die();
}
class Request {
    protected $params = array();
    
    public function __construct() {
        $this->params = array_merge($_GET, $_POST);
    }
    
    public function get($name, $default = NULL) {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }
    
    public function set($name, $value = NULL) {
        $this->params[$name] = $value;
        return $this;
    }
    
    public function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? true : false;
    }
    
    public function baseUrl($path = NULL) {
        defined( 'BASE_URL' ) || define( 'BASE_URL', preg_replace( '/(.*)\/'.  str_replace('.', '\.', basename( $_SERVER['SCRIPT_NAME'] ) ).'$/i', '$1', $_SERVER['SCRIPT_NAME'] ) );
        
        return $path === NULL ? BASE_URL : BASE_URL . $path;
    }
}

class Folder {
    protected $path;
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function getContent() {
        if ($this->path) {
            $dh = opendir($this->path);
            if ($dh) {
                $files = array();
                while (($file = readdir($dh)) !== FALSE) {
                    if ($file === '.' || $file === '..') {
                        // not a real file
                        continue;
                    }
                    
                    $info = array(
                        'path' => $this->path . DS . $file
                    );
                    if (preg_match('/^(.*)\.(\w+)$/i', $file, $matches)) {
                        $info['name'] = $matches[1];
                        $info['ext']  = $matches[2];
                        $info['file'] = $file;
                    } else {
                        $info['file'] = $file;
                        $info['name'] = $file;
                        $info['ext']  = '';
                    }
                    $info['is_dir'] = is_dir($info['path']);
                    $info['size']   = $info['is_dir'] ? NULL : $this->sizeInHumanReadble(filesize($info['path']));
                    
                    $files[] = $info;
                }
                
                return $files;
            }
            
            return false;
        }
        
        return false;
    }
    
    public static function sizeInHumanReadble($size) {
        $info = array(
            "size"  =>  $size,
            "in"    =>  "Bytes"
        );
        if ($size > 1000000000) {
            $info["size"]   = round(($size / 1000000000), 1);
            $info["in"]     = "Gb";
        } else if ($size > 1000000) {
            $info["size"]   = round(($size / 1000000), 1);
            $info["in"]     = "Mb";
        } else if ($size > 1000) {
            $info["size"]   = round(($size / 1000), 1);
            $info["in"]     = "Kb";
        } 
        return $info;
    }
}

/* Initial variables */
$title    = 'Simple File Manager - GMS';
$server   = $_SERVER;
$request  = new Request();
$base_url = $request->baseUrl();
$current_dir = $server['DOCUMENT_ROOT'] . $base_url;
$f = $request->get('f');
$d = $request->get('d');
if ($d) {
    $current_dir .= $d;
}

$folder = new Folder($current_dir);
$files  = $folder->getContent();
$error  = NULL;
if ($files === false) {
    $error = 'Unable to get contents of directory located at <strong>' . $folder->getPath() . '</strong>';
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="http://gms.fm/favicon.ico" />
        <title><?php echo $title;?></title>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

        <!-- Optional theme -->
        <link rel="stylesheet" href="http://getbootstrap.com/assets/css/docs.min.css">

        <!-- Font Awesome -->
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <style type="text/css">
            .bs-docs-container .row div[role="main"] {
                padding-top: 30px;
            }
            .right {
                text-align: right;
            }
        </style>
    </head>
    <body>
        <header class="navbar navbar-static-top bs-docs-nav" id="top" role="banner">
            <div class="container">
                <div class="navbar-header">
                    <a href="https://github.com/globalmediasoft/file-manager" class="navbar-brand">Simple File Manager</a>
                </div>
            </div>
        </header>
        <?php if (count($files)): ?>
        <div class="container bs-docs-container">
            <div class="row">
                <div class="col-md-9" role="main">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="1%"></th>
                                <th>File Name</th>
                                <th>Ext</th>
                                <th class="right">Size</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td>
                                    <?php if ($file['is_dir']): ?>
                                    <i class="fa fa-folder"></i>
                                    <?php else: ?>
                                    <i class="fa fa-file"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $file['name'];?></td>
                                <td><?php echo $file['ext'];?></td>
                                <td class="right"><?php echo $file['size'] ? $file['size']['size'] . ' ' . $file['size']['in'] : '';?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-3">
                    <div class="bs-docs-sidebar hidden-print hidden-xs hidden-sm affix" role="complementary" data-spy="affix" data-offset-top="120">
                        <ul class="nav bs-docs-sidenav">
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error;?></div>
        <?php endif; ?>

        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                
            });
        </script>
    </body>
</html>