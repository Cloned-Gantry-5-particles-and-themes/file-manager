<?php
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('FM', basename(__FILE__));
define('VERSION', '1.0.0');

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
    
    public function url($path) {
        return $this->baseUrl('/' . FM . '?d=' . urlencode($path));
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
        if ($this->path && is_dir($this->path)) {
            $dh = opendir($this->path);
            if ($dh) {
                $folders = array();
                $files   = array();
                while (($file = readdir($dh)) !== FALSE) {
                    if ($file === '.' || $file === '..') {
                        // not a real file
                        continue;
                    }
                    
                    $info = array(
                        'path' => $this->path . DS . $file
                    );
                    if (preg_match('/^(.*)\.(\w+)$/i', $file, $matches)) {
                        $info['name'] = empty($matches[1]) ? '.' : $matches[1];
                        $info['ext']  = $matches[2];
                        $info['file'] = $file;
                    } else {
                        $info['file'] = $file;
                        $info['name'] = $file;
                        $info['ext']  = '';
                    }
                    
                    if (is_dir($info['path'])) {
                        $info['is_dir'] = true;
                        $info['size']   = NULL;
                        $info['ext']    = NULL;
                        $info['name']   = $file;
                        $folders[] = $info;
                    } else {
                        $info['is_dir'] = false;
                        $info['size']   = $this->sizeInHumanReadble(filesize($info['path']));
                        $info['ext']    = strtolower($info['ext']);
                        $files[] = $info;
                    }
                }
                
                return array_merge($folders, $files);
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
$doc_root = preg_replace('/\/$/i', '', $server['DOCUMENT_ROOT']);
$current_dir = $doc_root;
$do = $request->get('do');
$f  = $request->get('f');
$d  = urldecode($request->get('d'));
if (!$d) {
    $d = $base_url;
} else {
    $d = preg_replace('/(\/{2,})/i', '/', $d);
}
$current_dir .= $d;

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
        <?php /*<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">*/?>
        <link rel="stylesheet" href="<?php echo $base_url;?>/plugins/bootstrap/3.2.0/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <?php /*<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">*/?>
        <link href="<?php echo $base_url;?>/plugins/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <style type="text/css">
            body { padding-top: 70px; padding-bottom: 70px; }
            .right {
                text-align: right;
            }
            #actions button {text-align: left;}
            #actions button i {margin-right: 5px;}
        </style>
    </head>
    <body>
        <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
            <div class="container">
                <div class="row">
                    <form class="navbar-form navbar-left" role="search">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><?php echo $doc_root;?></span>
                                <input type="text" class="form-control" name="d" placeholder="Current Location" value="<?php echo $d;?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Browse</button>
                    </form>
                </div>
            </div>
        </nav>
        <div class="container" id="main">
            <?php $breadcrumbs = explode('/', $d); if ( $d !== '/' && count($breadcrumbs)):?>
            <div class="row">
                <div class="col-md-12">
                    <ol class="breadcrumb">
                        <?php $link = ''; foreach ($breadcrumbs as $breadcrumb): $link .= '/' . $breadcrumb ?>
                        <li><a href="<?php echo $request->url($link);?>"><?php echo empty($breadcrumb) ? '/' : $breadcrumb;?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($files && count($files)): ?>
            <div class="row">
                <div class="col-md-9" role="main">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="1%"></th>
                                <th width="1%"></th>
                                <th>File Name</th>
                                <th>Ext</th>
                                <th class="right">Size</th>
                            </tr>
                        </thead>
                        <?php
                        $extensions = array(
                            'xls' => 'fa-file-excel-o',
                            'xlsx' => 'fa-file-excel-o',
                            'doc' => 'fa-file-word-o',
                            'docx' => 'fa-file-word-o',
                            'ppt' => 'fa-file-powerpoint-o',
                            'pptx' => 'fa-file-powerpoint-o',
                            'pdf' => 'fa-file-pdf-o',
                            
                            'php' => 'fa-file-code-o',
                            'js' => 'fa-file-code-o',
                            
                            'mp3' => 'fa-file-sound-o',
                            'mp4' => 'fa-file-video-o',
                            'flv' => 'fa-file-video-o',
                            
                            'jpg' => 'fa-file-image-o',
                            'png' => 'fa-file-image-o',
                            'gif' => 'fa-file-image-o',
                            
                            'rar' => 'fa-file-archive-o',
                            'zip' => 'fa-file-archive-o',
                            'gz' => 'fa-file-archive-o',
                            '7z' => 'fa-file-archive-o'
                        );
                        ?>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td>
                                    <input type="checkbox">
                                </td>
                                <td>
                                    <?php if ($file['is_dir']): ?>
                                    <i class="fa fa-folder"></i>
                                    <?php elseif ($file['ext'] && isset($extensions[$file['ext']])): ?>
                                    <i class="fa <?php echo $extensions[$file['ext']];?>"></i>
                                    <?php else: ?>
                                    <i class="fa fa-file-o"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file['is_dir']): ?>
                                        <?php if (empty($file['name'])): ?>
                                        <?php else: ?>
                                        <a href="<?php echo $request->url($d . '/' . $file['name']);?>"><?php echo $file['name'];?></a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo $file['name'];?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $file['ext'];?></td>
                                <td class="right">
                                    <?php if ($file['size']): ?>
                                    <?php echo $file['size']['size'] . ' ' . $file['size']['in'];?>
                                    <?php else: ?>
                                    <a href="javascript:void(0)">Calculate</a>
                                    <?php endif ; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="col-md-3" id="actions">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Actions</h3>
                        </div>
                        <div class="panel-body">
                            <button type="button" class="btn btn-default btn-block"><i class="fa fa-compress"></i>Compress</button>
                            
                            <button type="button" class="btn btn-default btn-block"><i class="fa fa-plus"></i>Create Folder</button>
                            <button type="button" class="btn btn-danger btn-block"><i class="fa fa-times-circle"></i>Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error;?></div>
            <?php endif; ?>
        </div>
        
        <nav class="navbar navbar-default navbar-fixed-bottom" role="navigation">
            <div class="container">
                <ul class="nav navbar-nav">
                    <li><a href="https://github.com/globalmediasoft/file-manager" target="_blank">Simple File Manager</a></li>
                    <li class="disabled"><a href="javascript:void(0)">v<?php echo VERSION;?></a></li>
                </ul>
            </div>
        </nav>

        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <?php /*<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>*/?>
        <script src="<?php echo $base_url;?>/plugins/jquery/1.11.1/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <?php /*<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>*/?>
        <script src="<?php echo $base_url;?>/plugins/bootstrap/3.2.0/js/bootstrap.min.js"></script>
        <script type="text/javascript">
            var actionsHolder, actionsWidth;
            $(document).ready(function() {
                actionsHolder = $('#actions');
                actionsWidth  = actionsHolder.width() + 30;
                actionsHolder.affix({
                    offset: {
                        top: 0
                    }
                });
                actionsHolder.bind('affix.bs.affix', function() {
                    $(this).css({
                        'margin-left': $('div[role="main"]').width() + 30,
                        width: actionsWidth,
                        top: 60
                    });
                });
                actionsHolder.bind('affixed-top.bs.affix', function() {
                    $(this).css({
                        'margin-left': '0',
                        top: 0
                    });
                });
            });
        </script>
    </body>
</html>