<?php namespace µMailPHP;
/**
 * Template class.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * A template.
 *
 * @package uMailPHP
 */
class Template extends \Nymph\Entity {
	const ETYPE = 'umailphp_template';
	protected $clientClassName = 'Template';
	public $clientEnabledMethods = ['defaultContent'];

	public function __construct($id = 0) {
		if (parent::__construct($id) !== null) {
			return;
		}
		// Defaults.
		$this->enabled = true;
		$this->replacements = [];
		$this->ac_other = 1;
	}

	public function info($type) {
		switch ($type) {
			case 'name':
				return $this->name;
			case 'type':
				return 'template';
			case 'types':
				return 'templates';
		}
		return null;
	}

	public function defaultContent() {
		$this->content = <<<EOF
<div style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; color: #3A3A3A;">
	<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" align="center" border="0">
		<tr><td valign="top" style="color:#000; font-size: 20px; font-weight: bold; text-align: left; line-height: 17px; background-color: #5baa45;">
			<div align="left" style="background: #5baa45;">
				<table class="table" width="600" cellpadding="0" cellspacing="0" align="center" border="0"><tr><td valign="top" style="text-align: left;">
					<div align="left" style="padding-top: 7px; padding-bottom: 9px"><a href="#site_link#">#system_name#</a></div>
				</td></tr></table>
			</div>
		</td></tr>
	</table>
	<br />
	<table class="table" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" align="center" border="0">
		<tr><td valign="top" style="color:#3A3A3A">#content#</td></tr>
	</table>
	<br />
	<br />
	<table class="table" width="600" cellpadding="8" cellspacing="0" bgcolor="#D8D8D8" align="center" border="0">
		<tr><td valign="top" style="color:#3A3A3A; font-size:14px; background-color: #D8D8D8; text-align:center; line-height:20px">You received this email because you have an account at <a href="#site_link#">#site_name#</a>.</td></tr>
	</table>
</div>
EOF;
		$this->document = <<<'EOF'
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>#subject#</title>
	<style type="text/css">
		.ExternalClass {width:100%;}
		.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
			line-height: 100%;}
		body {-webkit-text-size-adjust:none; -ms-text-size-adjust:none;}
		body {margin:0; padding:0;}
		table td {border-collapse:collapse;}
		h1, h2, h3, h4, h5, h6 {color: black; line-height: 100%;}
		a, a:link {color:#2A5DB0; text-decoration: underline;}
		@media only screen and (max-device-width: 480px) {
			body[style] .table {width:320px;}
		}
	</style>
</head>
<body style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; color: #3A3A3A;">
#content#
</body>
</html>
EOF;
	}

	/**
	 * Save the template.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		if (!isset($this->name)) {
			return false;
		}
		return parent::save();
	}

	/**
	 * Determine if this template is ready to use.
	 *
	 * @return bool True if the template is ready, false otherwise.
	 */
	public function ready() {
		if (!$this->enabled) {
			return false;
		}
		return true;
	}
}