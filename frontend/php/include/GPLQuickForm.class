<?php /*-*-PHP-*-*/
/*
Quick HTML_QuickForm clone without the GPL-incompatible license
Copyright (C) 2007  Cliss XXI
Copyright (C) 2007  Sylvain Beucler
This file is part of GCourrier.

GCourrier is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GCourrier is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GCourrier; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class GPLQuickForm_Element
{
  private $name = '';
  private $type = NULL;
  private $constant = NULL;
  private $default = NULL;
  private $value = NULL;

  private $frozen = false;

  private $label = '';
  private $error = '';

  private $select_options = array();

  public function __construct($type, $name, $params)
  {
    $this->type = $type;
    $this->name = $name;

    switch($this->type)
      {
      case "header":
      case "date":
      case "password":
      case "submit":
      case "text":
      case "checkbox":
      case "textarea":
	if (isset($params[0]))
	  $this->label = $params[0];
	break;
      case "hidden":
	if (isset($params[0]))
	  $this->default = $params[0];
	break;
      case "select":
	$this->label = $params[0];
	$this->select_options = $params[1];
	break;
      }
  }

  public function setLabel($text)
  {
    $this->label = $text;
  }
  public function setText($text)
  {
    $this->setLabel($text);
  }
  public function setError($text)
  {
    $this->error = $text;
  }

  public function getValue()
  {
    if ($this->constant !== NULL)
      return $this->constant;
    if ($this->value !== NULL)
      return $this->value;
    return $this->default;
  }

  private function dateConvert($str)
  {
    list($year, $month, $day) = split('-', $str);
    return array('d' => $day, 'M' => $month, 'Y' => $year);
  }
  public function setValue($value)
  {
    if ($this->type == 'date' and is_string($value))
      $value = $this->dateConvert($value);
    $this->value = $value;
  }
  public function setDefault($default)
  {
    if ($this->type == 'date' and is_string($default))
      $default = $this->dateConvert($default);
    $this->default = $default;
  }
  public function setConstant($constant)
  {
    if ($this->type == 'date' and is_string($constant))
      $constant = $this->dateConvert($constant);
    $this->constant = $constant;
  }
  public function freeze()
  {
    $this->frozen = true;
  }

  public function display()
  {
    // Don't use $this->value directly, to take constants into account
    $value = $this->getValue();
    if ($this->type == 'header')
      {
	print "<tr><td colspan=2 style='background: lightgrey;"
	  . " text-align: left; font-weight: bold'>"
	  . "{$this->label}</td></tr>";
      }
    else if ($this->type == 'submit')
      {
	$disabled = "";
	if ($this->frozen)
	  $disabled = 'disabled="disabled"';

	print "<tr><td></td><td class='element'>";
	print "<input type='submit'"
	  . " name='{$this->name}' value='{$this->label}' $disabled />";
	print '</td></tr>';
      }
    else if ($this->type == 'hidden')
      {
	print "<input type='hidden' name='{$this->name}' value='$value' />";
      }
    else
      {
	print '<tr>';
	print "<td class='label'>{$this->label}</td>";
	print "<td class='element'>";
	if (!empty($this->error))
	  print "<span class='error'>$this->error</span><br />";
	switch($this->type)
	  {

	  case 'date':
	    if ($this->frozen)
	      {
		print $value['d'];
		print "<input type='hidden' name='{$this->name}[d]' value='{$value['d']}' />";
		print " ";
		$month = mktime(0, 0, 0, $value['M'], 1, 2001);
		print strftime('%b', $month);
		print "<input type='hidden' name='{$this->name}[M]' value='{$value['M']}' />";
		print " ";
		print $value['Y'];
		print "<input type='hidden' name='{$this->name}[Y]' value='{$value['Y']}' />";
	      }
	    else
	      {
		print "<select name='{$this->name}[d]'>";
		for ($i = 1; $i <= 31; $i++)
		  {
		    $label = $i;
		    if ($i < 10)
		      $label = "0$label";
		    $selected = '';
		    if ($i == $value['d'])
		      $selected = 'selected="selected"';
		    print "<option value='$i' $selected>$label</option>";
		  }
		print "</select>";
		print "<select name='{$this->name}[M]'>";
		for ($i = 1; $i <= 12; $i++)
		  {
		    $month = mktime(0, 0, 0, $i, 1, 2001);
		    $selected = '';
		    if ($i == $value['M'])
		      $selected = 'selected="selected"';
		    print "<option value='$i' $selected>".strftime('%b', $month)."</option>";
		  }
		print "</select>";
		print "<select name='{$this->name}[Y]'>";
		for ($i = 2001; $i <= intval(date('Y')) + 3; $i++)
		  {
		    $selected = '';
		    if ($i == $value['Y'])
		      $selected = 'selected="selected"';
		    print "<option value='$i' $selected>$i</option>";
		  }
		print "</select>";
	      }
	    break;

	  case 'password':
	    if ($this->frozen)
	      print "*****";
	    else
	      print "<input type='password' name='{$this->name}' value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
	    break;

	  case 'text':
	    if ($this->frozen)
	      {
		print "$value";
		print "<input type='hidden' name='{$this->name}' value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
	      }
	    else
	      {
		print "<input type='text' name='{$this->name}' value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
	      }
	    break;

	  case 'select':
	    if ($this->frozen)
	      {
		if ($value != NULL)
		  {
		    print $this->select_options[$value];
		    print "<input type='hidden' name='{$this->name}' value='$value' />";
		  }
	      }
	    else
	      {
		print "<select name='{$this->name}'>";
		foreach($this->select_options as $id => $text)
		  {
		    $selected = '';
		    if ($id == $value)
		      $selected = "selected='selected'";
		    print "<option value='$id' $selected>$text</option>";
		  }
		print "</select>";
	      }
	    break;

	  case 'textarea':
	    $readonly = "";
	    if ($this->frozen)
	      $readonly = "readonly='readonly'";
	    print "<textarea name='{$this->name}' wrap='virtual' cols='60' rows='10' $readonly>"
	      . htmlspecialchars($value, ENT_QUOTES)
	      . "</textarea>";
	    break;

	  case 'checkbox':
	    $checked = "";
	    if ($value === NULL)
	      $value = '1';
	    else
	      $checked = 'checked="checked"';
	    $disabled = "";
	    if ($this->frozen)
	      $disabled = 'disabled="disabled"';
	    print "<input type='checkbox' name='{$this->name}' value='$value' $checked $disabled />";
	    break;
	  }
	print '</td>';
	print '</tr>';
      }
  }
}


class GPLQuickForm
{
  private $name = '';
  private $method = '';
  private $in = array();

  private $requiredNote = '<span style="font-size: smaller"><span style="color: red">*</span> required field</span>';
  private $jsWarnings_pref = 'The form is not valid';
  private $jsWarnings_post = '';
  private $elements = array();
  private $rules = array();

  public function __construct($name='', $method='post')
  {
    $this->name = $name;
    $this->method = $method;
    
    switch ($method)
      {
      case "get":
	$this->in = $_GET;
	break;
      case 'post':
      default:
	$this->in = $_POST;
	break;
      }
  }
  
  public function setRequiredNote($html_text)
  {
    $this->requiredNote = $html_text;
  }
  public function setJsWarnings($pref, $post)
  {
    $this->jsWarnings_pref = $pref;
    $this->jsWarnings_post = $post;
  }

  public function addElement()
  {
    $arg_list = func_get_args();

    $type = array_shift($arg_list);
    $name = array_shift($arg_list);
    $params = $arg_list;

    if (!is_string($type))
      throw new Exception("Adding elements as objects not supported");

    $this->elements_debug[$name] = array($type, $arg_list);

    $this->elements[$name] = new GPLQuickForm_Element($type, $name, $params);

    // Set the value from $_GET/$_POST if available
    if (isset($this->in[$name]))
      $this->elements[$name]->setValue($this->in[$name]);
  }
  public function getElement($name)
  {
    return $this->elements[$name];
  }


  public function setConstants($constants)
  {
    foreach($constants as $name => $value)
      {
	if (isset($this->elements[$name]))
	  $this->elements[$name]->setConstant($value);
      }
  }
  public function setDefaults($defaults)
  {
    foreach($defaults as $name => $value)
      {
	if (isset($this->elements[$name]))
	  $this->elements[$name]->setDefault($value);
      }
  }

  public function exportValue($name)
  {
    return $this->elements[$name]->getValue();
  }
  public function exportValues()
  {
    $retval = array();
    foreach ($this->elements as $name => $object)
      $retval[$name] = $object->getValue();
    return $retval;
  }

  public function freeze($elts_to_freeze=null)
  {
    
    if ($elts_to_freeze == NULL)
      $elts_to_freeze = array_keys($this->elements);
    else if (!is_array($elts_to_freeze))
      $elts_to_freeze = array($elts_to_freeze);

    foreach ($elts_to_freeze as $name)
      $this->elements[$name]->freeze();
  }



  public function applyFilter($name, $callback)
  {
    $elt = $this->elements[$name];
    $old_value = $elt->getValue();
    if ($old_value !== NULL)
      {
	$new_value = call_user_func($callback, $old_value);
	$elt->setValue($new_value);
      }
  }

  // string $element, string $message, string $type, [string $format = null], [string $validation = 'server'], [boolean $reset = false], [boolean $force = false]
  public function addRule($name, $error_message, $type, $type_param=null, $side='server')
  {
    $this->rules[] = array($name, $error_message, $type, $type_param, $side);
  }
  public function validate()
  {
    $elt_is_valid = array();
    $form_is_valid = true;
    
    if (empty($this->in))
      // form not submitted yet
      return false;
    
    foreach($this->rules as $rule)
      {
	list ($name, $error_message, $type, $type_param, $side) = $rule;
	if (is_array($name))
	  {
	    $name_array = $name;
	    $name = $name_array[0];
	  }
	if (!isset($elt_is_valid[$name]))
	  $elt_is_valid[$name] = true;
	$elt = $this->elements[$name];

	if ($elt_is_valid[$name])
	  {
	    $rule_is_valid = false;
	    if (!is_array($name))
	      {
		$value = $elt->getValue();
	      }
	    switch($type)
	      {
	      case 'callback':
		$callback = $type_param;
		$rule_is_valid = call_user_func($callback, $value);
		break;
	      case 'required':
		$rule_is_valid = !empty($value);
		break;
	      case 'regex':
		$pattern = $type_param;
		$rule_is_valid = preg_match($pattern, $value) > 0;
		break;
	      case 'nonzero':
		$rule_is_valid = !preg_match('/^0/', $value);
		break;
	      case 'lettersonly':
		$rule_is_valid = preg_match('/^[a-zA-Z]*$/', $value);
		break;
	      case 'alphanumeric':
		$rule_is_valid = preg_match('/^[a-zA-Z0-9]*$/', $value);
		break;
	      case 'minlength':
		$rule_is_valid = (strlen($value) >= $type_param);
		break;
	      case 'maxlength':
		$rule_is_valid = (strlen($value) <= $type_param);
		break;
	      case 'rangelength':
		$rule_is_valid = (strlen($value) >= $type_param[0]
				  && strlen($value) <= $type_param[1]);
		break;
	      case 'compare':
		$name2 = $name_array[1];
		$elt2 = $this->elements[$name2];
		$value2 = $elt2->getValue();
		$rule_is_valid = ($value == $value2);
		break;
	      default:
		die("Unsupported rule type: $type");
	      }
	    if (!$rule_is_valid)
	      {
		$form_is_valid = false;
		$elt_is_valid[$name] = false;
		$elt->setError($error_message);
	      }
	  }
      }
    return $form_is_valid;
  }

  public function display()
  {
    print "<form id='$this->name' action='{$_SERVER['PHP_SELF']}' method='$this->method'>";
    print "
<style type='text/css'><!--
#$this->name .label {
  text-align: right;
  font-weight: bold;
}
#$this->name .element {
  text-align: left;
}
#$this->name .error {
  color: red;
}
// -->
</style>
";
    print '<table>';
    foreach ($this->elements as $element)
      $element->display();
    $there_is_a_required_field = 1;
    if ($there_is_a_required_field)
      print "<tr><td /><td class='element'>$this->requiredNote</td></tr>";
    print '</table>';
    print "</form>";
  }
}
