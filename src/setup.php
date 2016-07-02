<?php

// Get definitions.
$classes = get_declared_classes();
$definitions = [];
foreach ($classes as $key => $cur_class) {
  if (is_subclass_of($cur_class, '\µMailPHP\Definition')) {
    $definitions[$cur_class] = [
      'cname' => $cur_class::$cname,
      'description' => $cur_class::$description,
      'expectsRecipient' => $cur_class::$expectsRecipient,
      'macros' => $cur_class::$macros,
      'subject' => $cur_class::getSubject(),
      'html' => $cur_class::getHTML(),
    ];
  }
}

$config = \SciActive\RequirePHP::_('µMailPHPConfig');
$examples = [
  'site_name' => $config->site_name['value'],
  'site_link' => $config->site_link['value'],
  'datetime_sort' => \µMailPHP\Mail::formatDate(time(), 'full_sort'),
  'datetime_short' => \µMailPHP\Mail::formatDate(time(), 'full_short'),
  'datetime_med' => \µMailPHP\Mail::formatDate(time(), 'full_med'),
  'datetime_long' => \µMailPHP\Mail::formatDate(time(), 'full_long'),
  'date_sort' => \µMailPHP\Mail::formatDate(time(), 'date_sort'),
  'date_short' => \µMailPHP\Mail::formatDate(time(), 'date_short'),
  'date_med' => \µMailPHP\Mail::formatDate(time(), 'date_med'),
  'date_long' => \µMailPHP\Mail::formatDate(time(), 'date_long'),
  'time_sort' => \µMailPHP\Mail::formatDate(time(), 'time_sort'),
  'time_short' => \µMailPHP\Mail::formatDate(time(), 'time_short'),
  'time_med' => \µMailPHP\Mail::formatDate(time(), 'time_med'),
  'time_long' => \µMailPHP\Mail::formatDate(time(), 'time_long')
];

?>
<!DOCTYPE html>
<html ng-app="setupApp">
  <head>
    <title>µMailPHP Setup App</title>
    <meta charset="utf-8">
    <script type="text/javascript">
      (function(){
        var s = document.createElement("script"); s.setAttribute("src", "https://www.promisejs.org/polyfills/promise-5.0.0.min.js");
        (typeof Promise !== "undefined" && typeof Promise.all === "function") || document.getElementsByTagName('head')[0].appendChild(s);
      })();
      NymphOptions = {
        restURL: <?php echo json_encode($restEndpoint); ?>
      };
      baseURL = <?php echo json_encode($baseURL); ?>;
      Definitions = <?php echo json_encode($definitions); ?>;
      Examples = <?php echo json_encode($examples); ?>;
      Tilmeld = <?php echo json_encode(class_exists('\Tilmeld\User')); ?>;
    </script>
    <script src="<?php echo htmlspecialchars($sciactiveBaseURL); ?>nymph-client/src/Nymph.js"></script>
    <script src="<?php echo htmlspecialchars($sciactiveBaseURL); ?>nymph-client/src/Entity.js"></script>
    <script src="<?php echo htmlspecialchars($baseURL); ?>src/Rendition.js"></script>
    <script src="<?php echo htmlspecialchars($baseURL); ?>src/Template.js"></script>

    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-route.js"></script>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="<?php echo htmlspecialchars($sciactiveBaseURL); ?>pform/css/pform.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($sciactiveBaseURL); ?>pform/css/pform-bootstrap.min.css">

    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/codemirror.min.js"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/codemirror.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/mode/css/css.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/mode/javascript/javascript.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/mode/xml/xml.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/mode/htmlmixed/htmlmixed.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/addon/fold/xml-fold.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/addon/edit/matchtags.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/addon/edit/matchbrackets.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/addon/edit/closetag.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/4.11.0/addon/edit/closebrackets.min.js"></script>
    <script src="https://rawgithub.com/angular-ui/ui-codemirror/bower/ui-codemirror.min.js"></script>

    <script src="<?php echo htmlspecialchars($baseURL); ?>src/setupApp.js"></script>

    <style type="text/css">
      form {
        padding-bottom: 64px;
      }
      .button-panel {
        position: fixed;
        bottom: 0;
        padding-bottom: 0;
        margin-bottom: 0;
        border-radius: 0;
        width: 100%;
        border: 0;
        z-index: 100;
        height: 64px;
      }
    </style>
  </head>
  <body>
    <div class="container" ng-controller="MainController">
      <div class="page-header">
        <h1>µMailPHP Setup App</h1>
      </div>
      <div class="row">
        <div class="col-lg-3">
          <ul class="nav nav-pills nav-stacked">
            <li role="presentation" ng-class="{active: $location.path() === '/'}"><a href="#">Instructions</a></li>
            <li role="presentation" ng-class="{active: $location.path().indexOf('/rendition/') === 0}"><a href="#/rendition/">Renditions</a></li>
            <li role="presentation" ng-class="{active: $location.path().indexOf('/template/') === 0}"><a href="#/template/">Templates</a></li>
          </ul>
        </div>
        <div class="col-lg-9">
          <div ng-view></div>
        </div>
      </div>
    </div>
  </body>
</html>
