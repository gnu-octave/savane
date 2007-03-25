#!/usr/bin/perl
# Test a project creation

use strict;
use warnings;
use WWW::Mechanize;
use Test::More qw(no_plan);

$ENV{SAVANE_CONF} = '/tmp/savane/savane';
use Savane;


my $agent = WWW::Mechanize->new();

# Login in
$agent->get('http://localhost:50080/');
$agent->follow_link(text => 'Login', n => '1');

$agent->form_number(2);
$agent->field('form_loginname', 'admin');
$agent->field('form_pw', 'admin');
$agent->click('login');


# Create a new project
$agent->follow_link(text => 'Register New Project', n => '1');
$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
# Test string escaping / quoting
$agent->field('form_purpose', '\'');
$agent->field('form_required_sw', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_unix_name', 'test');
$agent->field('form_full_name', 'test');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_license', 'gpl');
$agent->field('form_license_other', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
$agent->field('form_purpose', '\'');
$agent->field('group_type', '1');
$agent->field('form_required_sw', '');
$agent->field('form_license', 'gpl');
$agent->field('form_full_name', 'test');
$agent->field('form_license_other', '');
$agent->click('i_agree');


# Log out
$agent->follow_link(text => 'Logout', n => '1');


# Check that the project exists
ok(GetGroupSettings('test', 'status') eq 'P', 'Create project');

# Check that the associated task was created
# TODO
